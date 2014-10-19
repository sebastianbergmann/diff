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
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @author     Kore Nordmann <mail@kore-nordmann.de>
 * @copyright  2001-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */

namespace SebastianBergmann\Diff\LCS;

/**
 * Memory-efficient implementation of longest common subsequence calculation.
 *
 * @package    Diff
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @author     Denes Lados <lados.denes@gmail.com>
 * @copyright  2001-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */
class MemoryEfficientImplementation implements LongestCommonSubsequence
{
    /**
     * Calculates the longest common subsequence of two arrays.
     *
     * @param  array $from
     * @param  array $to
     * @return array
     */
    public function calculate(array $from, array $to)
    {
        $cFrom = count($from);
        $cTo   = count($to);

        if ($cFrom == 0) {
            return array();
        } elseif ($cFrom == 1) {
            if (in_array($from[0], $to)) {
                return array($from[0]);
            } else {
                return array();
            }
        } else {
            $i         = intval($cFrom / 2);
            $fromStart = array_slice($from, 0, $i);
            $fromEnd   = array_slice($from, $i);
            $llB       = $this->length($fromStart, $to);
            $llE       = $this->length(array_reverse($fromEnd), array_reverse($to));
            $jMax      = 0;
            $max       = 0;

            for ($j = 0; $j <= $cTo; $j++) {
                $m = $llB[$j] + $llE[$cTo - $j];

                if ($m >= $max) {
                    $max  = $m;
                    $jMax = $j;
                }
            }

            $toStart = array_slice($to, 0, $jMax);
            $toEnd   = array_slice($to, $jMax);

            return array_merge(
                $this->calculate($fromStart, $toStart),
                $this->calculate($fromEnd, $toEnd)
            );
        }
    }

    /**
     * @param array $from
     * @param array $to
     * @return array
     */
    private function length(array $from, array $to)
    {
        $current = array_fill(0, count($to) + 1, 0);
        $cFrom   = count($from);
        $cTo     = count($to);

        for ($i = 0; $i < $cFrom; $i++) {
            $prev = $current;

            for ($j = 0; $j < $cTo; $j++) {
                if ($from[$i] == $to[$j]) {
                    $current[$j + 1] = $prev[$j] + 1;
                } else {
                    $current[$j + 1] = max($current[$j], $prev[$j + 1]);
                }
            }
        }

        return $current;
    }
}
