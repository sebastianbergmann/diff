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

use function array_reverse;
use function count;
use function mt_rand;
use function mt_srand;
use function range;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(MyersDiff::class)]
#[Small]
final class MyersDiffTest extends TestCase
{
    public function testBothEmpty(): void
    {
        $this->assertSame([], (new MyersDiff)->calculate([], []));
    }

    public function testFromEmpty(): void
    {
        $this->assertSame(
            [['a', Differ::ADDED], ['b', Differ::ADDED]],
            (new MyersDiff)->calculate([], ['a', 'b']),
        );
    }

    public function testToEmpty(): void
    {
        $this->assertSame(
            [['a', Differ::REMOVED], ['b', Differ::REMOVED]],
            (new MyersDiff)->calculate(['a', 'b'], []),
        );
    }

    public function testIdenticalSequences(): void
    {
        foreach ([1, 2, 3, 100, 1000] as $size) {
            $seq    = range(1, $size);
            $result = (new MyersDiff)->calculate($seq, $seq);

            $this->assertCount($size, $result);

            $expected = [];

            foreach ($seq as $value) {
                $expected[] = [$value, Differ::OLD];
            }

            $this->assertSame($expected, $result);
        }
    }

    public function testCompletelyDistinctSequences(): void
    {
        $result = (new MyersDiff)->calculate(['A', 'B', 'C'], ['D', 'E', 'F']);

        $this->assertSame(
            [
                ['A', Differ::REMOVED],
                ['B', Differ::REMOVED],
                ['C', Differ::REMOVED],
                ['D', Differ::ADDED],
                ['E', Differ::ADDED],
                ['F', Differ::ADDED],
            ],
            $result,
        );
    }

    public function testSingleSubstitutionInMiddle(): void
    {
        $result = (new MyersDiff)->calculate(['a', 'b', 'c'], ['a', 'd', 'c']);

        $this->assertSame(
            [
                ['a', Differ::OLD],
                ['b', Differ::REMOVED],
                ['d', Differ::ADDED],
                ['c', Differ::OLD],
            ],
            $result,
        );
    }

    public function testStrictTypeComparison(): void
    {
        $result = (new MyersDiff)->calculate(['5'], ['05']);

        $this->assertSame(
            [['5', Differ::REMOVED], ['05', Differ::ADDED]],
            $result,
        );
    }

    public function testEditScriptRoundTrip(): void
    {
        mt_srand(42);

        for ($trial = 0; $trial < 200; $trial++) {
            $n        = mt_rand(0, 40);
            $m        = mt_rand(0, 40);
            $alphabet = ['a', 'b', 'c', 'd', 'e'];
            $from     = [];
            $to       = [];

            for ($i = 0; $i < $n; $i++) {
                $from[] = $alphabet[mt_rand(0, 4)];
            }

            for ($i = 0; $i < $m; $i++) {
                $to[] = $alphabet[mt_rand(0, 4)];
            }

            $script      = (new MyersDiff)->calculate($from, $to);
            $rebuiltFrom = [];
            $rebuiltTo   = [];

            foreach ($script as [$token, $op]) {
                if ($op === Differ::OLD) {
                    $rebuiltFrom[] = $token;
                    $rebuiltTo[]   = $token;
                } elseif ($op === Differ::REMOVED) {
                    $rebuiltFrom[] = $token;
                } elseif ($op === Differ::ADDED) {
                    $rebuiltTo[] = $token;
                }
            }

            $this->assertSame($from, $rebuiltFrom, "trial {$trial}");
            $this->assertSame($to, $rebuiltTo, "trial {$trial}");
        }
    }

    public function testReversedSequenceProducesOptimalEditDistance(): void
    {
        $from   = range(1, 8);
        $to     = array_reverse($from);
        $script = (new MyersDiff)->calculate($from, $to);

        // For reversed sequences of length n, the LCS has length 1, so the
        // edit script must contain (n - 1) removes and (n - 1) adds.
        $removes = $adds = $olds = 0;

        foreach ($script as [, $op]) {
            if ($op === Differ::REMOVED) {
                $removes++;
            } elseif ($op === Differ::ADDED) {
                $adds++;
            } else {
                $olds++;
            }
        }

        $n = count($from);
        $this->assertSame($n - 1, $removes);
        $this->assertSame($n - 1, $adds);
        $this->assertSame(1, $olds);
    }
}
