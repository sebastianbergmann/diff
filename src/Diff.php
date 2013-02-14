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
 * @author     Kore Nordmann <mail@kore-nordmann.de>
 * @copyright  2001-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */

namespace SebastianBergmann;

/**
 * Diff implementation.
 *
 * @package    Diff
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @author     Kore Nordmann <mail@kore-nordmann.de>
 * @copyright  2001-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */
class Diff
{
    /**
     * Token has not changed
     *
     * @var integer
     */
    const OLD = 0;

    /**
     * Token was added
     *
     * @var integer
     */
    const ADDED = 1;

    /**
     * Token was removed
     *
     * @var integer
     */
    const REMOVED = 2;

    /**
     * The original string
     *
     * @var string
     */
    private $from;

    /**
     * The new string
     *
     * @var string
     */
    private $to;

    /**
     * The diff header
     *
     * @var string
     */
    private $header = "--- Original\n+++ New\n";

    /**
     * Constructs a new diff for two given strings
     *
     * @param mixed $from The original string
     * @param mixed $to The new string
     * @param mixed $header The diff header
     */
    public function __construct($from, $to, $header = '')
    {
        $this->from = $from;
        $this->to = $to;

        if ($header) {
            $this->header = $header;
        }
    }

    /**
     * Exports a value into a string.
     *
     * @return string
     * @see    PHP_Exporter\Diff::diff
     */
    public function __toString()
    {
        return $this->diff();
    }

    /**
     * Returns the diff between two strings as a string.
     *
     * @return string
     */
    public function diff()
    {
        if ($this->from === $this->to) {
            return '';
        }

        $buffer = $this->header;
        $diff   = $this->toArray();

        $inOld = FALSE;
        $i     = 0;
        $old   = array();

        foreach ($diff as $line) {
            if ($line[1] ===  0 /* OLD */) {
                if ($inOld === FALSE) {
                    $inOld = $i;
                }
            }

            else if ($inOld !== FALSE) {
                if (($i - $inOld) > 5) {
                    $old[$inOld] = $i - 1;
                }

                $inOld = FALSE;
            }

            ++$i;
        }

        $start = isset($old[0]) ? $old[0] : 0;
        $end   = count($diff);

        if ($tmp = array_search($end, $old)) {
            $end = $tmp;
        }

        $newChunk = TRUE;

        for ($i = $start; $i < $end; $i++) {
            if (isset($old[$i])) {
                $buffer  .= "\n";
                $newChunk = TRUE;
                $i        = $old[$i];
            }

            if ($newChunk) {
                $buffer  .= "@@ @@\n";
                $newChunk = FALSE;
            }

            if ($diff[$i][1] === static::ADDED) {
                $buffer .= '+' . $diff[$i][0] . "\n";
            }

            else if ($diff[$i][1] === static::REMOVED) {
                $buffer .= '-' . $diff[$i][0] . "\n";
            }

            else {
                $buffer .= ' ' . $diff[$i][0] . "\n";
            }
        }

        return $buffer;
    }

    /**
     * Returns the diff between two strings as an array.
     *
     * every array-entry containts two elements:
     *   - [0] => string $token
     *   - [1] => 2|1|0
     *
     * - 2: REMOVED: $token was removed from $from
     * - 1: ADDED: $token was added to $from
     * - 0: OLD: $token is not changed in $to
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->from === $this->to) {
            return array();
        }

        $from = preg_split('(\r\n|\r|\n)', $this->from);
        $to = preg_split('(\r\n|\r|\n)', $this->to);

        $start      = array();
        $end        = array();
        $fromLength = count($from);
        $toLength   = count($to);
        $length     = min($fromLength, $toLength);

        for ($i = 0; $i < $length; ++$i) {
            if ($from[$i] === $to[$i]) {
                $start[] = $from[$i];
                unset($from[$i], $to[$i]);
            } else {
                break;
            }
        }

        $length -= $i;

        for ($i = 1; $i < $length; ++$i) {
            if ($from[$fromLength - $i] === $to[$toLength - $i]) {
                array_unshift($end, $from[$fromLength - $i]);
                unset($from[$fromLength - $i], $to[$toLength - $i]);
            } else {
                break;
            }
        }

        $common = self::longestCommonSubsequence(
          array_values($from), array_values($to)
        );

        $diff = array();

        foreach ($start as $token) {
            $diff[] = array($token, static::OLD);
        }

        reset($from);
        reset($to);

        foreach ($common as $token) {
            while ((($fromToken = reset($from)) !== $token)) {
                $diff[] = array(array_shift($from), static::REMOVED);
            }

            while ((($toToken = reset($to)) !== $token)) {
                $diff[] = array(array_shift($to), static::ADDED);
            }

            $diff[] = array($token, static::OLD);

            array_shift($from);
            array_shift($to);
        }

        while (($token = array_shift($from)) !== NULL) {
            $diff[] = array($token, static::REMOVED);
        }

        while (($token = array_shift($to)) !== NULL) {
            $diff[] = array($token, static::ADDED);
        }

        foreach ($end as $token) {
            $diff[] = array($token, static::OLD);
        }

        return $diff;
    }

    /**
     * Calculates the longest common subsequence of two arrays.
     *
     * @param  array $from
     * @param  array $to
     * @return array
     */
    protected static function longestCommonSubsequence(array $from, array $to)
    {
        $common     = array();
        $matrix     = array();
        $fromLength = count($from);
        $toLength   = count($to);

        for ($i = 0; $i <= $fromLength; ++$i) {
            $matrix[$i][0] = 0;
        }

        for ($j = 0; $j <= $toLength; ++$j) {
            $matrix[0][$j] = 0;
        }

        for ($i = 1; $i <= $fromLength; ++$i) {
            for ($j = 1; $j <= $toLength; ++$j) {
                $matrix[$i][$j] = max(
                  $matrix[$i-1][$j],
                  $matrix[$i][$j-1],
                  $from[$i-1] === $to[$j-1] ? $matrix[$i-1][$j-1] + 1 : 0
                );
            }
        }

        $i = $fromLength;
        $j = $toLength;

        while ($i > 0 && $j > 0) {
            if ($from[$i-1] === $to[$j-1]) {
                array_unshift($common, $from[$i-1]);
                --$i;
                --$j;
            }

            else if ($matrix[$i][$j-1] > $matrix[$i-1][$j]) {
                --$j;
            }

            else {
                --$i;
            }
        }

        return $common;
    }
}
