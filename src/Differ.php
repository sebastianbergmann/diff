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

/**
 * Diff implementation.
 */
final class Differ
{
    const MODE_CHUNK        = 1;
    const MODE_FULL         = 2;
    const MODE_CHANGED_ONLY = 3;

    /**
     * @var string
     */
    private $header;

    /**
     * @var int
     */
    private $mode;

    public function __construct(string $header = "--- Original\n+++ New\n", int $mode = self::MODE_CHUNK)
    {
        $this->header = $header;

        if ($mode !== self::MODE_CHUNK && $mode !== self::MODE_FULL && $mode !== self::MODE_CHANGED_ONLY) {
            throw new \InvalidArgumentException(\sprintf(
                'Mode must be any of MODE_CHUNK (%d) MODE_FULL (%d) MODE_CHANGED_ONLY (%d) got %s.',
                self::MODE_CHUNK, self::MODE_FULL, self::MODE_CHANGED_ONLY,
                $mode
            ));
        }

        $this->mode = $mode;
    }

    /**
     * Returns the diff between two arrays or strings as string.
     *
     * @param array|string                            $from
     * @param array|string                            $to
     * @param LongestCommonSubsequenceCalculator|null $lcs
     *
     * @return string
     */
    public function diff($from, $to, LongestCommonSubsequenceCalculator $lcs = null): string
    {
        $from = $this->validateDiffInput($from);
        $to   = $this->validateDiffInput($to);

        return $this->getBuffer($this->diffToArray($from, $to, $lcs));
    }

    /**
     * Casts variable to string if it is not a string or array.
     *
     * @param mixed $input
     *
     * @return string
     */
    private function validateDiffInput($input): string
    {
        if (!\is_array($input) && !\is_string($input)) {
            return (string) $input;
        }

        return $input;
    }

    /**
     * Takes input of the diff array and returns the old array.
     * Iterates through diff line by line,
     *
     * @param array $diff
     *
     * @return array
     */
    private function checkIfDiffInOld(array $diff): array
    {
        $inOld = false;
        $i     = 0;
        $old   = [];

        foreach ($diff as $line) {
            if ($line[1] === 0 /* OLD */) {
                if ($inOld === false) {
                    $inOld = $i;
                }
            } elseif ($inOld !== false) {
                if (($i - $inOld) > 5) {
                    $old[$inOld] = $i - 1;
                }

                $inOld = false;
            }

            ++$i;
        }

        return $old;
    }

    /**
     * Generates buffer in string format, returning the patch.
     *
     * @param array $diff
     *
     * @return string
     */
    private function getBuffer(array $diff): string
    {
        $buffer = \fopen('php://memory', 'r+b');
        \fwrite($buffer, $this->header);

        if ($this->mode === self::MODE_CHUNK) {
            $this->writeDiffChunked(
                $buffer,
                $diff,
                $this->checkIfDiffInOld($diff)
            );
        } elseif ($this->mode === self::MODE_FULL) {
            $this->writeDiffFull($buffer, $diff);
        } else { //if ($this->mode === self::MODE_CHANGED_ONLY) {
            $this->writeDiffChangedOnly($buffer, $diff);
        }

        $diff = \stream_get_contents($buffer, -1, 0);
        \fclose($buffer);

        return $diff;
    }

    // `old` is an array with key => value pairs . Each pair represents a start and end index of `diff`
    // of a list of elements all containing `same` (0) entries.
    private function writeDiffChunked($output, array $diff, array $old)
    {
        $upperLimit = \count($diff);

        if (!\count($old)) {
            // no common parts, i.e. one diff of one chunk
            // do not add lines from the end that do not contain changes, but keep the last same for context
            while (isset($diff[$upperLimit - 2]) && 0 === $diff[$upperLimit - 2][1]) {
                --$upperLimit;
            }

            list($fromRange, $toRange) = $this->getChunkRange($diff, 0, $upperLimit);
            $this->writeChunk($output, $diff, 0, $upperLimit, 0, $fromRange, 0, $toRange);

            return;
        }

        \reset($old);
        $start     = 0;
        $fromStart = 0;
        $toStart   = 0;

        // iterate the diff, go from chunk to chunk skipping same chunk of lines between those
        do {
            $end = \key($old);
            if (0 !== $end) {
                list($fromRange, $toRange) = $this->getChunkRange($diff, $start, $end);
                $this->writeChunk($output, $diff, $start, $end, $fromStart, $fromRange, $toStart, $toRange);

                // correct start of diff with the range covered
                $fromStart += $fromRange;
                $toStart += $toRange;
            }

            // update start with the `old` (i.e. not modified) range we are about to skip
            // so from this 'end' till the next 'start'
            $start = \current($old);

            $fromStart += ($start - $end) + 1;
            $toStart += ($start - $end) + 1;

            ++$start;
        } while (false !== \next($old));

        // do not add lines from the end that do not contain changes, but keep the last same for context
        while (isset($diff[$upperLimit - 2]) && 0 === $diff[$upperLimit - 2][1]) {
            --$upperLimit;
        }

        // create a chunk till the end if needed
        if ($start < $upperLimit) {
            list($fromRange, $toRange) = $this->getChunkRange($diff, $start, $upperLimit);
            $this->writeChunk($output, $diff, $start, $upperLimit, $fromStart, $fromRange, $toStart, $toRange);
        } // else { not possible with the current duff builder, however should not be an issue here }
    }

    private function writeChunk(
        $output,
        array $diff,
        int $diffStartIndex,
        int $diffEndIndex,
        int $fromStart,
        int $fromRange,
        int $toStart,
        int $toRange
    ) {
        \fwrite($output, '@@ -' . (1 + $fromStart));
        if ($fromRange > 1) {
            \fwrite($output, ',' . $fromRange);
        }

        \fwrite($output, ' +' . (1 + $toStart));
        if ($toRange > 1) {
            \fwrite($output, ',' . $toRange);
        }

        \fwrite($output, " @@\n");

        for ($i = $diffStartIndex; $i < $diffEndIndex; ++$i) {
            if ($diff[$i][1] === 1 /* ADDED */) {
                \fwrite($output, '+' . $diff[$i][0] . "\n");
            } elseif ($diff[$i][1] === 2 /* REMOVED */) {
                \fwrite($output, '-' . $diff[$i][0] . "\n");
            } else { /* Not changed (old) */
                \fwrite($output, ' ' . $diff[$i][0] . "\n");
            }
        }
    }

    private function getChunkRange(array $diff, int $diffStartIndex, int $diffEndIndex): array
    {
        $toRange   = 0;
        $fromRange = 0;

        for ($i = $diffStartIndex; $i < $diffEndIndex; ++$i) {
            if ($diff[$i][1] === 1) { // added
                ++$toRange;
            } elseif ($diff[$i][1] === 2) { // removed
                ++$fromRange;
            } else { // { ($diff[$i][1] === 0) { // same }
                ++$fromRange;
                ++$toRange;
            }
        }

        return [$fromRange, $toRange];
    }

    private function writeDiffFull($output, array $diff)
    {
        $toRange   = 0;
        $fromRange = 0;

        foreach ($diff as $diffEntry) {
            if ($diffEntry[1] === 1) { // added
                ++$toRange;
            } elseif ($diffEntry[1] === 2) { // removed
                ++$fromRange;
            } else { // { ($diff[1] === 0) { // same }
                ++$fromRange;
                ++$toRange;
            }
        }

        \fwrite($output, '@@ -1');
        if ($fromRange > 1) {
            \fwrite($output, ',' . $fromRange);
        }

        \fwrite($output, ' +1');
        if ($toRange > 1) {
            \fwrite($output, ',' . $toRange);
        }

        \fwrite($output, " @@\n");

        foreach ($diff as $diffEntry) {
            if ($diffEntry[1] === 1 /* ADDED */) {
                \fwrite($output, '+' . $diffEntry[0] . "\n");
            } elseif ($diffEntry[1] === 2 /* REMOVED */) {
                \fwrite($output, '-' . $diffEntry[0] . "\n");
            } else { /* Not changed (old) */
                \fwrite($output, ' ' . $diffEntry[0] . "\n");
            }
        }
    }

    private function writeDiffChangedOnly($output, array $diff)
    {
        foreach ($diff as $diffEntry) {
            if ($diffEntry[1] === 1 /* ADDED */) {
                \fwrite($output, '+' . $diffEntry[0] . "\n");
            } elseif ($diffEntry[1] === 2 /* REMOVED */) {
                \fwrite($output, '-' . $diffEntry[0] . "\n");
            }
        }
    }

    /**
     * Returns the diff between two arrays or strings as array.
     *
     * Each array element contains two elements:
     *   - [0] => mixed $token
     *   - [1] => 2|1|0
     *
     * - 2: REMOVED: $token was removed from $from
     * - 1: ADDED: $token was added to $from
     * - 0: OLD: $token is not changed in $to
     *
     * @param array|string                       $from
     * @param array|string                       $to
     * @param LongestCommonSubsequenceCalculator $lcs
     *
     * @return array
     */
    public function diffToArray($from, $to, LongestCommonSubsequenceCalculator $lcs = null): array
    {
        if (\is_string($from)) {
            $fromMatches = $this->getNewLineMatches($from);
            $from        = $this->splitStringByLines($from);
        } elseif (\is_array($from)) {
            $fromMatches = [];
        } else {
            throw new \InvalidArgumentException('"from" must be an array or string.');
        }

        if (\is_string($to)) {
            $toMatches = $this->getNewLineMatches($to);
            $to        = $this->splitStringByLines($to);
        } elseif (\is_array($to)) {
            $toMatches = [];
        } else {
            throw new \InvalidArgumentException('"to" must be an array or string.');
        }

        list($from, $to, $start, $end) = self::getArrayDiffParted($from, $to);

        if ($lcs === null) {
            $lcs = $this->selectLcsImplementation($from, $to);
        }

        $common = $lcs->calculate(\array_values($from), \array_values($to));
        $diff   = [];

        if ($this->detectUnmatchedLineEndings($fromMatches, $toMatches)) {
            $diff[] = [
                '#Warning: Strings contain different line endings!',
                0
            ];
        }

        foreach ($start as $token) {
            $diff[] = [$token, 0 /* OLD */];
        }

        \reset($from);
        \reset($to);

        foreach ($common as $token) {
            while (($fromToken = \reset($from)) !== $token) {
                $diff[] = [\array_shift($from), 2 /* REMOVED */];
            }

            while (($toToken = \reset($to)) !== $token) {
                $diff[] = [\array_shift($to), 1 /* ADDED */];
            }

            $diff[] = [$token, 0 /* OLD */];

            \array_shift($from);
            \array_shift($to);
        }

        while (($token = \array_shift($from)) !== null) {
            $diff[] = [$token, 2 /* REMOVED */];
        }

        while (($token = \array_shift($to)) !== null) {
            $diff[] = [$token, 1 /* ADDED */];
        }

        foreach ($end as $token) {
            $diff[] = [$token, 0 /* OLD */];
        }

        return $diff;
    }

    /**
     * Get new strings denoting new lines from a given string.
     *
     * @param string $string
     *
     * @return array
     */
    private function getNewLineMatches(string $string): array
    {
        \preg_match_all('(\r\n|\r|\n)', $string, $stringMatches);

        return $stringMatches;
    }

    /**
     * Checks if input is string, if so it will split it line-by-line.
     *
     * @param string $input
     *
     * @return array
     */
    private function splitStringByLines(string $input): array
    {
        return \preg_split('(\r\n|\r|\n)', $input);
    }

    /**
     * @param array $from
     * @param array $to
     *
     * @return LongestCommonSubsequenceCalculator
     */
    private function selectLcsImplementation(array $from, array $to): LongestCommonSubsequenceCalculator
    {
        // We do not want to use the time-efficient implementation if its memory
        // footprint will probably exceed this value. Note that the footprint
        // calculation is only an estimation for the matrix and the LCS method
        // will typically allocate a bit more memory than this.
        $memoryLimit = 100 * 1024 * 1024;

        if ($this->calculateEstimatedFootprint($from, $to) > $memoryLimit) {
            return new MemoryEfficientLongestCommonSubsequenceCalculator;
        }

        return new TimeEfficientLongestCommonSubsequenceCalculator;
    }

    /**
     * Calculates the estimated memory footprint for the DP-based method.
     *
     * @param array $from
     * @param array $to
     *
     * @return int|float
     */
    private function calculateEstimatedFootprint(array $from, array $to)
    {
        $itemSize = PHP_INT_SIZE === 4 ? 76 : 144;

        return $itemSize * \min(\count($from), \count($to)) ** 2;
    }

    /**
     * Returns true if line ends don't match on fromMatches and toMatches.
     *
     * @param array $fromMatches
     * @param array $toMatches
     *
     * @return bool
     */
    private function detectUnmatchedLineEndings(array $fromMatches, array $toMatches): bool
    {
        return isset($fromMatches[0], $toMatches[0]) &&
               \count($fromMatches[0]) === \count($toMatches[0]) &&
               $fromMatches[0] !== $toMatches[0];
    }

    private static function getArrayDiffParted(array &$from, array &$to): array
    {
        $start = [];
        $end   = [];

        \reset($to);

        foreach ($from as $k => $v) {
            $toK = \key($to);

            if ($toK === $k && $v === $to[$k]) {
                $start[$k] = $v;

                unset($from[$k], $to[$k]);
            } else {
                break;
            }
        }

        \end($from);
        \end($to);

        do {
            $fromK = \key($from);
            $toK   = \key($to);

            if (null === $fromK || null === $toK || \current($from) !== \current($to)) {
                break;
            }

            \prev($from);
            \prev($to);

            $end = [$fromK => $from[$fromK]] + $end;
            unset($from[$fromK], $to[$toK]);
        } while (true);

        return [$from, $to, $start, $end];
    }
}
