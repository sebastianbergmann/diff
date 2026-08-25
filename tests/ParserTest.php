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

use function count;
use function is_array;
use function unserialize;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\Diff\Utils\FileUtils;

#[CoversClass(Parser::class)]
#[UsesClass(Chunk::class)]
#[UsesClass(Diff::class)]
#[UsesClass(Line::class)]
#[Small]
final class ParserTest extends TestCase
{
    private Parser $parser;

    /**
     * @return array<
     *     array{
     *         0: string,
     *         1: array{0: Diff},
     *     },
     * >
     */
    public static function diffProvider(): array
    {
        $diff = unserialize(FileUtils::getFileContent(__DIR__ . '/fixtures/serialized_diff.bin'));

        if (!is_array($diff) || !isset($diff[0]) || !($diff[0] instanceof Diff)) {
            throw new RuntimeException('Invalid serialized diff fixture.');
        }

        return [
            [
                "--- old.txt	2014-11-04 08:51:02.661868729 +0300\n+++ new.txt	2014-11-04 08:51:02.665868730 +0300\n@@ -1,3 +1,4 @@\n+2222111\n 1111111\n 1111111\n 1111111\n@@ -5,10 +6,8 @@\n 1111111\n 1111111\n 1111111\n +1121211\n 1111111\n -1111111\n -1111111\n -2222222\n 2222222\n 2222222\n 2222222\n@@ -17,5 +16,6 @@\n 2222222\n 2222222\n 2222222\n +2122212\n 2222222\n 2222222\n",
                [$diff[0]],
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

        $diff   = $diffs[0] ?? throw new LogicException('Expected one diff.');
        $chunks = $diff->chunks();
        $this->assertContainsOnlyInstancesOf(Chunk::class, $chunks);

        $this->assertCount(1, $chunks);

        $chunk = $chunks[0] ?? throw new LogicException('Expected one chunk.');
        $this->assertSame(20, $chunk->start());
        $this->assertCount(4, $chunk->lines());
    }

    public function testParseWithMultipleChunks(): void
    {
        $content = FileUtils::getFileContent(__DIR__ . '/fixtures/patch2.txt');

        $diffs = $this->parser->parse($content);

        $this->assertCount(1, $diffs);

        $diff   = $diffs[0] ?? throw new LogicException('Expected one diff.');
        $chunks = $diff->chunks();
        $this->assertCount(3, $chunks);

        $chunkOne   = $chunks[0] ?? throw new LogicException('Expected first chunk.');
        $chunkTwo   = $chunks[1] ?? throw new LogicException('Expected second chunk.');
        $chunkThree = $chunks[2] ?? throw new LogicException('Expected third chunk.');

        $this->assertSame(20, $chunkOne->start());
        $this->assertSame(320, $chunkTwo->start());
        $this->assertSame(600, $chunkThree->start());

        $this->assertCount(5, $chunkOne->lines());
        $this->assertCount(5, $chunkTwo->lines());
        $this->assertCount(4, $chunkThree->lines());
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

        $diff = $diffs[0] ?? throw new LogicException('Expected one diff.');
        $this->assertEquals('a/Foo Bar.txt', $diff->from());
        $this->assertEquals('b/Foo Bar.txt', $diff->to());
    }

    public function testParseWithSpacesInFileNamesAndTimestamp(): void
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

        $diff = $diffs[0] ?? throw new LogicException('Expected one diff.');
        $this->assertEquals('a/Foo Bar.txt', $diff->from());
        $this->assertEquals('b/Foo Bar.txt', $diff->to());
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

        $diff   = $diffs[0] ?? throw new LogicException('Expected one diff.');
        $chunks = $diff->chunks();

        $this->assertContainsOnlyInstancesOf(Chunk::class, $chunks);
        $this->assertCount(1, $chunks);

        $chunk = $chunks[0] ?? throw new LogicException('Expected one chunk.');
        $this->assertSame(49, $chunk->start());
        $this->assertSame(49, $chunk->end());
        $this->assertSame(9, $chunk->startRange());
        $this->assertSame(8, $chunk->endRange());

        $lines = $chunk->lines();
        $this->assertContainsOnlyInstancesOf(Line::class, $lines);
        $this->assertCount(2, $lines);

        $line = $lines[0] ?? throw new LogicException('Expected first line.');
        $this->assertSame('A', $line->content());
        $this->assertSame(Line::UNCHANGED, $line->type());

        $line = $lines[1] ?? throw new LogicException('Expected second line.');
        $this->assertSame('B', $line->content());
        $this->assertSame(Line::REMOVED, $line->type());
    }

    public function testParseDiffForMultipleFiles(): void
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

        $diff = $diffs[0] ?? throw new LogicException('Expected first diff.');
        $this->assertSame('a/Test.txt', $diff->from());
        $this->assertSame('b/Test.txt', $diff->to());
        $this->assertCount(1, $diff->chunks());

        $diff = $diffs[1] ?? throw new LogicException('Expected second diff.');
        $this->assertSame('a/Test2.txt', $diff->from());
        $this->assertSame('b/Test2.txt', $diff->to());
        $this->assertCount(1, $diff->chunks());
    }

    public function testParseDoesNotMistakeContentLinesForHeaderLines(): void
    {
        $content = <<<'END'
diff --git a/migration.sql b/migration.sql
index abcdefg..abcdefh 100644
--- a/migration.sql
+++ b/migration.sql
@@ -1,5 +1,6 @@
 -- header comment
+-- a comment added by this change
+-- b comment added by this change
-++ a stray marker removed
--- a/foo gone
+++ b/foo added
--- x removed
+++ y added
 SELECT 1;
END;
        $diffs = $this->parser->parse($content);
        $this->assertCount(1, $diffs);

        $diff   = $diffs[0] ?? throw new LogicException('Expected one diff.');
        $chunks = $diff->chunks();
        $this->assertCount(1, $chunks);

        $chunk = $chunks[0] ?? throw new LogicException('Expected one chunk.');
        $lines = $chunk->lines();
        $this->assertContainsOnlyInstancesOf(Line::class, $lines);

        $expected = [
            ['-- header comment', Line::UNCHANGED],
            ['-- a comment added by this change', Line::ADDED],
            ['-- b comment added by this change', Line::ADDED],
            ['++ a stray marker removed', Line::REMOVED],
            ['-- a/foo gone', Line::REMOVED],
            ['++ b/foo added', Line::ADDED],
            ['-- x removed', Line::REMOVED],
            ['++ y added', Line::ADDED],
            ['SELECT 1;', Line::UNCHANGED],
        ];

        $this->assertCount(count($expected), $lines);

        foreach ($expected as $i => [$expectedContent, $expectedType]) {
            $line = $lines[$i] ?? throw new LogicException('Expected line #' . $i . '.');

            $this->assertSame($expectedContent, $line->content());
            $this->assertSame($expectedType, $line->type());
        }
    }

    public function testParseContinuesWithNextFileAfterChunkWithFewerLinesThanAnnounced(): void
    {
        $content = <<<'END'
diff --git a/first.txt b/first.txt
index abcdefg..abcdefh 100644
--- a/first.txt
+++ b/first.txt
@@ -1,3 +1,3 @@
 shared
-removed
+added
diff --git a/second.txt b/second.txt
index abcdefg..abcdefh 100644
--- a/second.txt
+++ b/second.txt
@@ -1 +1 @@
-old
+new
END;
        $diffs = $this->parser->parse($content);
        $this->assertCount(2, $diffs);

        $diff = $diffs[0] ?? throw new LogicException('Expected first diff.');
        $this->assertSame('a/first.txt', $diff->from());
        $this->assertSame('b/first.txt', $diff->to());

        $chunks = $diff->chunks();
        $this->assertCount(1, $chunks);

        $chunk = $chunks[0] ?? throw new LogicException('Expected one chunk.');
        $this->assertCount(3, $chunk->lines());

        $diff = $diffs[1] ?? throw new LogicException('Expected second diff.');
        $this->assertSame('a/second.txt', $diff->from());
        $this->assertSame('b/second.txt', $diff->to());

        $chunks = $diff->chunks();
        $this->assertCount(1, $chunks);

        $chunk = $chunks[0] ?? throw new LogicException('Expected one chunk.');
        $this->assertCount(2, $chunk->lines());
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

        $diff   = $diffs[0] ?? throw new LogicException('Expected one diff.');
        $chunks = $diff->chunks();

        $this->assertContainsOnlyInstancesOf(Chunk::class, $chunks);
        $this->assertCount(2, $chunks);

        $chunk = $chunks[0] ?? throw new LogicException('Expected first chunk.');
        $this->assertSame(49, $chunk->start());
        $this->assertSame(49, $chunk->end());
        $this->assertSame(0, $chunk->startRange());
        $this->assertSame(0, $chunk->endRange());

        $chunk = $chunks[1] ?? throw new LogicException('Expected second chunk.');
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
