<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\DecimalObject\Exception;

use OverflowException as BaseOverflowException;

/**
 * The value does not fit the native type it should be converted to.
 */
class OverflowException extends BaseOverflowException implements DecimalException
{
}
