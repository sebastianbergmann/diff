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

use SebastianBergmann\Diff\Output\DiffOutputBuilderInterface;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Diff implementation.
 */
final class Differ
{
    /**
     * @var DiffOutputBuilderInterface
     */
    private $outputBuilder;

    public function __construct(DiffOutputBuilderInterface $outputBuilder = null)
    {
        $this->outputBuilder = $outputBuilder ?? new UnifiedDiffOutputBuilder();
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
        $diff = $this->diffToArray($from, $to, $lcs);

        return $this->outputBuilder->getDiff($diff);
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
                "#Warning: Strings contain different line endings!\n",
                3
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
        return \preg_split('/(.*\R)/', $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
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
