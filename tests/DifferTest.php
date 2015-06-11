<?php
/*
 * This file is part of the Diff package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\Diff;

use PHPUnit_Framework_TestCase;
use SebastianBergmann\Diff\LCS\MemoryEfficientImplementation;
use SebastianBergmann\Diff\LCS\TimeEfficientImplementation;

class DifferTest extends PHPUnit_Framework_TestCase
{
    const REMOVED = 2;
    const ADDED = 1;
    const OLD = 0;

    /**
     * @var Differ
     */
    private $differ;

    protected function setUp()
    {
        $this->differ = new Differ;
    }

    /**
     * @param array  $expected
     * @param string $from
     * @param string $to
     * @dataProvider arrayProvider
     * @covers       SebastianBergmann\Diff\Differ::diffToArray
     * @covers       SebastianBergmann\Diff\LCS\TimeEfficientImplementation
     */
    public function testArrayRepresentationOfDiffCanBeRenderedUsingTimeEfficientLcsImplementation(array $expected, $from, $to)
    {
        $this->assertEquals($expected, $this->differ->diffToArray($from, $to, new TimeEfficientImplementation));
    }

    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @dataProvider textProvider
     * @covers       SebastianBergmann\Diff\Differ::diff
     * @covers       SebastianBergmann\Diff\LCS\TimeEfficientImplementation
     */
    public function testTextRepresentationOfDiffCanBeRenderedUsingTimeEfficientLcsImplementation($expected, $from, $to)
    {
        $this->assertEquals($expected, $this->differ->diff($from, $to, new TimeEfficientImplementation));
    }

    /**
     * @param array  $expected
     * @param string $from
     * @param string $to
     * @dataProvider arrayProvider
     * @covers       SebastianBergmann\Diff\Differ::diffToArray
     * @covers       SebastianBergmann\Diff\LCS\MemoryEfficientImplementation
     */
    public function testArrayRepresentationOfDiffCanBeRenderedUsingMemoryEfficientLcsImplementation(array $expected, $from, $to)
    {
        $this->assertEquals($expected, $this->differ->diffToArray($from, $to, new MemoryEfficientImplementation));
    }

    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @dataProvider textProvider
     * @covers       SebastianBergmann\Diff\Differ::diff
     * @covers       SebastianBergmann\Diff\LCS\MemoryEfficientImplementation
     */
    public function testTextRepresentationOfDiffCanBeRenderedUsingMemoryEfficientLcsImplementation($expected, $from, $to)
    {
        $this->assertEquals($expected, $this->differ->diff($from, $to, new MemoryEfficientImplementation));
    }

    /**
     * @covers SebastianBergmann\Diff\Differ::diff
     */
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
	 * @param array  $expected
	 * @dataProvider diffProvider
	 * @covers       SebastianBergmann\Diff\Parser::parse
	 */
	public function testParser($diff, $expected)
	{
		$parser = new Parser;
		$result = $parser->parse($diff);

		$this->assertEquals($expected, $result);
	}

    public function arrayProvider()
    {
        return array(
            array(
                array(
                    array('a', self::REMOVED),
                    array('b', self::ADDED)
                ),
                'a',
                'b'
            ),
            array(
                array(
                    array('ba', self::REMOVED),
                    array('bc', self::ADDED)
                ),
                'ba',
                'bc'
            ),
            array(
                array(
                    array('ab', self::REMOVED),
                    array('cb', self::ADDED)
                ),
                'ab',
                'cb'
            ),
            array(
                array(
                    array('abc', self::REMOVED),
                    array('adc', self::ADDED)
                ),
                'abc',
                'adc'
            ),
            array(
                array(
                    array('ab', self::REMOVED),
                    array('abc', self::ADDED)
                ),
                'ab',
                'abc'
            ),
            array(
                array(
                    array('bc', self::REMOVED),
                    array('abc', self::ADDED)
                ),
                'bc',
                'abc'
            ),
            array(
                array(
                    array('abc', self::REMOVED),
                    array('abbc', self::ADDED)
                ),
                'abc',
                'abbc'
            ),
            array(
                array(
                    array('abcdde', self::REMOVED),
                    array('abcde', self::ADDED)
                ),
                'abcdde',
                'abcde'
            ),
            array(
            	array(
            		array('a', self::REMOVED),
            		array('b', self::ADDED)
            	),
            	array('a'),
            	array('b')
            )
        );
    }

    public function textProvider()
    {
        return array(
            array(
                "--- Original\n+++ New\n@@ @@\n-a\n+b\n",
                'a',
                'b'
            ),
            array(
                "--- Original\n+++ New\n@@ @@\n-ba\n+bc\n",
                'ba',
                'bc'
            ),
            array(
                "--- Original\n+++ New\n@@ @@\n-ab\n+cb\n",
                'ab',
                'cb'
            ),
            array(
                "--- Original\n+++ New\n@@ @@\n-abc\n+adc\n",
                'abc',
                'adc'
            ),
            array(
                "--- Original\n+++ New\n@@ @@\n-ab\n+abc\n",
                'ab',
                'abc'
            ),
            array(
                "--- Original\n+++ New\n@@ @@\n-bc\n+abc\n",
                'bc',
                'abc'
            ),
            array(
                "--- Original\n+++ New\n@@ @@\n-abc\n+abbc\n",
                'abc',
                'abbc'
            ),
            array(
                "--- Original\n+++ New\n@@ @@\n-abcdde\n+abcde\n",
                'abcdde',
                'abcde'
            ),
        );
    }

	public function diffProvider()
	{
		$serialized_arr = <<<EOL
a:1:{i:0;O:27:"SebastianBergmann\Diff\Diff":3:{s:33:"\0SebastianBergmann\Diff\Diff\0from";s:7:"old.txt";s:31:"\0SebastianBergmann\Diff\Diff\0to";s:7:"new.txt";s:35:"\0SebastianBergmann\Diff\Diff\0chunks";a:3:{i:0;O:28:"SebastianBergmann\Diff\Chunk":5:{s:35:"\0SebastianBergmann\Diff\Chunk\0start";i:1;s:40:"\0SebastianBergmann\Diff\Chunk\0startRange";i:3;s:33:"\0SebastianBergmann\Diff\Chunk\0end";i:1;s:38:"\0SebastianBergmann\Diff\Chunk\0endRange";i:4;s:35:"\0SebastianBergmann\Diff\Chunk\0lines";a:4:{i:0;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:1;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222111";}i:1;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"1111111";}i:2;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"1111111";}i:3;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"1111111";}}}i:1;O:28:"SebastianBergmann\Diff\Chunk":5:{s:35:"\0SebastianBergmann\Diff\Chunk\0start";i:5;s:40:"\0SebastianBergmann\Diff\Chunk\0startRange";i:10;s:33:"\0SebastianBergmann\Diff\Chunk\0end";i:6;s:38:"\0SebastianBergmann\Diff\Chunk\0endRange";i:8;s:35:"\0SebastianBergmann\Diff\Chunk\0lines";a:11:{i:0;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"1111111";}i:1;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"1111111";}i:2;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"1111111";}i:3;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:8:"+1121211";}i:4;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"1111111";}i:5;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:8:"-1111111";}i:6;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:8:"-1111111";}i:7;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:8:"-2222222";}i:8;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222222";}i:9;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222222";}i:10;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222222";}}}i:2;O:28:"SebastianBergmann\Diff\Chunk":5:{s:35:"\0SebastianBergmann\Diff\Chunk\0start";i:17;s:40:"\0SebastianBergmann\Diff\Chunk\0startRange";i:5;s:33:"\0SebastianBergmann\Diff\Chunk\0end";i:16;s:38:"\0SebastianBergmann\Diff\Chunk\0endRange";i:6;s:35:"\0SebastianBergmann\Diff\Chunk\0lines";a:7:{i:0;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222222";}i:1;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222222";}i:2;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222222";}i:3;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:8:"+2122212";}i:4;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222222";}i:5;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:7:"2222222";}i:6;O:27:"SebastianBergmann\Diff\Line":2:{s:33:"\0SebastianBergmann\Diff\Line\0type";i:3;s:36:"\0SebastianBergmann\Diff\Line\0content";s:0:"";}}}}}}
EOL;
		return array(
			array(
				"--- old.txt	2014-11-04 08:51:02.661868729 +0300\n+++ new.txt	2014-11-04 08:51:02.665868730 +0300\n@@ -1,3 +1,4 @@\n+2222111\n 1111111\n 1111111\n 1111111\n@@ -5,10 +6,8 @@\n 1111111\n 1111111\n 1111111\n +1121211\n 1111111\n -1111111\n -1111111\n -2222222\n 2222222\n 2222222\n 2222222\n@@ -17,5 +16,6 @@\n 2222222\n 2222222\n 2222222\n +2122212\n 2222222\n 2222222\n",
				unserialize($serialized_arr)
			)
		);
	}
}
