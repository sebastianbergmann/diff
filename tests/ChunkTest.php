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

#[CoversClass(Chunk::class)]
#[UsesClass(Line::class)]
#[Small]
final class ChunkTest extends TestCase
{
    private Chunk $chunk;

    protected function setUp(): void
    {
        $this->chunk = new Chunk;
    }

    public function testHasInitiallyNoLines(): void
    {
        $this->assertSame([], $this->chunk->lines());
        $this->assertSame([], $this->chunk->getLines());
    }

    public function testCanBeCreatedWithoutArguments(): void
    {
        $this->assertInstanceOf(Chunk::class, $this->chunk);
    }

    public function testStartCanBeRetrieved(): void
    {
        $this->assertSame(0, $this->chunk->start());
        $this->assertSame(0, $this->chunk->getStart());
    }

    public function testStartRangeCanBeRetrieved(): void
    {
        $this->assertSame(1, $this->chunk->startRange());
        $this->assertSame(1, $this->chunk->getStartRange());
    }

    public function testEndCanBeRetrieved(): void
    {
        $this->assertSame(0, $this->chunk->end());
        $this->assertSame(0, $this->chunk->getEnd());
    }

    public function testEndRangeCanBeRetrieved(): void
    {
        $this->assertSame(1, $this->chunk->endRange());
        $this->assertSame(1, $this->chunk->getEndRange());
    }

    public function testLinesCanBeRetrieved(): void
    {
        $this->assertSame([], $this->chunk->lines());
        $this->assertSame([], $this->chunk->getLines());
    }

    public function testLinesCanBeSet(): void
    {
        $lines = [new Line(Line::ADDED, 'added'), new Line(Line::REMOVED, 'removed')];

        $this->chunk->setLines($lines);

        $this->assertSame($lines, $this->chunk->lines());
        $this->assertSame($lines, $this->chunk->getLines());
    }
}
