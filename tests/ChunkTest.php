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
    public function testHasLines(): void
    {
        $this->assertEquals([$this->line()], $this->chunk()->lines());
    }

    public function testHasStart(): void
    {
        $this->assertSame(1, $this->chunk()->start());
    }

    public function testHasStartRange(): void
    {
        $this->assertSame(2, $this->chunk()->startRange());
    }

    public function testHasEnd(): void
    {
        $this->assertSame(3, $this->chunk()->end());
    }

    public function testHasEndRange(): void
    {
        $this->assertSame(4, $this->chunk()->endRange());
    }

    public function testLinesCanBeSet(): void
    {
        $chunk = $this->chunk();
        $lines = [new Line(Line::ADDED, 'added'), new Line(Line::REMOVED, 'removed')];

        $chunk->setLines($lines);

        $this->assertSame($lines, $chunk->lines());
    }

    public function testCanBeIterated(): void
    {
        $line  = new Line;
        $chunk = new Chunk(lines: [$line]);

        foreach ($chunk as $index => $_line) {
            $this->assertSame(0, $index);
            $this->assertSame($line, $_line);
        }
    }

    private function chunk(): Chunk
    {
        return new Chunk(
            1,
            2,
            3,
            4,
            [
                $this->line(),
            ],
        );
    }

    private function line(): Line
    {
        return new Line(Line::ADDED, 'content');
    }
}
