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

#[CoversClass(MemoryEfficientLongestCommonSubsequenceCalculator::class)]
#[Small]
final class MemoryEfficientImplementationTest extends LongestCommonSubsequenceTestCase
{
    protected function createImplementation(): LongestCommonSubsequenceCalculator
    {
        return new MemoryEfficientLongestCommonSubsequenceCalculator;
    }
}
