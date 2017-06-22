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

/**
 * Builds a diff string representation in unified diff format in chunks.
 */
final class UnifiedDiffOutputBuilder extends AbstractChunkOutputBuilder
{
    /**
     * @var string
     */
    private $header;

    public function __construct(string $header = "--- Original\n+++ New\n")
    {
        $this->header = $header;
    }

    public function getDiff(array $diff): string
    {
        $buffer = \fopen('php://memory', 'r+b');

        if ('' !== $this->header) {
            \fwrite($buffer, $this->header);
            if ("\n" !== \substr($this->header, -1, 1)) {
                \fwrite($buffer, "\n");
            }
        }

        $this->writeDiffChunked($buffer, $diff, $this->getCommonChunks($diff));

        $diff = \stream_get_contents($buffer, -1, 0);

        \fclose($buffer);

        return $diff;
    }

    // `old` is an array with key => value pairs . Each pair represents a start and end index of `diff`
    // of a list of elements all containing `same` (0) entries.
    private function writeDiffChunked($output, array $diff, array $old)
    {
        $start = isset($old[0]) ? $old[0] : 0;
        $end   = \count($diff);

        if (\count($old)) {
            \end($old);
            $tmp = \key($old);
            \reset($old);
            if ($old[$tmp] === $end - 1) {
                $end = $tmp;
            }
        }

        if (!isset($old[$start])) {
            $this->writeDiffBufferElementNew($diff, $output, $start);
            ++$start;
        }

        for ($i = $start; $i < $end; $i++) {
            if (isset($old[$i])) {
                $i = $old[$i];
                $this->writeDiffBufferElementNew($diff, $output, $i);
            } else {
                $this->writeDiffBufferElement($diff, $output, $i);
            }
        }
    }

    /**
     * Gets individual buffer element with opening.
     *
     * @param array    $diff
     * @param resource $buffer
     * @param int      $diffIndex
     */
    private function writeDiffBufferElementNew(array $diff, $buffer, int $diffIndex)
    {
        \fwrite($buffer, "@@ @@\n");

        $this->writeDiffBufferElement($diff, $buffer, $diffIndex);
    }

    /**
     * Gets individual buffer element.
     *
     * @param array    $diff
     * @param resource $buffer
     * @param int      $diffIndex
     */
    private function writeDiffBufferElement(array $diff, $buffer, int $diffIndex)
    {
        if ($diff[$diffIndex][1] === 1 /* ADDED */) {
            \fwrite($buffer, '+' . $diff[$diffIndex][0] . "\n");
        } elseif ($diff[$diffIndex][1] === 2 /* REMOVED */) {
            \fwrite($buffer, '-' . $diff[$diffIndex][0] . "\n");
        } else { /* Not changed (OLD) 0 or Warning 3 */
            \fwrite($buffer, ' ' . $diff[$diffIndex][0] . "\n");
        }
    }
}
