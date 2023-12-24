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
use SebastianBergmann\Diff\TimeEfficientLongestCommonSubsequenceCalculator;

#[CoversClass(UnifiedDiffOutputBuilder::class)]
#[UsesClass(Differ::class)]
#[UsesClass(AbstractChunkOutputBuilder::class)]
#[UsesClass(TimeEfficientLongestCommonSubsequenceCalculator::class)]
final class UnifiedDiffOutputBuilderTest extends TestCase
{
    public static function headerProvider(): array
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

    public static function provideDiffWithLineNumbers(): array
    {
        return UnifiedDiffOutputBuilderDataProvider::provideDiffWithLineNumbers();
    }

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
}
