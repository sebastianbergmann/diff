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

use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * @covers SebastianBergmann\Diff\Differ
 * @covers SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder
 *
 * @uses SebastianBergmann\Diff\MemoryEfficientLongestCommonSubsequenceCalculator
 * @uses SebastianBergmann\Diff\TimeEfficientLongestCommonSubsequenceCalculator
 * @uses SebastianBergmann\Diff\Output\AbstractChunkOutputBuilder
 */
final class DifferTest extends TestCase
{
    const WARNING = 3;
    const REMOVED = 2;
    const ADDED   = 1;
    const OLD     = 0;

    /**
     * @var Differ
     */
    private $differ;

    protected function setUp()
    {
        $this->differ = new Differ;
    }

    /**
     * @param array        $expected
     * @param string|array $from
     * @param string|array $to
     * @dataProvider arrayProvider
     */
    public function testArrayRepresentationOfDiffCanBeRenderedUsingTimeEfficientLcsImplementation(array $expected, $from, $to)
    {
        $this->assertSame($expected, $this->differ->diffToArray($from, $to, new TimeEfficientLongestCommonSubsequenceCalculator));
    }

    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @dataProvider textProvider
     */
    public function testTextRepresentationOfDiffCanBeRenderedUsingTimeEfficientLcsImplementation(string $expected, string $from, string $to)
    {
        $this->assertSame($expected, $this->differ->diff($from, $to, new TimeEfficientLongestCommonSubsequenceCalculator));
    }

    /**
     * @param array        $expected
     * @param string|array $from
     * @param string|array $to
     * @dataProvider arrayProvider
     */
    public function testArrayRepresentationOfDiffCanBeRenderedUsingMemoryEfficientLcsImplementation(array $expected, $from, $to)
    {
        $this->assertSame($expected, $this->differ->diffToArray($from, $to, new MemoryEfficientLongestCommonSubsequenceCalculator));
    }

    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @dataProvider textProvider
     */
    public function testTextRepresentationOfDiffCanBeRenderedUsingMemoryEfficientLcsImplementation(string $expected, string $from, string $to)
    {
        $this->assertSame($expected, $this->differ->diff($from, $to, new MemoryEfficientLongestCommonSubsequenceCalculator));
    }

    public function testTypesOtherThanArrayAndStringCanBePassed()
    {
        $this->assertSame(
            "--- Original\n+++ New\n@@ @@\n-1\n+2\n",
            $this->differ->diff(1, 2)
        );
    }

    public function arrayProvider(): array
    {
        return [
            [
                [
                    ['a', self::REMOVED],
                    ['b', self::ADDED],
                ],
                'a',
                'b',
            ],
            [
                [
                    ['ba', self::REMOVED],
                    ['bc', self::ADDED],
                ],
                'ba',
                'bc',
            ],
            [
                [
                    ['ab', self::REMOVED],
                    ['cb', self::ADDED],
                ],
                'ab',
                'cb',
            ],
            [
                [
                    ['abc', self::REMOVED],
                    ['adc', self::ADDED],
                ],
                'abc',
                'adc',
            ],
            [
                [
                    ['ab', self::REMOVED],
                    ['abc', self::ADDED],
                ],
                'ab',
                'abc',
            ],
            [
                [
                    ['bc', self::REMOVED],
                    ['abc', self::ADDED],
                ],
                'bc',
                'abc',
            ],
            [
                [
                    ['abc', self::REMOVED],
                    ['abbc', self::ADDED],
                ],
                'abc',
                'abbc',
            ],
            [
                [
                    ['abcdde', self::REMOVED],
                    ['abcde', self::ADDED],
                ],
                'abcdde',
                'abcde',
            ],
            'same start' => [
                [
                    [17, self::OLD],
                    ['b', self::REMOVED],
                    ['d', self::ADDED],
                ],
                [30 => 17, 'a' => 'b'],
                [30 => 17, 'c' => 'd'],
            ],
            'same end' => [
                [
                    [1, self::REMOVED],
                    [2, self::ADDED],
                    ['b', self::OLD],
                ],
                [1 => 1, 'a' => 'b'],
                [1 => 2, 'a' => 'b'],
            ],
            'same start (2), same end (1)' => [
                [
                    [17, self::OLD],
                    [2, self::OLD],
                    [4, self::REMOVED],
                    ['a', self::ADDED],
                    [5, self::ADDED],
                    ['x', self::OLD],
                ],
                [30 => 17, 1 => 2, 2 => 4, 'z' => 'x'],
                [30 => 17, 1 => 2, 3 => 'a', 2 => 5, 'z' => 'x'],
            ],
            'same' => [
                [
                    ['x', self::OLD],
                ],
                ['z' => 'x'],
                ['z' => 'x'],
            ],
            'diff' => [
                [
                    ['y', self::REMOVED],
                    ['x', self::ADDED],
                ],
                ['x' => 'y'],
                ['z' => 'x'],
            ],
            'diff 2' => [
                [
                    ['y', self::REMOVED],
                    ['b', self::REMOVED],
                    ['x', self::ADDED],
                    ['d', self::ADDED],
                ],
                ['x' => 'y', 'a' => 'b'],
                ['z' => 'x', 'c' => 'd'],
            ],
            'test line diff detection' => [
                [
                    [
                        "#Warning: Strings contain different line endings!\n",
                        self::WARNING,
                    ],
                    [
                        "<?php\r\n",
                        self::REMOVED,
                    ],
                    [
                        "<?php\n",
                        self::ADDED,
                    ],
                ],
                "<?php\r\n",
                "<?php\n",
            ],
            'test line diff detection in array input' => [
                [
                    [
                        "#Warning: Strings contain different line endings!\n",
                        self::WARNING,
                    ],
                    [
                        "<?php\r\n",
                        self::REMOVED,
                    ],
                    [
                        "<?php\n",
                        self::ADDED,
                    ],
                ],
                ["<?php\r\n"],
                ["<?php\n"],
            ],
        ];
    }

    public function textProvider(): array
    {
        return [
            [
                "--- Original\n+++ New\n@@ @@\n-a\n+b\n",
                'a',
                'b',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-ba\n+bc\n",
                'ba',
                'bc',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-ab\n+cb\n",
                'ab',
                'cb',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-abc\n+adc\n",
                'abc',
                'adc',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-ab\n+abc\n",
                'ab',
                'abc',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-bc\n+abc\n",
                'bc',
                'abc',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-abc\n+abbc\n",
                'abc',
                'abbc',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-abcdde\n+abcde\n",
                'abcdde',
                'abcde',
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-A\n+A1\n",
                "A\nB",
                "A1\nB",
            ],
            [
                <<<EOF
--- Original
+++ New
@@ @@
 a
-b
+p
@@ @@
-j
+w

EOF
            ,
                "a\nb\nc\nd\ne\nf\ng\nh\ni\nj\nk",
                "a\np\nc\nd\ne\nf\ng\nh\ni\nw\nk",
            ],
            [
                <<<EOF
--- Original
+++ New
@@ @@
-A
+B

EOF
            ,
                "A\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1",
                "B\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1",
            ],
            [
                "--- Original\n+++ New\n@@ @@\n #Warning: Strings contain different line endings!\n-<?php\r\n+<?php\n",
                "<?php\r\nA\n",
                "<?php\nA\n",
            ],
            [
                "--- Original\n+++ New\n@@ @@\n #Warning: Strings contain different line endings!\n-a\r\n+\n+c\r",
                "a\r\n",
                "\nc\r",
            ],
        ];
    }

    public function testDiffToArrayInvalidFromType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^"from" must be an array or string\.$#');

        $this->differ->diffToArray(null, '');
    }

    public function testDiffInvalidToType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#^"to" must be an array or string\.$#');

        $this->differ->diffToArray('', new \stdClass);
    }

    /**
     * @param array  $expected
     * @param string $input
     * @dataProvider provideSplitStringByLinesCases
     */
    public function testSplitStringByLines(array $expected, string $input)
    {
        $reflection = new \ReflectionObject($this->differ);
        $method     = $reflection->getMethod('splitStringByLines');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($this->differ, $input));
    }

    public function provideSplitStringByLinesCases(): array
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

    public function testConstructorNull()
    {
        $this->assertAttributeInstanceOf(
            UnifiedDiffOutputBuilder::class,
            'outputBuilder',
            new Differ(null)
        );
    }

    public function testConstructorString()
    {
        $this->assertAttributeInstanceOf(
            UnifiedDiffOutputBuilder::class,
            'outputBuilder',
            new Differ("--- Original\n+++ New\n")
        );
    }

    public function testConstructorInvalidArgInt()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/^Expected builder to be an instance of DiffOutputBuilderInterface, <null> or a string, got integer "1"\.$/');

        new Differ(1);
    }

    public function testConstructorInvalidArgObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/^Expected builder to be an instance of DiffOutputBuilderInterface, <null> or a string, got instance of "SplFileInfo"\.$/');

        new Differ(new \SplFileInfo(__FILE__));
    }
}
