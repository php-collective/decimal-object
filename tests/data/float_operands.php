<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

// No strict_types on purpose: this is how a caller without it reaches the methods.

use PhpCollective\DecimalObject\Decimal;

$result = [];

try {
    $result['mod'] = (string)(new Decimal('7.0'))->mod(2.5);
} catch (Throwable $e) {
    $result['mod'] = $e::class;
}

try {
    $result['pow'] = (string)(new Decimal('4'))->pow(2.0);
} catch (Throwable $e) {
    $result['pow'] = $e::class;
}

return $result;
