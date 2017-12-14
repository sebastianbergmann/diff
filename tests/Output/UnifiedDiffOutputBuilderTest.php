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

use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;

/**
 * @covers SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder
 *
 * @uses SebastianBergmann\Diff\Differ
 * @uses SebastianBergmann\Diff\Output\AbstractChunkOutputBuilder
 * @uses SebastianBergmann\Diff\TimeEfficientLongestCommonSubsequenceCalculator
 */
final class UnifiedDiffOutputBuilderTest extends TestCase
{
    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @param string $header
     * @dataProvider headerProvider
     */
    public function testCustomHeaderCanBeUsed(string $expected, string $from, string $to, string $header)
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder($header));

        $this->assertSame(
            $expected,
            $differ->diff($from, $to)
        );
    }

    public function headerProvider()
    {
        return [
            [
                "CUSTOM HEADER\n@@ @@\n-a\n+b\n",
                'a',
                'b',
                'CUSTOM HEADER',
            ],
            [
                "CUSTOM HEADER\n@@ @@\n-a\n+b\n",
                'a',
                'b',
                "CUSTOM HEADER\n",
            ],
            [
                "CUSTOM HEADER\n\n@@ @@\n-a\n+b\n",
                'a',
                'b',
                "CUSTOM HEADER\n\n",
            ],
            [
                "@@ @@\n-a\n+b\n",
                'a',
                'b',
                '',
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $from
     * @param string $to
     * @dataProvider provideDiffWithLineNumbers
     */
    public function testDiffWithLineNumbers($expected, $from, $to)
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder("--- Original\n+++ New\n", true));
        $this->assertSame($expected, $differ->diff($from, $to));
    }

    public function provideDiffWithLineNumbers(): array
    {
        return [
            'diff line 1 non_patch_compat' => [
                '--- Original
+++ New
@@ -1 +1 @@
-AA
+BA
',
                'AA',
                'BA',
            ],
            'diff line +1 non_patch_compat' => [
                '--- Original
+++ New
@@ -1 +1,2 @@
-AZ
+
+B
',
                'AZ',
                "\nB",
            ],
            'diff line -1 non_patch_compat' => [
                '--- Original
+++ New
@@ -1,2 +1 @@
-
-AF
+B
',
                "\nAF",
                'B',
            ],
            'II non_patch_compat' => [
                '--- Original
+++ New
@@ -1,2 +1,0 @@
-
-
'
                ,
                "\n\nA\n1",
                "A\n1",
            ],
            'diff last line II - no trailing linebreak non_patch_compat' => [
                '--- Original
+++ New
@@ -8 +8 @@
-E
+B
',
                "A\n\n\n\n\n\n\nE",
                "A\n\n\n\n\n\n\nB",
            ],
            [
                "--- Original\n+++ New\n@@ -1,2 +1 @@\n \n-\n",
                "\n\n",
                "\n",
            ],
            'diff line endings non_patch_compat' => [
                "--- Original\n+++ New\n@@ -1 +1 @@\n #Warning: Strings contain different line endings!\n-<?php\r\n+<?php\n",
                "<?php\r\n",
                "<?php\n",
            ],
            'same non_patch_compat' => [
                '--- Original
+++ New
',
                "AT\n",
                "AT\n",
            ],
            [
                '--- Original
+++ New
@@ -1 +1 @@
-b
+a
',
                "b\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n",
                "a\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n",
            ],
            'diff line @1' => [
                '--- Original
+++ New
@@ -1,2 +1,2 @@
 ' . '
-AG
+B
',
                "\nAG\n",
                "\nB\n",
            ],
            'same multiple lines' => [
                '--- Original
+++ New
@@ -1,3 +1,3 @@
 ' . '
 ' . '
-V
+B
'

                ,
                "\n\nV\nC213",
                "\n\nB\nC213",
            ],
            'diff last line I' => [
                '--- Original
+++ New
@@ -8 +8 @@
-E
+B
',
                "A\n\n\n\n\n\n\nE\n",
                "A\n\n\n\n\n\n\nB\n",
            ],
            'diff line middle' => [
                '--- Original
+++ New
@@ -8 +8 @@
-X
+Z
',
                "A\n\n\n\n\n\n\nX\n\n\n\n\n\n\nAY",
                "A\n\n\n\n\n\n\nZ\n\n\n\n\n\n\nAY",
            ],
            'diff last line III' => [
                '--- Original
+++ New
@@ -15 +15 @@
-A
+B
',
                "A\n\n\n\n\n\n\nA\n\n\n\n\n\n\nA\n",
                "A\n\n\n\n\n\n\nA\n\n\n\n\n\n\nB\n",
            ],
            [
                '--- Original
+++ New
@@ -1,7 +1,7 @@
 A
-B
+B1
 D
 E
 EE
 F
-G
+G1
',
                "A\nB\nD\nE\nEE\nF\nG\nH",
                "A\nB1\nD\nE\nEE\nF\nG1\nH",
            ],
            [
                '--- Original
+++ New
@@ -1 +1,2 @@
 Z
+
@@ -10 +11 @@
-i
+x
',
                'Z
a
b
c
d
e
f
g
h
i
j',
                'Z

a
b
c
d
e
f
g
h
x
j',
            ],
            [
                '--- Original
+++ New
@@ -1,5 +1,3 @@
-
-a
+b
 A
-a
-
+b
',
                "\na\nA\na\n\n\nA",
                "b\nA\nb\n\nA",
            ],
            [
                <<<EOF
--- Original
+++ New
@@ -1,4 +1,2 @@
-
-
 a
-b
+p
@@ -12 +10 @@
-j
+w

EOF
                ,
                "\n\na\nb\nc\nd\ne\nf\ng\nh\ni\nj\nk",
                "a\np\nc\nd\ne\nf\ng\nh\ni\nw\nk",
            ],
            [
                '--- Original
+++ New
@@ -11 +11 @@
-A
+C
',
                "E\n\n\n\n\nB\n\n\n\n\nA\n\n\n\n\n\n\n\n\nD1",
                "E\n\n\n\n\nB\n\n\n\n\nC\n\n\n\n\n\n\n\n\nD1",
            ],
            [
                '--- Original
+++ New
@@ -8 +8 @@
-Z
+U
@@ -15 +15 @@
-X
+V
@@ -22 +22 @@
-Y
+W
@@ -29 +29 @@
-W
+X
@@ -36 +36 @@
-V
+Y
@@ -43 +43 @@
-U
+Z
',
                "\n\n\n\n\n\n\nZ\n\n\n\n\n\n\nX\n\n\n\n\n\n\nY\n\n\n\n\n\n\nW\n\n\n\n\n\n\nV\n\n\n\n\n\n\nU\n",
                "\n\n\n\n\n\n\nU\n\n\n\n\n\n\nV\n\n\n\n\n\n\nW\n\n\n\n\n\n\nX\n\n\n\n\n\n\nY\n\n\n\n\n\n\nZ\n",
            ],
            [
                <<<EOF
--- Original
+++ New
@@ -1,2 +1,2 @@
 a
-b
+p
@@ -10 +10 @@
-j
+w

EOF
                ,
                "a\nb\nc\nd\ne\nf\ng\nh\ni\nj\nk",
                "a\np\nc\nd\ne\nf\ng\nh\ni\nw\nk",
            ],
            [
                <<<EOF
--- Original
+++ New
@@ -1 +1 @@
-A
+B

EOF
                ,
                "A\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1",
                "B\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1\n1",
            ],
            [
                "--- Original\n+++ New\n@@ -7 +7 @@\n-X\n+B\n",
                "A\nA\nA\nA\nA\nA\nX\nC\nC\nC\nC\nC\nC",
                "A\nA\nA\nA\nA\nA\nB\nC\nC\nC\nC\nC\nC",
            ],
        ];
    }
}
