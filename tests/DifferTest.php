<?php declare(strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use SebastianBergmann\Diff\Output\AbstractChunkOutputBuilder;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

#[CoversClass(Differ::class)]
#[CoversClass(UnifiedDiffOutputBuilder::class)]
#[UsesClass(MemoryEfficientLongestCommonSubsequenceCalculator::class)]
#[UsesClass(TimeEfficientLongestCommonSubsequenceCalculator::class)]
#[UsesClass(AbstractChunkOutputBuilder::class)]
#[Small]
final class DifferTest extends TestCase
{
    private Differ $differ;

    public static function arrayProvider(): array
    {
        return [
            [
                [
                    ['a', Differ::REMOVED],
                    ['b', Differ::ADDED],
                ],
                'a',
                'b',
            ],
            [
                [
                    ['ba', Differ::REMOVED],
                    ['bc', Differ::ADDED],
                ],
                'ba',
                'bc',
            ],
            [
                [
                    ['ab', Differ::REMOVED],
                    ['cb', Differ::ADDED],
                ],
                'ab',
                'cb',
            ],
            [
                [
                    ['abc', Differ::REMOVED],
                    ['adc', Differ::ADDED],
                ],
                'abc',
                'adc',
            ],
            [
                [
                    ['ab', Differ::REMOVED],
                    ['abc', Differ::ADDED],
                ],
                'ab',
                'abc',
            ],
            [
                [
                    ['bc', Differ::REMOVED],
                    ['abc', Differ::ADDED],
                ],
                'bc',
                'abc',
            ],
            [
                [
                    ['abc', Differ::REMOVED],
                    ['abbc', Differ::ADDED],
                ],
                'abc',
                'abbc',
            ],
            [
                [
                    ['abcdde', Differ::REMOVED],
                    ['abcde', Differ::ADDED],
                ],
                'abcdde',
                'abcde',
            ],
            'same start' => [
                [
                    [17, Differ::OLD],
                    ['b', Differ::REMOVED],
                    ['d', Differ::ADDED],
                ],
                [30 => 17, 'a' => 'b'],
                [30 => 17, 'c' => 'd'],
            ],
            'same end' => [
                [
                    [1, Differ::REMOVED],
                    [2, Differ::ADDED],
                    ['b', Differ::OLD],
                ],
                [1 => 1, 'a' => 'b'],
                [1 => 2, 'a' => 'b'],
            ],
            'same start (2), same end (1)' => [
                [
                    [17, Differ::OLD],
                    [2, Differ::OLD],
                    [4, Differ::REMOVED],
                    ['a', Differ::ADDED],
                    [5, Differ::ADDED],
                    ['x', Differ::OLD],
                ],
                [30 => 17, 1 => 2, 2 => 4, 'z' => 'x'],
                [30 => 17, 1 => 2, 3 => 'a', 2 => 5, 'z' => 'x'],
            ],
            'same' => [
                [
                    ['x', Differ::OLD],
                ],
                ['z' => 'x'],
                ['z' => 'x'],
            ],
            'diff' => [
                [
                    ['y', Differ::REMOVED],
                    ['x', Differ::ADDED],
                ],
                ['x' => 'y'],
                ['z' => 'x'],
            ],
            'diff 2' => [
                [
                    ['y', Differ::REMOVED],
                    ['b', Differ::REMOVED],
                    ['x', Differ::ADDED],
                    ['d', Differ::ADDED],
                ],
                ['x' => 'y', 'a' => 'b'],
                ['z' => 'x', 'c' => 'd'],
            ],
            'test line diff detection' => [
                [
                    [
                        "#Warning: Strings contain different line endings!\n",
                        Differ::DIFF_LINE_END_WARNING,
                    ],
                    [
                        "<?php\r\n",
                        Differ::REMOVED,
                    ],
                    [
                        "<?php\n",
                        Differ::ADDED,
                    ],
                ],
                "<?php\r\n",
                "<?php\n",
            ],
            'test line diff detection in array input' => [
                [
                    [
                        "#Warning: Strings contain different line endings!\n",
                        Differ::DIFF_LINE_END_WARNING,
                    ],
                    [
                        "<?php\r\n",
                        Differ::REMOVED,
                    ],
                    [
                        "<?php\n",
                        Differ::ADDED,
                    ],
                ],
                ["<?php\r\n"],
                ["<?php\n"],
            ],
        ];
    }

    public static function textProvider(): array
    {
        return [
            [
                "--- Original\n+++ New\n@@ @@\n-a\n+b\n",
                'a',
                'b',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-A\n+A1\n B\n",
                "A\nB",
                "A1\nB",
            ],
            [
                <<<'EOF'
--- Original
+++ New
@@ @@
 a
-b
+p
 c
 d
 e
@@ @@
 g
 h
 i
-j
+w
 k

EOF
                ,
                "a\nb\nc\nd\ne\nf\ng\nh\ni\nj\nk\n",
                "a\np\nc\nd\ne\nf\ng\nh\ni\nw\nk\n",
            ],
            [
                <<<'EOF'
--- Original
+++ New
@@ @@
-A
+B
 1
 2
 3

EOF
                ,
                "A\n1\n2\n3\n4\n5\n6\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n",
                "B\n1\n2\n3\n4\n5\n6\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n",
            ],
            [
                "--- Original\n+++ New\n@@ @@\n #Warning: Strings contain different line endings!\n-<?php\r\n+<?php\n A\n",
                "<?php\r\nA\n",
                "<?php\nA\n",
            ],
            [
                "--- Original\n+++ New\n@@ @@\n #Warning: Strings contain different line endings!\n-a\r\n+\n+c\r\n",
                "a\r\n",
                "\nc\r",
            ],
        ];
    }

    public static function provideSplitStringByLinesCases(): array
    {
        return [
            [
                [],
                '',
            ],
            [
                ['a'],
                'a',
            ],
            [
                ["a\n"],
                "a\n",
            ],
            [
                ["a\r"],
                "a\r",
            ],
            [
                ["a\r\n"],
                "a\r\n",
            ],
            [
                ["\n"],
                "\n",
            ],
            [
                ["\r"],
                "\r",
            ],
            [
                ["\r\n"],
                "\r\n",
            ],
            [
                [
                    "A\n",
                    "B\n",
                    "\n",
                    "C\n",
                ],
                "A\nB\n\nC\n",
            ],
            [
                [
                    "A\r\n",
                    "B\n",
                    "\n",
                    "C\r",
                ],
                "A\r\nB\n\nC\r",
            ],
            [
                [
                    "\n",
                    "A\r\n",
                    "B\n",
                    "\n",
                    'C',
                ],
                "\nA\r\nB\n\nC",
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->differ = new Differ(new UnifiedDiffOutputBuilder);
    }

    #[DataProvider('arrayProvider')]
    public function testArrayRepresentationOfDiffCanBeRenderedUsingTimeEfficientLcsImplementation(array $expected, array|string $from, array|string $to): void
    {
        $this->assertSame($expected, $this->differ->diffToArray($from, $to, new TimeEfficientLongestCommonSubsequenceCalculator));
    }

    #[DataProvider('textProvider')]
    public function testTextRepresentationOfDiffCanBeRenderedUsingTimeEfficientLcsImplementation(string $expected, string $from, string $to): void
    {
        $this->assertSame($expected, $this->differ->diff($from, $to, new TimeEfficientLongestCommonSubsequenceCalculator));
    }

    #[DataProvider('arrayProvider')]
    public function testArrayRepresentationOfDiffCanBeRenderedUsingMemoryEfficientLcsImplementation(array $expected, array|string $from, array|string $to): void
    {
        $this->assertSame($expected, $this->differ->diffToArray($from, $to, new MemoryEfficientLongestCommonSubsequenceCalculator));
    }

    #[DataProvider('textProvider')]
    public function testTextRepresentationOfDiffCanBeRenderedUsingMemoryEfficientLcsImplementation(string $expected, string $from, string $to): void
    {
        $this->assertSame($expected, $this->differ->diff($from, $to, new MemoryEfficientLongestCommonSubsequenceCalculator));
    }

    public function testArrayDiffs(): void
    {
        $this->assertSame(
            '--- Original
+++ New
@@ @@
-one
+two
',
            $this->differ->diff(['one'], ['two']),
        );
    }

    #[DataProvider('provideSplitStringByLinesCases')]
    public function testSplitStringByLines(array $expected, string $input): void
    {
        $reflection = new ReflectionObject($this->differ);
        $method     = $reflection->getMethod('splitStringByLines');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($this->differ, $input));
    }
}
