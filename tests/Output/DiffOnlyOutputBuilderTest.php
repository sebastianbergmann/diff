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

#[CoversClass(DiffOnlyOutputBuilder::class)]
#[UsesClass(Differ::class)]
#[UsesClass(TimeEfficientLongestCommonSubsequenceCalculator::class)]
final class DiffOnlyOutputBuilderTest extends TestCase
{
    public static function textForNoNonDiffLinesProvider(): array
    {
        return [
            [
                " #Warning: Strings contain different line endings!\n-A\r\n+B\n",
                "A\r\n",
                "B\n",
            ],
            [
                "-A\n+B\n",
                "\nA",
                "\nB",
            ],
            [
                '',
                'a',
                'a',
            ],
            [
                "-A\n+C\n",
                "A\n\n\nB",
                "C\n\n\nB",
            ],
            [
                "header\n",
                'a',
                'a',
                'header',
            ],
            [
                "header\n",
                'a',
                'a',
                "header\n",
            ],
        ];
    }

    #[DataProvider('textForNoNonDiffLinesProvider')]
    public function testDiffDoNotShowNonDiffLines(string $expected, string $from, string $to, string $header = ''): void
    {
        $differ = new Differ(new DiffOnlyOutputBuilder($header));

        $this->assertSame($expected, $differ->diff($from, $to));
    }
}
