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
 * @package    Diff
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @author     Kore Nordmann <mail@kore-nordmann.de>
 * @copyright  2001-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.github.com/sebastianbergmann/diff
 */
class Chunk
{
    private $start;
    private $startRange;
    private $end;
    private $endRange;
    private $lines;

    public function __construct($start = null, $startRange = 1, $end = null, $endRange = 1, array $lines = array())
    {
        $this->start      = (int) $start;
        $this->startRange = (int) $startRange;
        $this->end        = (int) $end;
        $this->endRange   = (int) $endRange;
        $this->lines      = $lines;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getStartRange()
    {
        return $this->startRange;
    }

    public function getEnd()
    {
        return $this->end;
    }

    public function getEndRange()
    {
        return $this->endRange;
    }

    public function getLines()
    {
        return $this->lines;
    }

    public function setLines(array $lines)
    {
        $this->lines = $lines;
    }
}
