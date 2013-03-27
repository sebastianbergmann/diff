<?php
/**
 * Diff
 *
 * Copyright (c) 2001-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Diff
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2001-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */

namespace SebastianBergmann;

class DiffTest extends \PHPUnit_Framework_TestCase
{
    const REMOVED = 2;
    const ADDED = 1;
    const OLD = 0;

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testComparisonErrorMessage()
    {
        $this->assertEquals(
          "--- Original\n+++ New\n@@ @@\n-a\n+b\n",
          new Diff('a', 'b')
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testComparisonErrorMessage_toArray()
    {
        $expected = array();
        $expected[] = array('a', self::REMOVED);
        $expected[] = array('b', self::ADDED);

        $diff = new Diff('a', 'b');
        $this->assertEquals($expected, $diff->toArray());
    }

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testComparisonErrorStartSame()
    {
        $this->assertEquals(
          "--- Original\n+++ New\n@@ @@\n-ba\n+bc\n",
          new Diff('ba', 'bc')
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testComparisonErrorStartSame_toArray()
    {
        $expected = array();
        $expected[] = array('ba', self::REMOVED);
        $expected[] = array('bc', self::ADDED);

        $diff = new Diff('ba', 'bc');
        $this->assertEquals($expected, $diff->toArray());
    }

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testComparisonErrorEndSame()
    {
        $this->assertEquals(
          "--- Original\n+++ New\n@@ @@\n-ab\n+cb\n",
          new Diff('ab', 'cb')
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testComparisonErrorEndSame_toArray()
    {
        $expected = array();
        $expected[] = array('ab', self::REMOVED);
        $expected[] = array('cb', self::ADDED);

        $diff = new Diff('ab', 'cb');
        $this->assertEquals($expected, $diff->toArray());
    }

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testComparisonErrorStartAndEndSame()
    {
        $this->assertEquals(
          "--- Original\n+++ New\n@@ @@\n-abc\n+adc\n",
          new Diff('abc', 'adc')
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testComparisonErrorStartAndEndSame_toArray()
    {
        $expected = array();
        $expected[] = array('abc', self::REMOVED);
        $expected[] = array('adc', self::ADDED);

        $diff = new Diff('abc', 'adc');
        $this->assertEquals($expected, $diff->toArray());
    }

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testComparisonErrorStartSameComplete()
    {
        $this->assertEquals(
          "--- Original\n+++ New\n@@ @@\n-ab\n+abc\n",
          new Diff('ab', 'abc')
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testComparisonErrorStartSameComplete_toArray()
    {
        $expected = array();
        $expected[] = array('ab', self::REMOVED);
        $expected[] = array('abc', self::ADDED);

        $diff = new Diff('ab', 'abc');
        $this->assertEquals($expected, $diff->toArray());
    }

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testComparisonErrorEndSameComplete()
    {
        $this->assertEquals(
          "--- Original\n+++ New\n@@ @@\n-bc\n+abc\n",
          new Diff('bc', 'abc')
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testComparisonErrorEndSameComplete_toArray()
    {
        $expected = array();
        $expected[] = array('bc', self::REMOVED);
        $expected[] = array('abc', self::ADDED);

        $diff = new Diff('bc', 'abc');
        $this->assertEquals($expected, $diff->toArray());
    }

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testComparisonErrorOverlapingMatches()
    {
        $this->assertEquals(
          "--- Original\n+++ New\n@@ @@\n-abc\n+abbc\n",
          new Diff('abc', 'abbc')
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testComparisonErrorOverlapingMatches_toArray()
    {
        $expected = array();
        $expected[] = array('abc', self::REMOVED);
        $expected[] = array('abbc', self::ADDED);

        $diff = new Diff('abc', 'abbc');
        $this->assertEquals($expected, $diff->toArray());
    }

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testComparisonErrorOverlapingMatches2()
    {
        $this->assertEquals(
            "--- Original\n+++ New\n@@ @@\n-abcdde\n+abcde\n",
            new Diff('abcdde', 'abcde')
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testComparisonErrorOverlapingMatches2_toArray()
    {
        $expected = array();
        $expected[] = array('abcdde', self::REMOVED);
        $expected[] = array('abcde', self::ADDED);

        $diff = new Diff('abcdde', 'abcde');
        $this->assertEquals($expected, $diff->toArray());
    }

    /**
     * @covers SebastianBergmann\Diff::diff
     */
    public function testEmptyDiff()
    {
        $this->assertEquals(
            "--- Original\n+++ New\n@@ @@\n abc\n",
            (string)(new Diff('abc', 'abc'))
        );
    }

    /**
     * @covers SebastianBergmann\Diff::toArray
     */
    public function testEmptyDiff_toArray()
    {
        $diff = new Diff('abc', 'abc');
        $this->assertEquals(
            array(
                array('abc', 0)
            ),
            $diff->toArray()
         );
    }

    public function testCustomHeader()
    {
        $this->assertEquals(
          "CUSTOM HEADER@@ @@\n-a\n+b\n",
          new Diff('a', 'b', 'CUSTOM HEADER')
        );
    }
}
