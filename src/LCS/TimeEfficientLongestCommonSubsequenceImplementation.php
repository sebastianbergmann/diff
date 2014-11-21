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
 * Time-efficient implementation of longest common subsequence calculation.
 *
 * @package    Diff
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @author     Kore Nordmann <mail@kore-nordmann.de>
 * @copyright  2001-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */
class TimeEfficientImplementation implements LongestCommonSubsequence
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
        $common     = array();
        $fromLength = count($from);
        $toLength   = count($to);
        $width      = $fromLength + 1;
        $matrix     = new \SplFixedArray($width * ($toLength + 1));

        for ($i = 0; $i <= $fromLength; ++$i) {
            $matrix[$i] = 0;
        }

        for ($j = 0; $j <= $toLength; ++$j) {
            $matrix[$j * $width] = 0;
        }

        for ($i = 1; $i <= $fromLength; ++$i) {
            for ($j = 1; $j <= $toLength; ++$j) {
                $o = ($j * $width) + $i;
                $matrix[$o] = max(
                    $matrix[$o - 1],
                    $matrix[$o - $width],
                    $from[$i - 1] === $to[$j - 1] ? $matrix[$o - $width - 1] + 1 : 0
                );
            }
        }

        $i = $fromLength;
        $j = $toLength;

        while ($i > 0 && $j > 0) {
            if ($from[$i-1] === $to[$j-1]) {
                $common[] = $from[$i-1];
                --$i;
                --$j;
            } else {
                $o = ($j * $width) + $i;
                if ($matrix[$o - $width] > $matrix[$o - 1]) {
                    --$j;
                } else {
                    --$i;
                }
            }
        }

        return array_reverse($common);
    }
}
