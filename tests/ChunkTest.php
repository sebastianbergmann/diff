<?php
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\Diff;

use PHPUnit\Framework\TestCase;

/**
 * @covers SebastianBergmann\Diff\Chunk
 *
 * @internal
 */
final class ChunkTest extends TestCase
{
    /**
     * @var Chunk
     */
    private $chunk;

    protected function setUp()
    {
        $this->chunk = new Chunk;
    }

    public function testCanBeCreatedWithoutArguments()
    {
        $this->assertInstanceOf(Chunk::class, $this->chunk);
    }

    public function testStartCanBeRetrieved()
    {
        $this->assertEquals(0, $this->chunk->getStart());
    }

    public function testStartRangeCanBeRetrieved()
    {
        $this->assertEquals(1, $this->chunk->getStartRange());
    }

    public function testEndCanBeRetrieved()
    {
        $this->assertEquals(0, $this->chunk->getEnd());
    }

    public function testEndRangeCanBeRetrieved()
    {
        $this->assertEquals(1, $this->chunk->getEndRange());
    }

    public function testLinesCanBeRetrieved()
    {
        $this->assertEquals([], $this->chunk->getLines());
    }

    public function testLinesCanBeSet()
    {
        $this->assertEquals([], $this->chunk->getLines());

        $testValue = ['line0', 'line1'];
        $this->chunk->setLines($testValue);
        $this->assertEquals($testValue, $this->chunk->getLines());
    }
}
