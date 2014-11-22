<?php
/**
 * Diff
 *
 * Copyright (c) 2001-2014, Sebastian Bergmann <sebastian@phpunit.de>.
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
 * @author     Guillaume Crico <guillaume.crico@gmail.com>
 * @copyright  2001-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */

namespace SebastianBergmann\Diff\LCS;

use PHPUnit_Framework_TestCase;

/**
 * Some of these tests are volontary stressfull, in order to give some approximative benchmark hints.
 */
class TimeEfficientImplementationTest extends PHPUnit_Framework_TestCase
{
    private $implementation;
    private $memory_limit;
    private $stress_sizes = array(1, 2, 3, 100, 500, 1000, 2000);

    protected function setUp()
    {
        $this->memory_limit = ini_get('memory_limit');
        ini_set('memory_limit', '256M');

        $this->implementation = new TimeEfficientImplementation;
    }

    protected function tearDown()
    {
        ini_set('memory_limit', $this->memory_limit);
    }

    public function testBothEmpty()
    {
        $from   = array();
        $to     = array();
        $common = $this->implementation->calculate($from, $to);

        $this->assertEquals(array(), $common);
    }

    public function testIsStrictComparison()
    {
        $from = array(
            false, 0, 0.0, '', null, array(),
            true, 1, 1.0, 'foo', array('foo', 'bar'), array('foo' => 'bar')
        );
        $to = $from;
        $common = $this->implementation->calculate($from, $to);

        $this->assertEquals($from, $common);

        $to = array(
            false, false, false, false, false, false,
            true, true, true, true, true, true
        );
        $expected = array(
            false,
            true,
        );
        $common = $this->implementation->calculate($from, $to);

        $this->assertEquals($expected, $common);
    }

    public function testEqualSequences()
    {
        foreach ($this->stress_sizes as $size) {
            $range  = range(1, $size);
            $from   = $range;
            $to     = $range;
            $common = $this->implementation->calculate($from, $to);

            $this->assertEquals($range, $common);
        }
    }

    public function testDistinctSequences()
    {
        $from  = array('A');
        $to    = array('B');
        $common = $this->implementation->calculate($from, $to);
        $this->assertEquals(array(), $common);

        $from  = array('A', 'B', 'C');
        $to    = array('D', 'E', 'F');
        $common = $this->implementation->calculate($from, $to);
        $this->assertEquals(array(), $common);

        foreach ($this->stress_sizes as $size) {
            $from  = range(1, $size);
            $to    = range($size + 1, $size * 2);
            $common = $this->implementation->calculate($from, $to);
            $this->assertEquals(array(), $common);
        }
    }

    public function testCommonSubsequence()
    {
        $from     = array('A',      'C',      'E', 'F', 'G'     );
        $to       = array('A', 'B',      'D', 'E',           'H');
        $expected = array('A',                'E'               );
        $common   = $this->implementation->calculate($from, $to);
        $this->assertEquals($expected, $common);

        $from     = array('A',      'C',      'E', 'F', 'G'     );
        $to       = array(     'B', 'C', 'D', 'E', 'F',      'H');
        $expected = array('C',                'E', 'F'          );
        $common   = $this->implementation->calculate($from, $to);
        $this->assertEquals($expected, $common);

        foreach ($this->stress_sizes as $size) {
            $from     = $size < 2 ? array(1) : range(1, $size + 1, 2);
            $to       = $size < 3 ? array(1) : range(1, $size + 1, 3);
            $expected = $size < 6 ? array(1) : range(1, $size + 1, 6);
            $common   = $this->implementation->calculate($from, $to);

            $this->assertEquals($expected, $common);
        }
    }

    public function testSingleElementSubsequenceAtStart()
    {
        foreach ($this->stress_sizes as $size) {
            $from   = range(1, $size);
            $to     = array_slice($from, 0, 1);
            $common = $this->implementation->calculate($from, $to);

            $this->assertEquals($to, $common);
        }
    }

    public function testSingleElementSubsequenceAtMiddle()
    {
        foreach ($this->stress_sizes as $size) {
            $from   = range(1, $size);
            $to     = array_slice($from, (int) $size / 2, 1);
            $common = $this->implementation->calculate($from, $to);

            $this->assertEquals($to, $common);
        }
    }

    public function testSingleElementSubsequenceAtEnd()
    {
        foreach ($this->stress_sizes as $size) {
            $from   = range(1, $size);
            $to     = array_slice($from, $size - 1, 1);
            $common = $this->implementation->calculate($from, $to);

            $this->assertEquals($to, $common);
        }
    }

    public function testReversedSequences()
    {
        $from     = array('A', 'B');
        $to       = array('B', 'A');
        $expected = array('A');
        $common   = $this->implementation->calculate($from, $to);
        $this->assertEquals($expected, $common);

        foreach ($this->stress_sizes as $size) {
            $from   = range(1, $size);
            $to     = array_reverse($from);
            $common = $this->implementation->calculate($from, $to);

            $this->assertEquals(array(1), $common);
        }
    }
}
