<?php declare(strict_types=1);

namespace SebastianBergmann\Diff;

use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

final class HangingDemoTest extends TestCase
{
    public function test(): void
    {
        if ('' !== (string) getenv('CI')) {
            $this->markTestSkipped('CI env is defined');
        }

        [$input1, $input2] = $this->generateInputs();

        fprintf(STDERR, "Before\n");

        $differ = new Differ(new UnifiedDiffOutputBuilder);
        $differ->diff($input1, $input2);

        fprintf(STDERR, "After\n");
    }

    private function generateInputs(): array
    {
        // Generate two files with 100k lines and 7k edits

        srand(1);

        $buf1 = [];
        $buf2 = [];

        for ($i = 0; $i < 100000; $i++) {
            $line = $this->generateLine();
            $buf1[] = $line;
            if (rand(0,14) === 0) {
                $buf2[] = $this->generateLine();
            } else {
                $buf2[] = $line;
            }
        }

        return [
            implode("\n", $buf1),
            implode("\n", $buf2),
        ];
    }

    private function generateLine(): string
    {
        $buf = [];
        for ($j = 0; $j < 30; $j++) {
            $buf[] = chr(rand(97, 122));
        }
        return implode('', $buf);
    }
}
