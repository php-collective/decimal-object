<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

/**
 * Copy of the enum PHP 8.4 ships, for static analysis on PHP 8.2 and 8.3 only. Never loaded at runtime.
 */
enum RoundingMode
{
    case HalfAwayFromZero;
    case HalfTowardsZero;
    case HalfEven;
    case HalfOdd;
    case TowardsZero;
    case AwayFromZero;
    case NegativeInfinity;
    case PositiveInfinity;
}
