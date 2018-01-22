<?php declare(strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\Diff\Output;

use SebastianBergmann\Diff\ConfigurationException;

/**
 * Strict Unified diff output builder.
 *
 * @name Unified diff output builder
 *
 * @description Generates (strict) Unified diff's (unidiffs) with hunks.
 *
 * @author SpacePossum
 *
 * @api
 */
final class StrictUnifiedDiffOutputBuilder implements DiffOutputBuilderInterface
{
    /**
     * @var int
     */
    private static $noNewlineAtOEFid = 998877;

    /**
     * @var bool
     */
    private $changed;

    /**
     * @var bool
     */
    private $collapseRanges;

    /**
     * @var int >= 0
     */
    private $commonLineThreshold;

    /**
     * @var string
     */
    private $header;

    /**
     * @var int >= 0
     */
    private $contextLines;

    private static $default = [
        'collapseRanges'      => true, // ranges of length one are rendered with the trailing `,1`
        'commonLineThreshold' => 6,    // number of same lines before ending a new hunk and creating a new one (if needed)
        'contextLines'        => 3,    // like `diff:  -u, -U NUM, --unified[=NUM]`, for patch/git apply compatibility best to keep at least @ 3
        'fromFile'            => null,
        'fromFileDate'        => null,
        'toFile'              => null,
        'toFileDate'          => null,
    ];

    public function __construct(array $options = [])
    {
        $options = \array_merge(self::$default, $options);

        if (!\is_bool($options['collapseRanges'])) {
            throw new ConfigurationException('collapseRanges', 'a bool', $options['collapseRanges']);
        }

        if (!\is_int($options['contextLines']) || $options['contextLines'] < 0) {
            throw new ConfigurationException('contextLines', 'an int >= 0', $options['contextLines']);
        }

        if (!\is_int($options['commonLineThreshold']) || $options['commonLineThreshold'] < 1) {
            throw new ConfigurationException('commonLineThreshold', 'an int > 0', $options['commonLineThreshold']);
        }

        foreach (['fromFile', 'toFile'] as $option) {
            if (!\is_string($options[$option])) {
                throw new ConfigurationException($option, 'a string', $options[$option]);
            }
        }

        foreach (['fromFileDate', 'toFileDate'] as $option) {
            if (null !== $options[$option] && !\is_string($options[$option])) {
                throw new ConfigurationException($option, 'a string or <null>', $options[$option]);
            }
        }

        $this->header = \sprintf(
            "--- %s%s\n+++ %s%s\n",
            $options['fromFile'],
            null === $options['fromFileDate'] ? '' : "\t" . $options['fromFileDate'],
            $options['toFile'],
            null === $options['toFileDate'] ? '' : "\t" . $options['toFileDate']
        );

        $this->collapseRanges      = $options['collapseRanges'];
        $this->commonLineThreshold = $options['commonLineThreshold'];
        $this->contextLines        = $options['contextLines'];
    }

    public function getDiff(array $diff): string
    {
        if (0 === \count($diff)) {
            return '';
        }

        $this->changed = false;

        $buffer = \fopen('php://memory', 'r+b');
        \fwrite($buffer, $this->header);

        $this->writeDiffHunks($buffer, $diff);

        if (!$this->changed) {
            \fclose($buffer);

            return '';
        }

        $diff = \stream_get_contents($buffer, -1, 0);

        \fclose($buffer);

        // If the last char is not a linebreak: add it.
        // This might happen when both the `from` and `to` do not have a trailing linebreak
        $last = \substr($diff, -1);

        return "\n" !== $last && "\r" !== $last
            ? $diff . "\n"
            : $diff
        ;
    }

    private function writeDiffHunks($output, array $diff)
    {
        // detect "No newline at end of file" and insert into `$diff` if needed

        $upperLimit = \count($diff);

        if (0 === $diff[$upperLimit - 1][1]) {
            $lc = \substr($diff[$upperLimit - 1][0], -1);
            if ("\n" !== $lc) {
                \array_splice($diff, $upperLimit, 0, [["\n\\ No newline at end of file\n", self::$noNewlineAtOEFid]]);
            }
        } else {
            // search back for the last `+` and `-` line,
            // check if has trailing linebreak, else add under it warning under it
            $toFind = [1 => true, 2 => true];
            for ($i = $upperLimit - 1; $i >= 0; --$i) {
                if (isset($toFind[$diff[$i][1]])) {
                    unset($toFind[$diff[$i][1]]);
                    $lc = \substr($diff[$i][0], -1);
                    if ("\n" !== $lc) {
                        \array_splice($diff, $i + 1, 0, [["\n\\ No newline at end of file\n", self::$noNewlineAtOEFid]]);
                    }

                    if (!\count($toFind)) {
                        break;
                    }
                }
            }
        }

        // write hunks to output buffer

        $cutOff      = \max($this->commonLineThreshold, $this->contextLines);
        $hunkCapture = false;
        $sameCount   = $toRange = $fromRange = 0;
        $toStart     = $fromStart = 1;

        foreach ($diff as $i => $entry) {
            if (0 === $entry[1]) { // same
                if (false === $hunkCapture) {
                    ++$fromStart;
                    ++$toStart;

                    continue;
                }

                ++$sameCount;
                ++$toRange;
                ++$fromRange;

                if ($sameCount === $cutOff) {
                    $contextStartOffset = ($hunkCapture - $this->contextLines) < 0
                        ? $hunkCapture
                        : $this->contextLines
                    ;

                    // note: $contextEndOffset = $this->contextLines;
                    //
                    // because we never go beyond the end of the diff.
                    // with the cutoff/contextlines here the follow is never true;
                    //
                    // if ($i - $cutOff + $this->contextLines + 1 > \count($diff)) {
                    //    $contextEndOffset = count($diff) - 1;
                    // }
                    //
                    // ; that would be true for a trailing incomplete hunk case which is dealt with after this loop

                    $this->writeHunk(
                        $diff,
                        $hunkCapture - $contextStartOffset,
                        $i - $cutOff + $this->contextLines + 1,
                        $fromStart - $contextStartOffset,
                        $fromRange - $cutOff + $contextStartOffset + $this->contextLines,
                        $toStart - $contextStartOffset,
                        $toRange - $cutOff + $contextStartOffset + $this->contextLines,
                        $output
                    );

                    $fromStart += $fromRange;
                    $toStart += $toRange;

                    $hunkCapture = false;
                    $sameCount   = $toRange = $fromRange = 0;
                }

                continue;
            }

            $sameCount = 0;

            if ($entry[1] === self::$noNewlineAtOEFid) {
                continue;
            }

            $this->changed = true;

            if (false === $hunkCapture) {
                $hunkCapture = $i;
            }

            if (1 === $entry[1]) { // added
                ++$toRange;
            }

            if (2 === $entry[1]) { // removed
                ++$fromRange;
            }
        }

        if (false === $hunkCapture) {
            return;
        }

        // we end here when cutoff (commonLineThreshold) was not reached, but we where capturing a hunk,
        // do not render hunk till end automatically because the number of context lines might be less than the commonLineThreshold

        $contextStartOffset = $hunkCapture - $this->contextLines < 0
            ? $hunkCapture
            : $this->contextLines
        ;

        // prevent trying to write out more common lines than there are in the diff _and_
        // do not write more than configured through the context lines
        $contextEndOffset = \min($sameCount, $this->contextLines);

        $fromRange -= $sameCount;
        $toRange -= $sameCount;

        $this->writeHunk(
            $diff,
            $hunkCapture - $contextStartOffset,
            $i - $sameCount + $contextEndOffset + 1,
            $fromStart - $contextStartOffset,
            $fromRange + $contextStartOffset + $contextEndOffset,
            $toStart - $contextStartOffset,
            $toRange + $contextStartOffset + $contextEndOffset,
            $output
        );
    }

    private function writeHunk(
        array $diff,
        int $diffStartIndex,
        int $diffEndIndex,
        int $fromStart,
        int $fromRange,
        int $toStart,
        int $toRange,
        $output
    ) {
        \fwrite($output, '@@ -' . $fromStart);

        if (!$this->collapseRanges || 1 !== $fromRange) {
            \fwrite($output, ',' . $fromRange);
        }

        \fwrite($output, ' +' . $toStart);
        if (!$this->collapseRanges || 1 !== $toRange) {
            \fwrite($output, ',' . $toRange);
        }

        \fwrite($output, " @@\n");

        for ($i = $diffStartIndex; $i < $diffEndIndex; ++$i) {
            if ($diff[$i][1] === 1) { // added
                $this->changed = true;
                \fwrite($output, '+' . $diff[$i][0]);
            } elseif ($diff[$i][1] === 2) { // removed
                $this->changed = true;
                \fwrite($output, '-' . $diff[$i][0]);
            } elseif ($diff[$i][1] === 0) { // same
                \fwrite($output, ' ' . $diff[$i][0]);
            } elseif ($diff[$i][1] === self::$noNewlineAtOEFid) {
                $this->changed = true;
                \fwrite($output, $diff[$i][0]);
            }
            //} elseif ($diff[$i][1] === 3) { // custom comment inserted by PHPUnit/diff package
                //  skip
            //} else {
                //  unknown/invalid
            //}
        }
    }
}
