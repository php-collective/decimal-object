<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\DecimalObject\Exception;

use RuntimeException;

/**
 * The result does not fit the requested scale and no rounding mode was given.
 */
class RoundingNecessaryException extends RuntimeException implements DecimalException
{
}
