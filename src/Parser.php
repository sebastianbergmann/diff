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

namespace SebastianBergmann\Diff;

/**
 * Unified diff parser.
 *
 * @package    Diff
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @author     Kore Nordmann <mail@kore-nordmann.de>
 * @copyright  2001-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */
class Parser
{
    /**
     * @param  string $string
     * @return Diff[]
     */
    public function parse($string)
    {
        $lines     = preg_split('(\r\n|\r|\n)', $string);
        $lineCount = count($lines);
        $diffs     = array();
        $diff      = null;
        $collected = array();

        for ($i = 0; $i < $lineCount; ++$i) {
            if (preg_match('(^---\\s+(?P<file>\\S+))', $lines[$i], $fromMatch) &&
                preg_match('(^\\+\\+\\+\\s+(?P<file>\\S+))', $lines[$i + 1], $toMatch)) {
                if ($diff !== null) {
                    $this->parseFileDiff($diff, $collected);
                    $diffs[]   = $diff;
                    $collected = array();
                }

                $diff = new Diff($fromMatch['file'], $toMatch['file']);
                ++$i;
            } else {
                $collected[] = $lines[$i];
            }
        }

        if (count($collected) && ($diff !== null)) {
            $this->parseFileDiff($diff, $collected);
            $diffs[] = $diff;
        }

        return $diffs;
    }

    /**
     * @param Diff  $diff
     * @param array $lines
     */
    private function parseFileDiff(Diff $diff, array $lines)
    {
        $chunks = array();

        while (count($lines)) {
            while (!preg_match('(^@@\\s+-(?P<start>\\d+)(?:,\\s*(?P<startrange>\\d+))?\\s+\\+(?P<end>\\d+)(?:,\\s*(?P<endrange>\\d+))?\\s+@@)', $last = array_shift($lines), $match)) {
                if ($last === null) {
                    break 2;
                }
            }

            $chunk = new Chunk(
                $match['start'],
                isset($match['startrange']) ? max(1, $match['startrange']) : 1,
                $match['end'],
                isset($match['endrange']) ? max(1, $match['endrange']) : 1
            );

            $diffLines = array();
            $last      = null;

            while (count($lines) &&
                  (preg_match('(^(?P<type>[+ -])?(?P<line>.*))', $last = array_shift($lines), $match) ||
                  (strpos($last, '\\ No newline at end of file') === 0))) {
                if (count($match)) {
                    $type = Line::UNCHANGED;

                    if ($match['type'] == '+') {
                        $type = Line::ADDED;
                    } elseif ($match['type'] == '-') {
                        $type = Line::REMOVED;
                    }

                    $diffLines[] = new Line($type, $match['line']);
                }
            }

            $chunk->setLines($diffLines);

            $chunks[] = $chunk;

            if ($last !== null) {
                array_unshift($lines, $last);
            }
        }

        $diff->setChunks($chunks);
    }
}
