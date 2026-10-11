<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\DecimalObject\Exception;

use DomainException;

/**
 * The operation is not defined for negative numbers.
 */
class NegativeNumberException extends DomainException implements DecimalException
{
}
