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

use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;

/**
 * @covers \SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder
 *
 * @uses \SebastianBergmann\Diff\Differ
 * @uses \SebastianBergmann\Diff\Output\AbstractChunkOutputBuilder
 * @uses \SebastianBergmann\Diff\TimeEfficientLongestCommonSubsequenceCalculator
 */
final class UnifiedDiffOutputBuilderTest extends TestCase
{
    /**
     * @dataProvider headerProvider
     */
    public function testCustomHeaderCanBeUsed(string $expected, string $from, string $to, string $header): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder($header));

        $this->assertSame(
            $expected,
            $differ->diff($from, $to)
        );
    }

    public function headerProvider(): array
    {
        return [
            [
                "CUSTOM HEADER\n@@ @@\n-a\n+b\n",
                'a',
                'b',
                'CUSTOM HEADER',
            ],
            [
                "CUSTOM HEADER\n@@ @@\n-a\n+b\n",
                'a',
                'b',
                "CUSTOM HEADER\n",
            ],
            [
                "CUSTOM HEADER\n\n@@ @@\n-a\n+b\n",
                'a',
                'b',
                "CUSTOM HEADER\n\n",
            ],
            [
                "@@ @@\n-a\n+b\n",
                'a',
                'b',
                '',
            ],
        ];
    }

    /**
     * @dataProvider provideDiffWithLineNumbers
     */
    public function testDiffWithLineNumbers(string $expected, string $from, string $to): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder("--- Original\n+++ New\n", true));
        $this->assertSame($expected, $differ->diff($from, $to));
    }

    public function provideDiffWithLineNumbers(): array
    {
        return UnifiedDiffOutputBuilderDataProvider::provideDiffWithLineNumbers();
    }

    /**
     * @dataProvider provideStringsThatAreTheSame
     */
    public function testEmptyDiffProducesEmptyOutput(string $from, string $to): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder('', false));

        $output = $differ->diff($from, $to);

        $this->assertEmpty($output);
    }

    public function provideStringsThatAreTheSame(): array
    {
        return [
            ['', ''],
            ['a', 'a'],
            ['these strings are the same', 'these strings are the same'],
            ["\n", "\n"],
            ["multi-line strings\nare the same", "multi-line strings\nare the same"],
        ];
    }
}
