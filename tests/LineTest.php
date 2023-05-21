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
use PHPUnit\Framework\TestCase;

#[CoversClass(Line::class)]
#[Small]
final class LineTest extends TestCase
{
    public function testCanBeOfTypeAdded(): void
    {
        $this->assertSame(Line::ADDED, $this->added()->type());
        $this->assertSame(Line::ADDED, $this->added()->getType());

        $this->assertTrue($this->added()->isAdded());
        $this->assertFalse($this->added()->isRemoved());
        $this->assertFalse($this->added()->isUnchanged());
    }

    public function testCanBeOfTypeRemoved(): void
    {
        $this->assertSame(Line::REMOVED, $this->removed()->type());
        $this->assertSame(Line::REMOVED, $this->removed()->getType());

        $this->assertTrue($this->removed()->isRemoved());
        $this->assertFalse($this->removed()->isAdded());
        $this->assertFalse($this->removed()->isUnchanged());
    }

    public function testCanBeOfTypeUnchanged(): void
    {
        $this->assertSame(Line::UNCHANGED, $this->unchanged()->type());
        $this->assertSame(Line::UNCHANGED, $this->unchanged()->getType());

        $this->assertTrue($this->unchanged()->isUnchanged());
        $this->assertFalse($this->unchanged()->isAdded());
        $this->assertFalse($this->unchanged()->isRemoved());
    }

    public function testHasContent(): void
    {
        $this->assertSame('content', $this->added()->content());
        $this->assertSame('content', $this->added()->getContent());
    }

    private function added(): Line
    {
        return new Line(Line::ADDED, 'content');
    }

    private function removed(): Line
    {
        return new Line(Line::REMOVED, 'content');
    }

    private function unchanged(): Line
    {
        return new Line(Line::UNCHANGED, 'content');
    }
}
