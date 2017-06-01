<?php
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

/**
 * @covers SebastianBergmann\Diff\Differ
 *
 * @uses SebastianBergmann\Diff\MemoryEfficientLongestCommonSubsequenceCalculator
 * @uses SebastianBergmann\Diff\TimeEfficientLongestCommonSubsequenceCalculator
 * @uses SebastianBergmann\Diff\Chunk
 * @uses SebastianBergmann\Diff\Diff
 * @uses SebastianBergmann\Diff\Line
 * @uses SebastianBergmann\Diff\Parser
 */
class DifferTest extends TestCase
{
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
        $this->assertEquals($expected, $this->differ->diffToArray($from, $to, new TimeEfficientLongestCommonSubsequenceCalculator));
    }

    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @dataProvider textProvider
     */
    public function testTextRepresentationOfDiffCanBeRenderedUsingTimeEfficientLcsImplementation($expected, $from, $to)
    {
        $this->assertEquals($expected, $this->differ->diff($from, $to, new TimeEfficientLongestCommonSubsequenceCalculator));
    }

    /**
     * @param array        $expected
     * @param string|array $from
     * @param string|array $to
     * @dataProvider arrayProvider
     */
    public function testArrayRepresentationOfDiffCanBeRenderedUsingMemoryEfficientLcsImplementation(array $expected, $from, $to)
    {
        $this->assertEquals($expected, $this->differ->diffToArray($from, $to, new MemoryEfficientLongestCommonSubsequenceCalculator));
    }

    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @dataProvider textProvider
     */
    public function testTextRepresentationOfDiffCanBeRenderedUsingMemoryEfficientLcsImplementation($expected, $from, $to)
    {
        $this->assertEquals($expected, $this->differ->diff($from, $to, new MemoryEfficientLongestCommonSubsequenceCalculator));
    }

    public function testCustomHeaderCanBeUsed()
    {
        $differ = new Differ('CUSTOM HEADER');

        $this->assertEquals(
            "CUSTOM HEADER@@ @@\n-a\n+b\n",
            $differ->diff('a', 'b')
        );
    }

    public function testTypesOtherThanArrayAndStringCanBePassed()
    {
        $this->assertEquals(
            "--- Original\n+++ New\n@@ @@\n-1\n+2\n",
            $this->differ->diff(1, 2)
        );
    }

    /**
     * @param string $diff
     * @param Diff[] $expected
     * @dataProvider diffProvider
     */
    public function testParser($diff, array $expected)
    {
        $parser = new Parser;
        $result = $parser->parse($diff);

        $this->assertEquals($expected, $result);
    }

    public function arrayProvider()
    {
        return [
            [
                [
                    ['a', self::REMOVED],
                    ['b', self::ADDED]
                ],
                'a',
                'b'
            ],
            [
                [
                    ['ba', self::REMOVED],
                    ['bc', self::ADDED]
                ],
                'ba',
                'bc'
            ],
            [
                [
                    ['ab', self::REMOVED],
                    ['cb', self::ADDED]
                ],
                'ab',
                'cb'
            ],
            [
                [
                    ['abc', self::REMOVED],
                    ['adc', self::ADDED]
                ],
                'abc',
                'adc'
            ],
            [
                [
                    ['ab', self::REMOVED],
                    ['abc', self::ADDED]
                ],
                'ab',
                'abc'
            ],
            [
                [
                    ['bc', self::REMOVED],
                    ['abc', self::ADDED]
                ],
                'bc',
                'abc'
            ],
            [
                [
                    ['abc', self::REMOVED],
                    ['abbc', self::ADDED]
                ],
                'abc',
                'abbc'
            ],
            [
                [
                    ['abcdde', self::REMOVED],
                    ['abcde', self::ADDED]
                ],
                'abcdde',
                'abcde'
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
                        '#Warning: Strings contain different line endings!',
                        self::OLD,
                    ],
                    [
                        '<?php',
                        self::OLD,
                    ],
                    [
                        '',
                        self::OLD,
                    ],
                ],
                "<?php\r\n",
                "<?php\n"
            ]
        ];
    }

    public function textProvider()
    {
        return [
            [
                "--- Original\n+++ New\n@@ @@\n-a\n+b\n",
                'a',
                'b'
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-ba\n+bc\n",
                'ba',
                'bc'
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-ab\n+cb\n",
                'ab',
                'cb'
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-abc\n+adc\n",
                'abc',
                'adc'
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-ab\n+abc\n",
                'ab',
                'abc'
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-bc\n+abc\n",
                'bc',
                'abc'
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-abc\n+abbc\n",
                'abc',
                'abbc'
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-abcdde\n+abcde\n",
                'abcdde',
                'abcde'
            ],
            [
                "--- Original\n+++ New\n@@ @@\n-A\n+A1\n B\n",
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
 i
-j
+w
 k

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
 a
-b
+p
@@ @@
 i
-j
+w
 k

EOF
                ,
                "a\nb\nc\nd\ne\nf\ng\nh\ni\nj\nk",
                "a\np\nc\nd\ne\nf\ng\nh\ni\nw\nk",
            ],
        ];
    }

    public function diffProvider()
    {
        $serialized_arr = <<<EOL
a:1:{i:0;O:27:"SebastianBergmann\Diff\Diff":3:{s:33:" SebastianBergmann\Diff\Diff from";s:7:"old.txt";s:31:" SebastianBergmann\Diff\Diff to";s:7:"new.txt";s:35:" SebastianBergmann\Diff\Diff chunks";a:3:{i:0;O:28:"SebastianBergmann\Diff\Chunk":5:{s:35:" SebastianBergmann\Diff\Chunk start";i:1;s:40:" SebastianBergmann\Diff\Chunk startRange";i:3;s:33:" SebastianBergmann\Diff\Chunk end";i:1;s:38:" SebastianBergmann\Diff\Chunk endRange";i:4;s:35:" SebastianBergmann\Diff\Chunk lines";a:4:{i:0;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:1;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222111";}i:1;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"1111111";}i:2;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"1111111";}i:3;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"1111111";}}}i:1;O:28:"SebastianBergmann\Diff\Chunk":5:{s:35:" SebastianBergmann\Diff\Chunk start";i:5;s:40:" SebastianBergmann\Diff\Chunk startRange";i:10;s:33:" SebastianBergmann\Diff\Chunk end";i:6;s:38:" SebastianBergmann\Diff\Chunk endRange";i:8;s:35:" SebastianBergmann\Diff\Chunk lines";a:11:{i:0;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"1111111";}i:1;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"1111111";}i:2;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"1111111";}i:3;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:8:"+1121211";}i:4;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"1111111";}i:5;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:8:"-1111111";}i:6;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:8:"-1111111";}i:7;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:8:"-2222222";}i:8;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222222";}i:9;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222222";}i:10;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222222";}}}i:2;O:28:"SebastianBergmann\Diff\Chunk":5:{s:35:" SebastianBergmann\Diff\Chunk start";i:17;s:40:" SebastianBergmann\Diff\Chunk startRange";i:5;s:33:" SebastianBergmann\Diff\Chunk end";i:16;s:38:" SebastianBergmann\Diff\Chunk endRange";i:6;s:35:" SebastianBergmann\Diff\Chunk lines";a:6:{i:0;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222222";}i:1;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222222";}i:2;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222222";}i:3;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:8:"+2122212";}i:4;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222222";}i:5;O:27:"SebastianBergmann\Diff\Line":2:{s:33:" SebastianBergmann\Diff\Line type";i:3;s:36:" SebastianBergmann\Diff\Line content";s:7:"2222222";}}}}}}
EOL;

        return [
            [
                "--- old.txt	2014-11-04 08:51:02.661868729 +0300\n+++ new.txt	2014-11-04 08:51:02.665868730 +0300\n@@ -1,3 +1,4 @@\n+2222111\n 1111111\n 1111111\n 1111111\n@@ -5,10 +6,8 @@\n 1111111\n 1111111\n 1111111\n +1121211\n 1111111\n -1111111\n -1111111\n -2222222\n 2222222\n 2222222\n 2222222\n@@ -17,5 +16,6 @@\n 2222222\n 2222222\n 2222222\n +2122212\n 2222222\n 2222222\n",
                \unserialize($serialized_arr)
            ]
        ];
    }

    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @dataProvider textForNoNonDiffLinesProvider
     */
    public function testDiffDoNotShowNonDiffLines($expected, $from, $to)
    {
        $differ = new Differ('', false);
        $this->assertSame($expected, $differ->diff($from, $to));
    }

    public function textForNoNonDiffLinesProvider()
    {
        return [
            [
                '', 'a', 'a'
            ],
            [
                "-A\n+C\n",
                "A\n\n\nB",
                "C\n\n\nB",
            ],
        ];
    }

    /**
     * @requires PHPUnit 5.7
     */
    public function testDiffToArrayInvalidFromType()
    {
        $differ = new Differ;

        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessageRegExp('#^"from" must be an array or string\.$#');

        $differ->diffToArray(null, '');
    }

    /**
     * @requires PHPUnit 5.7
     */
    public function testDiffInvalidToType()
    {
        $differ = new Differ;

        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessageRegExp('#^"to" must be an array or string\.$#');

        $differ->diffToArray('', new \stdClass);
    }

    public function testSettingFluent()
    {
        $diff = $this->differ->setHeader('testSettingFluent');
        $this->assertSame($this->differ, $diff);

        $reflection = new \ReflectionObject($this->differ);

        $reflectionProperty = $reflection->getProperty('header');
        $reflectionProperty->setAccessible(true);
        $this->assertSame('testSettingFluent', $reflectionProperty->getValue($this->differ));
    }
}
