<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\DecimalObject\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

/**
 * The input cannot be read as a decimal number.
 */
class InvalidNumberException extends BaseInvalidArgumentException implements DecimalException
{
}
