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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Diff::class)]
#[UsesClass(Chunk::class)]
#[Small]
final class DiffTest extends TestCase
{
    public function testGettersAfterConstructionWithDefault(): void
    {
        $from = 'line1a';
        $to   = 'line2a';
        $diff = new Diff($from, $to);

        $this->assertSame($from, $diff->from());
        $this->assertSame($to, $diff->to());
        $this->assertSame([], $diff->chunks(), 'Expect chunks to be default value "array()".');
    }

    public function testGettersAfterConstructionWithChunks(): void
    {
        $from   = 'line1b';
        $to     = 'line2b';
        $chunks = [new Chunk, new Chunk(2, 3)];

        $diff = new Diff($from, $to, $chunks);

        $this->assertSame($from, $diff->from());
        $this->assertSame($to, $diff->to());
        $this->assertSame($chunks, $diff->chunks(), 'Expect chunks to be passed value.');
    }

    public function testSetChunksAfterConstruction(): void
    {
        $diff = new Diff('line1c', 'line2c');

        $this->assertSame([], $diff->chunks(), 'Expect chunks to be default value "array()".');

        $chunks = [new Chunk, new Chunk(2, 3)];
        $diff->setChunks($chunks);

        $this->assertSame($chunks, $diff->chunks(), 'Expect chunks to be passed value.');
    }

    public function testCanBeIterated(): void
    {
        $chunk = new Chunk;
        $diff  = new Diff('from', 'to', [$chunk]);

        foreach ($diff as $index => $_chunk) {
            $this->assertSame(0, $index);
            $this->assertSame($chunk, $_chunk);
        }
    }
}
