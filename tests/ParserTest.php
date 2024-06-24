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

use function unserialize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Utils\FileUtils;

#[CoversClass(Parser::class)]
#[UsesClass(Chunk::class)]
#[UsesClass(Diff::class)]
#[UsesClass(Line::class)]
#[Small]
final class ParserTest extends TestCase
{
    private Parser $parser;

    public static function diffProvider(): array
    {
        return [
            [
                "--- old.txt	2014-11-04 08:51:02.661868729 +0300\n+++ new.txt	2014-11-04 08:51:02.665868730 +0300\n@@ -1,3 +1,4 @@\n+2222111\n 1111111\n 1111111\n 1111111\n@@ -5,10 +6,8 @@\n 1111111\n 1111111\n 1111111\n +1121211\n 1111111\n -1111111\n -1111111\n -2222222\n 2222222\n 2222222\n 2222222\n@@ -17,5 +16,6 @@\n 2222222\n 2222222\n 2222222\n +2122212\n 2222222\n 2222222\n",
                unserialize(FileUtils::getFileContent(__DIR__ . '/fixtures/serialized_diff.bin')),
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->parser = new Parser;
    }

    public function testParse(): void
    {
        $content = FileUtils::getFileContent(__DIR__ . '/fixtures/patch.txt');

        $diffs = $this->parser->parse($content);

        $this->assertContainsOnlyInstancesOf(Diff::class, $diffs);
        $this->assertCount(1, $diffs);

        $chunks = $diffs[0]->chunks();
        $this->assertContainsOnlyInstancesOf(Chunk::class, $chunks);

        $this->assertCount(1, $chunks);

        $this->assertSame(20, $chunks[0]->start());
        $this->assertCount(4, $chunks[0]->lines());
    }

    public function testParseWithMultipleChunks(): void
    {
        $content = FileUtils::getFileContent(__DIR__ . '/fixtures/patch2.txt');

        $diffs = $this->parser->parse($content);

        $this->assertCount(1, $diffs);

        $chunks = $diffs[0]->chunks();
        $this->assertCount(3, $chunks);

        $this->assertSame(20, $chunks[0]->start());
        $this->assertSame(320, $chunks[1]->start());
        $this->assertSame(600, $chunks[2]->start());

        $this->assertCount(5, $chunks[0]->lines());
        $this->assertCount(5, $chunks[1]->lines());
        $this->assertCount(4, $chunks[2]->lines());
    }

    public function testParseWithSpacesInFileNames(): void
    {
        $content = <<<'PATCH'
diff --git a/Foo Bar.txt b/Foo Bar.txt
index abcdefg..abcdefh 100644
--- a/Foo Bar.txt
+++ b/Foo Bar.txt
@@ -20,4 +20,5 @@ class Foo
     const ONE = 1;
     const TWO = 2;
+    const THREE = 3;
     const FOUR = 4;

PATCH;

        $diffs = $this->parser->parse($content);

        $this->assertEquals('a/Foo Bar.txt', $diffs[0]->from());
        $this->assertEquals('b/Foo Bar.txt', $diffs[0]->to());
    }

    public function testParseWithSpacesInFileNamesAndTimesamp(): void
    {
        $content = <<<'PATCH'
diff --git a/Foo Bar.txt b/Foo Bar.txt
index abcdefg..abcdefh 100644
--- "a/Foo Bar.txt"  2020-10-02 13:31:52.938811371 +0200
+++ "b/Foo Bar.txt"  2020-10-02 13:31:50.022792064 +0200
@@ -20,4 +20,5 @@ class Foo
     const ONE = 1;
     const TWO = 2;
+    const THREE = 3;
     const FOUR = 4;
PATCH;

        $diffs = $this->parser->parse($content);

        $this->assertEquals('a/Foo Bar.txt', $diffs[0]->from());
        $this->assertEquals('b/Foo Bar.txt', $diffs[0]->to());
    }

    public function testParseWithRemovedLines(): void
    {
        $content = <<<'END'
diff --git a/Test.txt b/Test.txt
index abcdefg..abcdefh 100644
--- a/Test.txt
+++ b/Test.txt
@@ -49,9 +49,8 @@
 A
-B
END;
        $diffs = $this->parser->parse($content);
        $this->assertContainsOnlyInstancesOf(Diff::class, $diffs);
        $this->assertCount(1, $diffs);

        $chunks = $diffs[0]->chunks();

        $this->assertContainsOnlyInstancesOf(Chunk::class, $chunks);
        $this->assertCount(1, $chunks);

        $chunk = $chunks[0];
        $this->assertSame(49, $chunk->start());
        $this->assertSame(49, $chunk->end());
        $this->assertSame(9, $chunk->startRange());
        $this->assertSame(8, $chunk->endRange());

        $lines = $chunk->lines();
        $this->assertContainsOnlyInstancesOf(Line::class, $lines);
        $this->assertCount(2, $lines);

        $line = $lines[0];
        $this->assertSame('A', $line->content());
        $this->assertSame(Line::UNCHANGED, $line->type());

        $line = $lines[1];
        $this->assertSame('B', $line->content());
        $this->assertSame(Line::REMOVED, $line->type());
    }

    public function testParseDiffForMulitpleFiles(): void
    {
        $content = <<<'END'
diff --git a/Test.txt b/Test.txt
index abcdefg..abcdefh 100644
--- a/Test.txt
+++ b/Test.txt
@@ -1,3 +1,2 @@
 A
-B

diff --git a/Test123.txt b/Test123.txt
index abcdefg..abcdefh 100644
--- a/Test2.txt
+++ b/Test2.txt
@@ -1,2 +1,3 @@
 A
+B
END;
        $diffs = $this->parser->parse($content);
        $this->assertCount(2, $diffs);

        $diff = $diffs[0];
        $this->assertSame('a/Test.txt', $diff->from());
        $this->assertSame('b/Test.txt', $diff->to());
        $this->assertCount(1, $diff->chunks());

        $diff = $diffs[1];
        $this->assertSame('a/Test2.txt', $diff->from());
        $this->assertSame('b/Test2.txt', $diff->to());
        $this->assertCount(1, $diff->chunks());
    }

    public function testParseWithRange(): void
    {
        $content = <<<'END'
diff --git a/Test.txt b/Test.txt
index abcdefg..abcdefh 100644
--- a/Test.txt
+++ b/Test.txt
@@ -49,0 +49,0 @@
@@ -50 +50 @@
 A
-B
END;
        $diffs = $this->parser->parse($content);
        $this->assertContainsOnlyInstancesOf(Diff::class, $diffs);
        $this->assertCount(1, $diffs);

        $chunks = $diffs[0]->chunks();

        $this->assertContainsOnlyInstancesOf(Chunk::class, $chunks);
        $this->assertCount(2, $chunks);

        $chunk = $chunks[0];
        $this->assertSame(49, $chunk->start());
        $this->assertSame(49, $chunk->end());
        $this->assertSame(0, $chunk->startRange());
        $this->assertSame(0, $chunk->endRange());

        $chunk = $chunks[1];
        $this->assertSame(50, $chunk->start());
        $this->assertSame(50, $chunk->end());
        $this->assertSame(1, $chunk->startRange());
        $this->assertSame(1, $chunk->endRange());
    }

    /**
     * @param list<Diff> $expected
     */
    #[DataProvider('diffProvider')]
    public function testParser(string $diff, array $expected): void
    {
        $result = $this->parser->parse($diff);

        $this->assertEquals($expected, $result);
    }
}
