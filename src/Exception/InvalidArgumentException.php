<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\DecimalObject\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

/**
 * An argument other than the number itself is not usable, such as a negative scale or an unknown rounding mode.
 */
class InvalidArgumentException extends BaseInvalidArgumentException implements DecimalException
{
}
