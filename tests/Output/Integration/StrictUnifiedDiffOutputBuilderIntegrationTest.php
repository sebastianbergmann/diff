<?php declare(strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Diff\Output;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;
use function file_put_contents;
use function implode;
use function is_dir;
use function preg_replace;
use function preg_split;
use function realpath;
use function sprintf;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\MyersDiff;
use SebastianBergmann\Diff\Utils\FileUtils;
use SebastianBergmann\Diff\Utils\UnifiedDiffAssertTrait;
use SplFileInfo;
use Symfony\Component\Process\Process;

#[CoversClass(StrictUnifiedDiffOutputBuilder::class)]
#[UsesClass(Differ::class)]
#[UsesClass(MyersDiff::class)]
#[RequiresOperatingSystem('Linux')]
final class StrictUnifiedDiffOutputBuilderIntegrationTest extends TestCase
{
    use UnifiedDiffAssertTrait;
    private string $dir;
    private string $fileFrom;
    private string $fileTo;
    private string $filePatch;

    /**
     * @return array<array{0: string, 1: string, 2: string, 3: array{fromFile: string, toFile: string, collapseRanges?: bool, fromFileDate?: string, toFileDate?: string}}>
     */
    public static function provideOutputBuildingCases(): array
    {
        return StrictUnifiedDiffOutputBuilderDataProvider::provideOutputBuildingCases();
    }

    /**
     * @return array<array{0: string, 1: string, 2: string, 3: array{fromFile: string, toFile: string}}>
     */
    public static function provideSample(): array
    {
        return StrictUnifiedDiffOutputBuilderDataProvider::provideSample();
    }

    /**
     * @return array<array{0: string, 1: string, 2: string}>
     */
    public static function provideBasicDiffGeneration(): array
    {
        return StrictUnifiedDiffOutputBuilderDataProvider::provideBasicDiffGeneration();
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideFilePairs(): iterable
    {
        $cases     = [];
        $fromFile  = __FILE__;
        $vendorDir = realpath(__DIR__ . '/../../../vendor');

        if ($vendorDir === false) {
            throw new RuntimeException('vendor directory not found.');
        }

        $fileIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendorDir, RecursiveDirectoryIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($fileIterator as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }

            $toFile   = $file->getPathname();
            $fromReal = realpath($fromFile);
            $toReal   = realpath($toFile);

            if ($fromReal === false || $toReal === false) {
                continue;
            }

            yield sprintf("Diff file:\n\"%s\"\nvs.\n\"%s\"\n", $fromReal, $toReal) => [$fromFile, $toFile];
            $fromFile = $toFile;
        }

        return $cases;
    }

    protected function setUp(): void
    {
        $this->dir       = realpath(__DIR__ . '/../../fixtures/out') . '/';
        $this->fileFrom  = $this->dir . 'from.txt';
        $this->fileTo    = $this->dir . 'to.txt';
        $this->filePatch = $this->dir . 'diff.patch';

        if (!is_dir($this->dir)) {
            throw new RuntimeException('Integration test working directory not found.');
        }

        $this->cleanUpTempFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanUpTempFiles();
    }

    #[DataProvider('provideFilePairs')]
    public function testIntegrationUsingPHPFileInVendorGitApply(string $fileFrom, string $fileTo): void
    {
        $from = FileUtils::getFileContent($fileFrom);
        $to   = FileUtils::getFileContent($fileTo);

        $diff = new Differ(new StrictUnifiedDiffOutputBuilder(['fromFile' => 'Original', 'toFile' => 'New']))->diff($from, $to);

        if ('' === $diff && $from === $to) {
            // odd case: test after executing as it is more efficient than to read the files and check the contents every time
            $this->addToAssertionCount(1);

            return;
        }

        $this->doIntegrationTestGitApply($diff, $from);
    }

    #[DataProvider('provideFilePairs')]
    public function testIntegrationUsingPHPFileInVendorPatch(string $fileFrom, string $fileTo): void
    {
        $from = FileUtils::getFileContent($fileFrom);
        $to   = FileUtils::getFileContent($fileTo);

        $diff = new Differ(new StrictUnifiedDiffOutputBuilder(['fromFile' => 'Original', 'toFile' => 'New']))->diff($from, $to);

        if ('' === $diff && $from === $to) {
            // odd case: test after executing as it is more efficient than to read the files and check the contents every time
            $this->addToAssertionCount(1);

            return;
        }

        $this->doIntegrationTestPatch($diff, $from, $to);
    }

    /**
     * @param null|array<string, mixed> $options
     */
    #[DataProvider('provideBasicDiffGeneration')]
    #[DataProvider('provideOutputBuildingCases')]
    #[DataProvider('provideSample')]
    public function testIntegrationOfUnitTestCasesGitApply(string $expected, string $from, string $to, ?array $options = null): void
    {
        $this->doIntegrationTestGitApply($expected, $from);
    }

    /**
     * @param null|array<string, mixed> $options
     */
    #[DataProvider('provideBasicDiffGeneration')]
    #[DataProvider('provideOutputBuildingCases')]
    #[DataProvider('provideSample')]
    public function testIntegrationOfUnitTestCasesPatch(string $expected, string $from, string $to, ?array $options = null): void
    {
        $this->doIntegrationTestPatch($expected, $from, $to);
    }

    #[DataProvider('provideBasicDiffGeneration')]
    public function testIntegrationDiffOutputBuilderVersusDiffCommand(string $diff, string $from, string $to): void
    {
        $this->assertNotSame('', $diff);
        $this->assertValidUnifiedDiffFormat($diff);

        $this->assertNotFalse(file_put_contents($this->fileFrom, $from));
        $this->assertNotFalse(file_put_contents($this->fileTo, $to));

        $p = Process::fromShellCommandline('diff -u $from $to');
        $p->run(
            null,
            [
                'from' => $this->fileFrom,
                'to'   => $this->fileTo,
            ],
        );

        $this->assertSame(1, $p->getExitCode()); // note: Process assumes exit code 0 for `isSuccessful`, however `diff` uses the exit code `1` for success with diff

        $output = $p->getOutput();

        $diff   = self::setDiffFileHeader($diff, $this->fileFrom);
        $output = self::setDiffFileHeader($output, $this->fileFrom);

        $this->assertSame($diff, $output);
    }

    private function doIntegrationTestGitApply(string $diff, string $from): void
    {
        $this->assertNotSame('', $diff);
        $this->assertValidUnifiedDiffFormat($diff);

        $diff = self::setDiffFileHeader($diff, $this->fileFrom);

        $this->assertNotFalse(file_put_contents($this->fileFrom, $from));
        $this->assertNotFalse(file_put_contents($this->filePatch, $diff));

        $p = Process::fromShellCommandline('git --git-dir $dir apply --check -v --unsafe-paths --ignore-whitespace $patch');
        $p->run(
            null,
            [
                'dir'   => $this->dir,
                'patch' => $this->filePatch,
            ],
        );

        $this->assertProcessSuccessful($p);
    }

    private function doIntegrationTestPatch(string $diff, string $from, string $to): void
    {
        $this->assertNotSame('', $diff);
        $this->assertValidUnifiedDiffFormat($diff);

        $diff = self::setDiffFileHeader($diff, $this->fileFrom);

        $this->assertNotFalse(file_put_contents($this->fileFrom, $from));
        $this->assertNotFalse(file_put_contents($this->filePatch, $diff));

        $p = Process::fromShellCommandline('patch -u --verbose --posix $from < $patch');
        $p->run(
            null,
            [
                'from'  => $this->fileFrom,
                'patch' => $this->filePatch,
            ],
        );

        $this->assertProcessSuccessful($p);

        $this->assertStringEqualsFile(
            $this->fileFrom,
            $to,
            sprintf('Patch command "%s".', $p->getCommandLine()),
        );
    }

    private function assertProcessSuccessful(Process $p): void
    {
        $this->assertTrue(
            $p->isSuccessful(),
            /** @phpstan-ignore argument.type */
            sprintf(
                "Command exec. was not successful:\n\"%s\"\nOutput:\n\"%s\"\nStdErr:\n\"%s\"\nExit code %d.\n",
                $p->getCommandLine(),
                $p->getOutput(),
                $p->getErrorOutput(),
                $p->getExitCode(),
            ),
        );
    }

    private function cleanUpTempFiles(): void
    {
        @unlink($this->fileFrom . '.orig');
        @unlink($this->fileFrom . '.rej');
        @unlink($this->fileFrom);
        @unlink($this->fileTo);
        @unlink($this->filePatch);
    }

    private static function setDiffFileHeader(string $diff, string $file): string
    {
        $diffLines = preg_split('/(.*\R)/', $diff, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($diffLines === false || !isset($diffLines[0], $diffLines[1])) {
            return $diff;
        }

        $diffLines[0] = preg_replace('#^\-\-\- .*#', '--- /' . $file, $diffLines[0], 1) ?? $diffLines[0];
        $diffLines[1] = preg_replace('#^\+\+\+ .*#', '+++ /' . $file, $diffLines[1], 1) ?? $diffLines[1];

        return implode('', $diffLines);
    }
}
