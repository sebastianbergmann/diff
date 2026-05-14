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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\MyersDiff;

#[CoversClass(UnifiedDiffOutputBuilder::class)]
#[UsesClass(Differ::class)]
#[UsesClass(AbstractChunkOutputBuilder::class)]
#[UsesClass(MyersDiff::class)]
final class UnifiedDiffOutputBuilderTest extends TestCase
{
    /**
     * @return array<
     *     string[],
     * >
     */
    public static function headerProvider(): array
    {
        return [
            [
                "CUSTOM HEADER\n@@ @@\n-a\n\\ No newline at end of file\n+b\n\\ No newline at end of file\n",
                'a',
                'b',
                'CUSTOM HEADER',
            ],
            [
                "CUSTOM HEADER\n@@ @@\n-a\n\\ No newline at end of file\n+b\n\\ No newline at end of file\n",
                'a',
                'b',
                "CUSTOM HEADER\n",
            ],
            [
                "CUSTOM HEADER\n\n@@ @@\n-a\n\\ No newline at end of file\n+b\n\\ No newline at end of file\n",
                'a',
                'b',
                "CUSTOM HEADER\n\n",
            ],
            [
                "@@ @@\n-a\n\\ No newline at end of file\n+b\n\\ No newline at end of file\n",
                'a',
                'b',
                '',
            ],
        ];
    }

    /**
     * @return array{
     *     string?: array{
     *         0: string,
     *         1: string,
     *         2: string,
     *     },
     * }
     */
    public static function provideDiffWithLineNumbers(): array
    {
        return UnifiedDiffOutputBuilderDataProvider::provideDiffWithLineNumbers();
    }

    /**
     * @return array<
     *     array{0: string, 1: string}
     * >
     */
    public static function provideStringsThatAreTheSame(): array
    {
        return [
            ['', ''],
            ['a', 'a'],
            ['these strings are the same', 'these strings are the same'],
            ["\n", "\n"],
            ["multi-line strings\nare the same", "multi-line strings\nare the same"],
        ];
    }

    #[DataProvider('headerProvider')]
    public function testCustomHeaderCanBeUsed(string $expected, string $from, string $to, string $header): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder($header));

        $this->assertSame(
            $expected,
            $differ->diff($from, $to),
        );
    }

    #[DataProvider('provideDiffWithLineNumbers')]
    public function testDiffWithLineNumbers(string $expected, string $from, string $to): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder("--- Original\n+++ New\n", true));

        $this->assertSame($expected, $differ->diff($from, $to));
    }

    #[DataProvider('provideStringsThatAreTheSame')]
    public function testEmptyDiffProducesEmptyOutput(string $from, string $to): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder('', false));

        $output = $differ->diff($from, $to);

        $this->assertEmpty($output);
    }

    public function testCustomContextLinesCanBeUsed(): void
    {
        $from = "line1\nline2\nline3\nline4\nline5\nline6\nline7\nline8\nline9\nline10\n";
        $to   = "line1\nline2\nline3\nline4\nLINE5\nline6\nline7\nline8\nline9\nline10\n";

        $differ3 = new Differ(new UnifiedDiffOutputBuilder('', false, 3));
        $differ1 = new Differ(new UnifiedDiffOutputBuilder('', false, 1));
        $differ5 = new Differ(new UnifiedDiffOutputBuilder('', false, 5));

        $diff3 = $differ3->diff($from, $to);
        $diff1 = $differ1->diff($from, $to);
        $diff5 = $differ5->diff($from, $to);

        $this->assertStringContainsString(' line2', $diff3);
        $this->assertStringNotContainsString(' line1', $diff3);

        $this->assertStringContainsString(' line4', $diff1);
        $this->assertStringNotContainsString(' line3', $diff1);
        $this->assertStringNotContainsString(' line7', $diff1);

        $this->assertStringContainsString(' line1', $diff5);
        $this->assertStringContainsString(' line9', $diff5);
    }

    public function testIdenticalInputsProduceEmptyOutputEvenWithHeader(): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder("--- Original\n+++ New\n"));

        $this->assertSame('', $differ->diff("foo\n", "foo\n"));
    }

    public function testNoLineEndEofWarningCanBeSuppressed(): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder('', false, 3, false));

        $this->assertSame(
            "@@ @@\n-a\n+b\n",
            $differ->diff('a', 'b'),
        );
    }

    public function testNoLineEndEofWarningIsRenderedWithItsActualContent(): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder('', false));

        $this->assertSame(
            "@@ @@\n-a\n\\ No newline at end of file\n+b\n\\ No newline at end of file\n",
            $differ->diff('a', 'b'),
        );
    }

    public function testUnknownDiffEntryTypesAreSilentlySkipped(): void
    {
        $builder = new UnifiedDiffOutputBuilder('', false);

        $diff = [
            ["a\n", Differ::REMOVED],
            ['unknown', 99],
            ["b\n", Differ::ADDED],
        ];

        $this->assertSame(
            "@@ @@\n-a\n+b\n",
            $builder->getDiff($diff),
        );
    }
}
