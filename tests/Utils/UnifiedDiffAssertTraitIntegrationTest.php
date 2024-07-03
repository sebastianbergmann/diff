<?php declare(strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Diff\Utils;

use function file_exists;
use function realpath;
use function sprintf;
use function strlen;
use function substr;
use function unlink;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Process\Process;

#[CoversNothing]
#[RequiresOperatingSystem('Linux')]
final class UnifiedDiffAssertTraitIntegrationTest extends TestCase
{
    use UnifiedDiffAssertTrait;
    private string $filePatch;

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideFilePairsCases(): iterable
    {
        // created cases based on dedicated fixtures
        $dir = realpath(__DIR__ . '/../fixtures/UnifiedDiffAssertTraitIntegrationTest');
        Assert::assertIsString($dir);
        $dirLength = strlen($dir);

        for ($i = 1; ; $i++) {
            $fromFile = sprintf('%s/%d_a.txt', $dir, $i);
            $toFile   = sprintf('%s/%d_b.txt', $dir, $i);

            if (!file_exists($fromFile)) {
                break;
            }

            Assert::assertFileExists($toFile);

            yield sprintf("Diff file:\n\"%s\"\nvs.\n\"%s\"\n", substr(realpath($fromFile), $dirLength), substr(realpath($toFile), $dirLength)) => [$fromFile, $toFile];
        }

        // create cases based on PHP files within the vendor directory for integration testing
        $dir = realpath(__DIR__ . '/../../vendor');
        Assert::assertIsString($dir);
        $dirLength = strlen($dir);

        $fileIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        $fromFile     = __FILE__;

        /** @var SplFileInfo $file */
        foreach ($fileIterator as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }

            $toFile = $file->getPathname();

            yield sprintf("Diff file:\n\"%s\"\nvs.\n\"%s\"\n", substr(realpath($fromFile), $dirLength), substr(realpath($toFile), $dirLength)) => [$fromFile, $toFile];
            $fromFile = $toFile;
        }
    }

    protected function setUp(): void
    {
        $this->filePatch = __DIR__ . '/../fixtures/out/patch.txt';

        $this->cleanUpTempFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanUpTempFiles();
    }

    #[DataProvider('provideFilePairsCases')]
    public function testValidPatches(string $fileFrom, string $fileTo): void
    {
        $p = Process::fromShellCommandline('diff -u $from $to > $patch');
        $p->run(
            null,
            [
                'from'  => realpath($fileFrom),
                'to'    => realpath($fileTo),
                'patch' => $this->filePatch,
            ],
        );

        $exitCode = $p->getExitCode();

        if (0 === $exitCode) {
            // odd case when two files have the same content. Test after executing as it is more efficient than to read the files and check the contents every time.
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertSame(
            1, // means `diff` found a diff between the files we gave it
            $exitCode,
            sprintf(
                "Command exec. was not successful:\n\"%s\"\nOutput:\n\"%s\"\nStdErr:\n\"%s\"\nExit code %d.\n",
                $p->getCommandLine(),
                $p->getOutput(),
                $p->getErrorOutput(),
                $p->getExitCode(),
            ),
        );

        $this->assertValidUnifiedDiffFormat(FileUtils::getFileContent($this->filePatch));
    }

    private function cleanUpTempFiles(): void
    {
        @unlink($this->filePatch);
    }
}
