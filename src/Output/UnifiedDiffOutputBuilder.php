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
        $old   = $this->getCommonChunks($diff, 5);
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

        $buffer = $this->header;

        if (!isset($old[$start])) {
            $buffer = $this->getDiffBufferElementNew($diff, $buffer, $start);
            ++$start;
        }

        for ($i = $start; $i < $end; $i++) {
            if (isset($old[$i])) {
                $i      = $old[$i];
                $buffer = $this->getDiffBufferElementNew($diff, $buffer, $i);
            } else {
                $buffer = $this->getDiffBufferElement($diff, $buffer, $i);
            }
        }

        return $buffer;
    }

    /**
     * Gets individual buffer element with opening.
     *
     * @param array  $diff
     * @param string $buffer
     * @param int    $diffIndex
     *
     * @return string
     */
    private function getDiffBufferElementNew(array $diff, string $buffer, int $diffIndex): string
    {
        return $this->getDiffBufferElement($diff, $buffer . "@@ @@\n", $diffIndex);
    }

    /**
     * Gets individual buffer element.
     *
     * @param array  $diff
     * @param string $buffer
     * @param int    $diffIndex
     *
     * @return string
     */
    private function getDiffBufferElement(array $diff, string $buffer, int $diffIndex): string
    {
        if ($diff[$diffIndex][1] === 1 /* ADDED */) {
            $buffer .= '+' . $diff[$diffIndex][0] . "\n";
        } elseif ($diff[$diffIndex][1] === 2 /* REMOVED */) {
            $buffer .= '-' . $diff[$diffIndex][0] . "\n";
        } else {
            $buffer .= ' ' . $diff[$diffIndex][0] . "\n";
        }

        return $buffer;
    }
}
