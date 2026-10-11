<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\DecimalObject\Test;

use ArrayIterator;
use DivisionByZeroError;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;
use TypeError;

class DecimalTest extends TestCase
{
    /**
     * @param mixed $value
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('baseProvider')]
    public function testNewObject(mixed $value, string $expected): void
    {
        $decimal = new Decimal($value);
        $this->assertSame($expected, (string)$decimal);
    }

    /**
     * @return void
     */
    public function testNewObjectScientific(): void
    {
        $value = '2.2e-6';
        $decimal = new Decimal($value);
        $result = $decimal->toString();
        $this->assertSame('0.0000022', $result);

        $this->assertSame(7, $decimal->scale());
    }

    /**
     * @param mixed $value
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('baseProvider')]
    public function testCreate(mixed $value, string $expected): void
    {
        $decimal = Decimal::create($value);
        $this->assertSame($expected, (string)$decimal);
    }

    /**
     * @return void
     */
    public function testCreateInvalidObject(): void
    {
        $objectStringable = new class implements Stringable
        {
            /**
             * @return string
             */
            public function __toString(): string
            {
                return 'foo';
            }
        };

        $this->expectException(InvalidArgumentException::class);

        Decimal::create($objectStringable);
    }

    /**
     * @return array
     */
    public static function baseProvider(): array
    {
        $objectWithToStringMethod = new class
        {
            /**
             * @return string
             */
            public function __toString(): string
            {
                return '12.12';
            }
        };

        $objectStringable = new class implements Stringable
        {
            /**
             * @return string
             */
            public function __toString(): string
            {
                return '12.12';
            }
        };

        return [
            [1.1, '1.1'],
            [-23, '-23'],
            [50, '50'],
            [-25000, '-25000'],
            [0.00001, '0.00001'],
            [-0.000003, '-0.000003'],
            ['.0189', '0.0189'],
            ['-.3', '-0.3'],
            ['-5.000067', '-5.000067'],
            ['+5.000067', '5.000067'],
            ['0000005', '5'],
            ['000000.5', '0.5'],
            ['  0.0   ', '0.0'],
            ['6.22e8', '622000000'],
            ['6.22e18', '6220000000000000000'],
            [PHP_INT_MAX, (string)PHP_INT_MAX],
            [PHP_INT_MAX . '.' . PHP_INT_MAX, PHP_INT_MAX . '.' . PHP_INT_MAX],
            [-PHP_INT_MAX, '-' . PHP_INT_MAX],
            [Decimal::create('-12.375'), '-12.375'],
            ['0000', '0'],
            ['-0', '0'],
            ['+0', '0'],
            ['311000000000000000000000', '311000000000000000000000'],
            ['3.11e23', '311000000000000000000000'],
            ['622000000000000000000000', '622000000000000000000000'],
            ['3.11e2', '311'],
            [$objectWithToStringMethod, '12.12'],
            [$objectStringable, '12.12'],
        ];
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    #[DataProvider('invalidValuesProvider')]
    public function testNewObjectWithInvalidValueThrowsException(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::create($value);
    }

    /**
     * @return array
     */
    public static function invalidValuesProvider(): array
    {
        return [
            'invalid string' => ['xyz'],
            'non-english/localized case1' => ['1018,9'],
            'non-english/localized case2' => ['1.018,9'],
        ];
    }

    /**
     * @param mixed $input
     * @param int $scale
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('truncateProvider')]
    public function testTruncate(mixed $input, int $scale, string $expected): void
    {
        $decimal = Decimal::create($input);
        $this->assertSame($expected, (string)$decimal->truncate($scale));
    }

    /**
     * @return array
     */
    public static function truncateProvider(): array
    {
        return [
            [0, 0, '0'],
            [1, 0, '1'],
            [-1, 0, '-1'],
            ['12.375', 2, '12.37'],
            ['12.374', 0, '12'],
            ['-12.376', 1, '-12.3'],
        ];
    }

    /**
     * @param mixed $value
     * @param bool $expected
     *
     * @return void
     */
    #[DataProvider('integerProvider')]
    public function testIsInteger(mixed $value, bool $expected): void
    {
        $decimal = Decimal::create($value);
        $this->assertSame($expected, $decimal->isInteger());
    }

    /**
     * @return array
     */
    public static function integerProvider(): array
    {
        return [
            [5, true],
            [0.00001, false],
            [-0.000003, false],
            [Decimal::create('0'), true],
            [0, true],
            [0.0, true],
            ['0000', true],
            ['-0', true],
            ['+0', true],
            [-121211, true],
        ];
    }

    /**
     * @param mixed $value
     * @param bool $expected
     *
     * @return void
     */
    #[DataProvider('zeroProvider')]
    public function testIsZero(mixed $value, bool $expected): void
    {
        $decimal = Decimal::create($value);
        $this->assertSame($expected, $decimal->isZero());
    }

    /**
     * @return array
     */
    public static function zeroProvider(): array
    {
        return [
            [5, false],
            [0.00001, false],
            [-0.000003, false],
            [Decimal::create('0'), true],
            [0, true],
            [0.0, true],
            ['0000', true],
            ['-0', true],
            ['+0', true],
        ];
    }

    /**
     * @param mixed $input
     * @param int $expected
     *
     * @return void
     */
    #[DataProvider('compareZeroProvider')]
    public function testIsPositive(mixed $input, int $expected): void
    {
        $decimal = Decimal::create($input);
        $this->assertSame($expected > 0, $decimal->isPositive());
    }

    /**
     * @param mixed $input
     * @param int $expected
     *
     * @return void
     */
    #[DataProvider('compareZeroProvider')]
    public function testIsNegative(mixed $input, int $expected): void
    {
        $decimal = Decimal::create($input);
        $this->assertSame($expected < 0, $decimal->isNegative());
    }

    /**
     * @return array
     */
    public static function compareZeroProvider(): array
    {
        return [
            [0, 0],
            [1, 1],
            [-1, -1],
            [0.0, 0],
            ['0', 0],
            ['1', 1],
            ['-1', -1],
            ['00000', 0],
            ['0.0', 0],
            ['0.00001', 1],
            ['1e-20', 1],
            ['-1e-20', -1],
        ];
    }

    /**
     * @param mixed $input
     * @param int $expected
     *
     * @return void
     */
    #[DataProvider('scaleProvider')]
    public function testScale(mixed $input, int $expected): void
    {
        $decimal = Decimal::create($input);
        $this->assertSame($expected, $decimal->scale());
    }

    /**
     * @return array
     */
    public static function scaleProvider(): array
    {
        return [
            [0, 0],
            [1, 0],
            [-1, 0],
            ['120', 0],
            ['12.375', 3],
            ['-0.7', 1],
            ['6.22e23', 0],
            ['1e-10', 10],
            ['-2.3e-10', 11], // 0.00000000023
        ];
    }

    /**
     * @param mixed $value
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('scientificProvider')]
    public function testToScientific(mixed $value, string $expected): void
    {
        $decimal = Decimal::create($value);
        $this->assertSame($expected, $decimal->toScientific());
        $revertedDecimal = Decimal::create($decimal->toScientific());
        $this->assertSame($value, (string)$revertedDecimal);
    }

    /**
     * @return array
     */
    public static function scientificProvider(): array
    {
        return [
            ['-23', '-2.3e1'],
            ['1.000', '1.000e0'],
            ['-22.345', '-2.2345e1'],
            ['30022.0345', '3.00220345e4'],
            ['-0.00230', '-2.30e-3'],
        ];
    }

    /**
     * @return void
     */
    public function testToString(): void
    {
        $value = -23;
        $decimal = Decimal::create($value);

        $result = (string)$decimal;
        $this->assertSame('-23', $result);
    }

    /**
     * @return void
     */
    public function testTrim(): void
    {
        $value = '-2.0300000000000000000000000000';
        $decimal = Decimal::create($value);
        $this->assertSame(28, $decimal->scale());

        $trimmed = $decimal->trim();
        $this->assertSame('-2.03', (string)$trimmed);
        $this->assertSame(2, $trimmed->scale());

        $value = '2000';
        $decimal = Decimal::create($value);

        $trimmed = $decimal->trim();
        $this->assertSame('2000', (string)$trimmed);
        $this->assertSame(0, $trimmed->scale());
    }

    /**
     * @return void
     */
    public function testToFloat(): void
    {
        $value = '-23.44';
        $decimal = Decimal::create($value);

        $result = $decimal->toFloat();
        $this->assertSame(-23.44, $result);
    }

    /**
     * @param string $value
     *
     * @return void
     */
    #[DataProvider('bigFloatDataProvider')]
    public function testToFloatForBigDecimalThrowsAnException(string $value): void
    {
        $decimal = Decimal::create($value);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Cannot cast Big Decimal to Float');

        $decimal->toFloat();
    }

    /**
     * @return array
     */
    public static function bigFloatDataProvider(): array
    {
        return [
            'positive' => ['2.6' . PHP_INT_MAX],
            'negative' => ['-2.6' . PHP_INT_MAX],
        ];
    }

    /**
     * @return void
     */
    public function testToInt(): void
    {
        $value = '-23.74';
        $decimal = Decimal::create($value);

        $result = $decimal->toInt();
        $this->assertSame(-23, $result);
    }

    /**
     * @param string $value
     *
     * @return void
     */
    #[DataProvider('bigIntDataProvider')]
    public function testToIntForBigIntThrowsAnException(string $value): void
    {
        $decimal = Decimal::create($value);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Cannot cast Big Integer to Integer');

        $decimal->toInt();
    }

    /**
     * @return array
     */
    public static function bigIntDataProvider(): array
    {
        return [
            'positive' => ['9' . PHP_INT_MAX],
            'negative' => ['-9' . PHP_INT_MAX],
        ];
    }

    /**
     * @return void
     */
    public function testMod(): void
    {
        $value = '7';
        $decimal = Decimal::create($value);

        $result = $decimal->mod(2);
        $this->assertSame('1', (string)$result);
    }

    /**
     * @return void
     */
    public function testPow(): void
    {
        $value = '8';
        $decimal = Decimal::create($value);

        $result = $decimal->pow(2);
        $this->assertSame('64', (string)$result);
    }

    /**
     * @return void
     */
    public function testSqrt(): void
    {
        $value = '64';
        $decimal = Decimal::create($value);

        $result = $decimal->sqrt();
        $this->assertSame('8', (string)$result);

        $value = '18';
        $decimal = Decimal::create($value);

        $result = $decimal->sqrt(5);
        $this->assertSame('4.24264', (string)$result);

        $value = '18.000000';
        $decimal = Decimal::create($value);

        $result = $decimal->sqrt();
        $this->assertSame('4.242640', (string)$result);
    }

    /**
     * @return void
     */
    public function testAbsolute(): void
    {
        $value = '-23.44';
        $decimal = Decimal::create($value);

        $result = $decimal->absolute();
        $this->assertSame('23.44', (string)$result);
    }

    /**
     * @return void
     */
    public function testNegate(): void
    {
        $value = '-23.44';
        $decimal = Decimal::create($value);

        $result = $decimal->negate();
        $this->assertSame('23.44', (string)$result);

        $again = $result->negate();
        $this->assertSame($value, (string)$again);
    }

    /**
     * @return void
     */
    public function testIsNegativeBasic(): void
    {
        $value = '-23.44';
        $decimal = Decimal::create($value);
        $this->assertTrue($decimal->isNegative());

        $value = '23.44';
        $decimal = Decimal::create($value);
        $this->assertFalse($decimal->isNegative());

        $value = '0';
        $decimal = Decimal::create($value);
        $this->assertFalse($decimal->isNegative());
    }

    /**
     * @return void
     */
    public function testIsPositiveBasic(): void
    {
        $value = '-23.44';
        $decimal = Decimal::create($value);
        $this->assertFalse($decimal->isPositive());

        $value = '23.44';
        $decimal = Decimal::create($value);
        $this->assertTrue($decimal->isPositive());

        $value = '0';
        $decimal = Decimal::create($value);
        $this->assertFalse($decimal->isPositive());
    }

    /**
     * @return void
     */
    public function testEquals(): void
    {
        $value = '1.1';
        $decimalOne = Decimal::create($value);

        $value = '1.10';
        $decimalTwo = Decimal::create($value);

        $result = $decimalOne->equals($decimalTwo);
        $this->assertTrue($result);
    }

    /**
     * @param mixed $value
     * @param int $scale
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('roundProvider')]
    public function testRound(mixed $value, int $scale, string $expected): void
    {
        $decimal = Decimal::create($value);
        $this->assertSame($expected, (string)$decimal->round($scale));
        $this->assertNativeRound($expected, $value, $scale, PHP_ROUND_HALF_UP);
    }

    /**
     * @param string $expected
     * @param mixed $value
     * @param int $scale
     * @param int $roundMode
     *
     * @return void
     */
    protected function assertNativeRound(string $expected, mixed $value, int $scale, int $roundMode): void
    {
        $value = (string)round((float)$value, $scale, $roundMode);
        if ($value === '-0') {
            $value = '0';
        }

        $this->assertSame((new Decimal($expected))->trim()->toString(), $value);
    }

    /**
     * @return array
     */
    public static function roundProvider(): array
    {
        return [
            [0, 0, '0'],
            [1, 0, '1'],
            [11, 2, '11.00'],
            [-1, 0, '-1'],
            [-5, 1, '-5.0'],
            ['12.375', 1, '12.4'],
            ['12.374', 2, '12.37'],
            ['12.375', 2, '12.38'],
            ['12.364', 2, '12.36'],
            ['12.365', 2, '12.37'],
            ['-13.574', 0, '-14'],
            [13.4999, 0, '13'],
            [13.4999, 10, '13.4999000000'],
            [13.4999, 2, '13.50'],
            [0.0001, 0, '0'],
            [-0.0001, 0, '0'],
            [0.9999, 0, '1'],
            [-0.9999, 0, '-1'],
        ];
    }

    /**
     * @param mixed $value
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('floorProvider')]
    public function testFloor(mixed $value, string $expected): void
    {
        $decimal = Decimal::create($value);
        $this->assertSame($expected, (string)$decimal->floor());
        $this->assertNativeFloor($expected, $value);
    }

    /**
     * @param string $expected
     * @param mixed $value
     *
     * @return void
     */
    protected function assertNativeFloor(string $expected, mixed $value): void
    {
        $value = (string)floor((float)$value);
        if ($value === '-0') {
            $value = '0';
        }

        $this->assertSame($expected, $value);
    }

    /**
     * @return array
     */
    public static function floorProvider(): array
    {
        return [
            [0, '0'],
            [1, '1'],
            [100, '100'],
            [-1, '-1'],
            [-7, '-7'],
            ['12.375', '12'],
            ['-13.574', '-14'],
            ['-13.8', '-14'],
            ['-13.1', '-14'],
            ['13.6999', '13'],
            ['13.1', '13'],
            ['13.9', '13'],
            [0.0001, '0'],
            [-0.0001, '-1'],
        ];
    }

    /**
     * @param mixed $value
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('ceilProvider')]
    public function testCeil(mixed $value, string $expected): void
    {
        $decimal = Decimal::create($value);
        $this->assertSame($expected, (string)$decimal->ceil());
        $this->assertNativeCeil($expected, $value);
    }

    /**
     * @param string $expected
     * @param mixed $value
     *
     * @return void
     */
    protected function assertNativeCeil(string $expected, mixed $value): void
    {
        $value = (string)ceil((float)$value);
        if ($value === '-0') {
            $value = '0';
        }
        $this->assertSame($expected, $value);
    }

    /**
     * @return array
     */
    public static function ceilProvider(): array
    {
        return [
            [0, '0'],
            [1, '1'],
            [100, '100'],
            [-1, '-1'],
            [-2, '-2'],
            ['12.375', '13'],
            ['-13.574', '-13'],
            ['-13.8', '-13'],
            ['-13.1', '-13'],
            ['13.6999', '14'],
            ['13.1', '14'],
            ['13.9', '14'],
            [0.0001, '1'],
            [-0.0001, '0'],
        ];
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param int $expected
     *
     * @return void
     */
    #[DataProvider('compareProvider')]
    public function testGreaterThan(mixed $a, mixed $b, int $expected): void
    {
        $decimal = Decimal::create($a);
        $this->assertSame($expected > 0, $decimal->greaterThan($b));
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param int $expected
     *
     * @return void
     */
    #[DataProvider('compareProvider')]
    public function testLessThan(mixed $a, mixed $b, int $expected): void
    {
        $decimal = Decimal::create($a);
        $this->assertSame($expected < 0, $decimal->lessThan($b));
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param int $expected
     *
     * @return void
     */
    #[DataProvider('compareProvider')]
    public function testGreaterEquals(mixed $a, mixed $b, int $expected): void
    {
        $decimal = Decimal::create($a);
        $this->assertSame($expected >= 0, $decimal->greaterThanOrEquals($b));
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param int $expected
     *
     * @return void
     */
    #[DataProvider('compareProvider')]
    public function testLessEquals(mixed $a, mixed $b, int $expected): void
    {
        $decimal = Decimal::create($a);
        $this->assertSame($expected <= 0, $decimal->lessThanOrEquals($b));
    }

    /**
     * @return array
     */
    public static function compareProvider(): array
    {
        return [
            [0, 0, 0],
            [1, 0, 1],
            [-1, 0, -1],
            ['12.375', '12.375', 0],
            ['12.374', '12.375', -1],
            ['12.376', '12.375', 1],
            ['6.22e23', '6.22e23', 0],
            ['1e-10', '1e-9', -1],
        ];
    }

    /**
     * @return void
     */
    public function testAdd(): void
    {
        $value = '1.1';
        $decimalOne = Decimal::create($value);

        $value = '1.2';
        $decimalTwo = Decimal::create($value);

        $result = $decimalOne->add($decimalTwo);

        $this->assertSame('2.3', (string)$result);
        $this->assertSame(1, $result->scale());
    }

    /**
     * @return void
     */
    public function testSubtract(): void
    {
        $value = '0.1';
        $decimalOne = Decimal::create($value);

        $value = '0.01';
        $decimalTwo = Decimal::create($value);

        $result = $decimalOne->subtract($decimalTwo);
        $this->assertSame('0.09', (string)$result);
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param int|null $scale
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('multiplicationProvider')]
    public function testMultiply(mixed $a, mixed $b, ?int $scale, string $expected): void
    {
        $decimal = Decimal::create($a);
        $this->assertSame($expected, (string)$decimal->multiply($b, $scale));
    }

    /**
     * @return array
     */
    public static function multiplicationProvider(): array
    {
        return [
            ['0', '0', null, '0'],
            ['1', '10', null, '10'],
            ['1000', '10', null, '10000'],
            ['-10', '10', null, '-100'],
            ['10', '-10', null, '-100'],
            ['10', '10', null, '100'],
            ['0.1', '1', null, '0.1'],
            ['0.1', '0.01', null, '0.001'],
            ['-0.001', '0.01', null, '-0.00001'],
            ['9', '0.001', 3, '0.009'],
            ['9', '0.001', 0, '0'],
            ['1e-10', '28', null, '0.0000000028'],
            ['1e-10', '-1e-10', null, '-0.00000000000000000001'],
            ['1e-10', '-1e-10', 20, '-0.00000000000000000001'],
            ['1e-10', '-1e-10', 19, '0.0000000000000000000'],
        ];
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param int|null $scale
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('multiplicationLegacyProvider')]
    public function testMultiplyLegacy(mixed $a, mixed $b, ?int $scale, string $expected): void
    {
        $decimal = Decimal::create($a);
        $this->assertSame($expected, (string)$decimal->multiply($b, $scale));
    }

    /**
     * @return array
     */
    public static function multiplicationLegacyProvider(): array
    {
        return [
            ['0', '0', 3, '0.000'],
        ];
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param int $scale
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('divisionProvider')]
    public function testDivide(mixed $a, mixed $b, int $scale, string $expected): void
    {
        $decimal = Decimal::create($a);
        $this->assertSame($expected, (string)$decimal->divide($b, $scale));
    }

    /**
     * @return array
     */
    public static function divisionProvider(): array
    {
        return [
            ['0', '1', 0, '0'],
            ['1', '1', 0, '1'],
            ['0', '1e6', 0, '0'],
            [1, 10, 1, '0.1'],
            ['1000', '10', 0, '100'],
            ['-10', '10', 0, '-1'],
            ['10', '-10', 0, '-1'],
            ['10', '10', 0, '1'],
            ['0.1', '1', 1, '0.1'],
            ['0.1', '0.01', 0, '10'],
            ['-0.001', '0.01', 1, '-0.1'],
            ['1', '3', 3, '0.333'],
            ['1', '3', 0, '0'],
            ['15', '2', 1, '7.5'],
            ['15', '2', 1, '7.5'],
            ['101', '11', 3, '9.181'],
            ['10', '3', 3, '3.333'],
            ['1.1', '.2', 3, '5.500'],
            ['1.23', '.2', 3, '6.150'],
            ['0.2', '.11111', 20, '1.80001800018000180001'],
            ['6.22e23', '2', 0, '311000000000000000000000'],
            ['6.22e23', '-1', 0, '-622000000000000000000000'],
            ['1e-10', 3, 0, '0'],
            ['1e-10', 3, 11, '0.00000000003'],
            ['1e-10', 3, 12, '0.000000000033'],
        ];
    }

    /**
     * @return void
     */
    public function testDivideByZero(): void
    {
        $decimal = Decimal::create(1);

        $this->expectException(DivisionByZeroError::class);

        $decimal->divide(0, 10);
    }

    /**
     * @return void
     */
    public function testDebugInfo(): void
    {
        $value = '1.1';
        $decimal = Decimal::create($value);

        $result = $decimal->__debugInfo();
        $expected = [
            'value' => $value,
            'scale' => 1,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testPrecisionLoss(): void
    {
        $a = Decimal::create('0.123456789', 10);
        $this->assertSame('0.123456789', (string)$a);

        $b = Decimal::create((string)$a, 8);
        $this->assertSame('0.12345678', (string)$b);
    }

    /**
     * @return void
     */
    public function testPrecisionLossProtection(): void
    {
        $a = Decimal::create('0.1', 50, true);
        $this->assertSame(50, $a->scale());

        $b = Decimal::create($a, null, true);
        $this->assertSame(50, $b->scale());

        $c = Decimal::create($b, 6, true); // Not 50 if manually overwritten
        $this->assertSame(6, $c->scale());

        $d = Decimal::create($c, 64, true);
        $this->assertSame(64, $d->scale());
    }

    /**
     * @return void
     */
    public function testPrecisionLossFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Loss of precision detected');

        Decimal::create('0.123', 2, true);
    }

    /**
     * @param mixed $value
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('normalizationProvider')]
    public function testNewObjectNormalization(mixed $value, string $expected): void
    {
        $this->assertSame($expected, (string)new Decimal($value));
    }

    /**
     * @return array
     */
    public static function normalizationProvider(): array
    {
        return [
            'leading zeros' => ['0010', '10'],
            'negative leading zeros' => ['-007', '-7'],
            'positive sign leading zeros' => ['+007', '7'],
            'positive sign omitted zero' => ['+.5', '0.5'],
            'negative zero with decimals' => ['-0.0', '0.0'],
            'negative double zero' => ['-00', '0'],
            'trailing radix' => ['5.', '5'],
            'explicit positive exponent' => ['1e+5', '100000'],
            'uppercase exponent' => ['1E5', '100000'],
            'multi digit mantissa' => ['12.5e-1', '1.25'],
            'integer mantissa' => ['100e-2', '1.00'],
            'integer mantissa trailing zero' => ['120e-1', '12.0'],
            'long mantissa' => ['123.456e-2', '1.23456'],
            'negative mantissa' => ['-1.5e-2', '-0.015'],
            'omitted zero mantissa' => ['.5e1', '5'],
            'small mantissa' => ['0.00012e3', '0.12'],
            'significant zero in mantissa' => ['1.0e0', '1.0'],
            'zero exponent' => ['1.5e0', '1.5'],
            'negative zero exponent' => ['-0e5', '0'],
            'zero mantissa' => ['0.0e-3', '0.0000'],
            'large float' => [1e25, '10000000000000000000000000'],
            'small float' => [1.5e-7, '0.00000015'],
            'negative zero float' => [-0.0, '0'],
        ];
    }

    /**
     * @param float $value
     *
     * @return void
     */
    #[DataProvider('nonFiniteProvider')]
    public function testNewObjectNonFiniteFloat(float $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Decimal($value);
    }

    /**
     * @return array
     */
    public static function nonFiniteProvider(): array
    {
        return [
            'inf' => [INF],
            'negative inf' => [-INF],
            'nan' => [NAN],
        ];
    }

    /**
     * @return void
     */
    public function testNewObjectZeroScale(): void
    {
        $decimal = new Decimal('1.56', 0);
        $this->assertSame('1', (string)$decimal);
        $this->assertSame(0, $decimal->scale());
    }

    /**
     * @return void
     */
    public function testNewObjectZeroScaleStrict(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Loss of precision detected');

        new Decimal('1.56', 0, true);
    }

    /**
     * @return void
     */
    public function testNewObjectNegativeScale(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Decimal('1.56', -1);
    }

    /**
     * @return void
     */
    public function testNewObjectScientificWithScale(): void
    {
        $this->assertSame('1.500', (string)new Decimal('1.5e0', 3));
        $this->assertSame('0.001200', (string)new Decimal('1.2e-3', 6));
        $this->assertSame('0.0012', (string)new Decimal('1.23456e-3', 4));
    }

    /**
     * @return void
     */
    public function testNoNegativeZero(): void
    {
        $decimal = new Decimal('-0.0');
        $this->assertFalse($decimal->isNegative());
        $this->assertSame(0, $decimal->sign());

        $this->assertSame('0', (string)(new Decimal('0'))->negate());
        $this->assertSame('0.00', (string)(new Decimal('0.00'))->negate());
        $this->assertSame('0', (string)(new Decimal('-0.5'))->truncate());
        $this->assertSame('0.0', (string)(new Decimal('-0.05'))->truncate(1));
        $this->assertSame('0.00', (string)new Decimal('-0.001', 2));
        $this->assertFalse((new Decimal('-0.5'))->truncate()->isNegative());
    }

    /**
     * @param string $value
     * @param int $scale
     * @param int $mode
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('roundModeProvider')]
    public function testRoundMode(string $value, int $scale, int $mode, string $expected): void
    {
        $this->assertSame($expected, (string)(new Decimal($value))->round($scale, $mode));
    }

    /**
     * @return array
     */
    public static function roundModeProvider(): array
    {
        return [
            'floor negative whole' => ['-1.0', 0, Decimal::ROUND_FLOOR, '-1'],
            'floor positive whole' => ['1.00', 0, Decimal::ROUND_FLOOR, '1'],
            'floor negative' => ['-1.5', 0, Decimal::ROUND_FLOOR, '-2'],
            'floor scale' => ['1.25', 1, Decimal::ROUND_FLOOR, '1.2'],
            'floor negative scale' => ['-1.21', 1, Decimal::ROUND_FLOOR, '-1.3'],
            'floor exact scale' => ['-1.20', 1, Decimal::ROUND_FLOOR, '-1.2'],
            'floor higher scale' => ['1.5', 3, Decimal::ROUND_FLOOR, '1.500'],
            'floor small negative' => ['-0.001', 2, Decimal::ROUND_FLOOR, '-0.01'],
            'ceil positive whole' => ['1.0', 0, Decimal::ROUND_CEIL, '1'],
            'ceil negative whole' => ['-1.00', 0, Decimal::ROUND_CEIL, '-1'],
            'ceil positive' => ['1.5', 0, Decimal::ROUND_CEIL, '2'],
            'ceil scale' => ['1.21', 1, Decimal::ROUND_CEIL, '1.3'],
            'ceil negative scale' => ['-1.29', 1, Decimal::ROUND_CEIL, '-1.2'],
            'ceil exact scale' => ['1.20', 1, Decimal::ROUND_CEIL, '1.2'],
            'ceil small negative' => ['-0.001', 2, Decimal::ROUND_CEIL, '0.00'],
            'ceil small positive' => ['0.001', 2, Decimal::ROUND_CEIL, '0.01'],
            'half up negative' => ['-1.005', 2, Decimal::ROUND_HALF_UP, '-1.01'],
            'half up to zero' => ['-0.004', 2, Decimal::ROUND_HALF_UP, '0.00'],
        ];
    }

    /**
     * @param string $value
     *
     * @return void
     */
    #[DataProvider('roundInvalidModeProvider')]
    public function testRoundInvalidMode(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal($value))->round(0, PHP_ROUND_HALF_EVEN);
    }

    /**
     * @return array
     */
    public static function roundInvalidModeProvider(): array
    {
        return [
            'needs rounding' => ['1.5'],
            'exact' => ['1.0'],
        ];
    }

    /**
     * @param string $value
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('scientificEdgeProvider')]
    public function testToScientificEdgeCases(string $value, string $expected): void
    {
        $decimal = new Decimal($value);
        $this->assertSame($expected, $decimal->toScientific());
        $this->assertSame($value, (string)new Decimal($decimal->toScientific()));
    }

    /**
     * @return array
     */
    public static function scientificEdgeProvider(): array
    {
        return [
            'zero' => ['0', '0e0'],
            'zero with scale' => ['0.00', '0.00e0'],
            'single digit' => ['5', '5e0'],
            'power of ten' => ['10', '1e1'],
            'large power of ten' => ['100000', '1e5'],
            'trailing zero integer' => ['120', '1.2e2'],
            'trailing zero integer with fraction' => ['120.50', '1.2050e2'],
            'beyond float precision' => ['1234567890123456789012.5', '1.2345678901234567890125e21'],
            'beyond int precision' => ['12345678901234567', '1.2345678901234567e16'],
            'single fraction digit' => ['0.5', '5e-1'],
            'small fraction' => ['0.00012', '1.2e-4'],
            'negative with scale' => ['-12.50', '-1.250e1'],
        ];
    }

    /**
     * @return void
     */
    public function testToIntBoundaries(): void
    {
        $this->assertSame(PHP_INT_MIN, (new Decimal((string)PHP_INT_MIN))->toInt());
        $this->assertSame(PHP_INT_MAX, (new Decimal((string)PHP_INT_MAX))->toInt());
        $this->assertFalse((new Decimal((string)PHP_INT_MIN))->isBigInteger());
        $this->assertTrue((new Decimal('-9223372036854775809'))->isBigInteger());
        $this->assertTrue((new Decimal('9223372036854775808'))->isBigInteger());
    }

    /**
     * @return void
     */
    public function testModOperands(): void
    {
        $decimal = new Decimal('10');

        $this->assertSame('0', (string)$decimal->mod('1e1'));
        $this->assertSame('1', (string)$decimal->mod(new Decimal('3')));
        $this->assertSame('0.1', (string)$decimal->mod('0.3'));
        $this->assertSame('1.5', (string)(new Decimal('10.5'))->mod('3'));
        $this->assertSame('0', (string)$decimal->mod('0.3', 0));
    }

    /**
     * @return void
     */
    public function testPowOperands(): void
    {
        $decimal = new Decimal('2');

        $this->assertSame('100', (string)(new Decimal('10'))->pow('2e0'));
        $this->assertSame('4', (string)$decimal->pow(new Decimal('2')));
        $this->assertSame('0.50', (string)$decimal->pow(-1, 2));
        $this->assertSame('2.25', (string)(new Decimal('1.50'))->pow(2));
    }

    /**
     * @param string $value
     * @param int $scale
     * @param int $mode
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('roundExtraModeProvider')]
    public function testRoundExtraModes(string $value, int $scale, int $mode, string $expected): void
    {
        $this->assertSame($expected, (string)(new Decimal($value))->round($scale, $mode));
    }

    /**
     * @return array
     */
    public static function roundExtraModeProvider(): array
    {
        return [
            'half down tie' => ['1.25', 1, Decimal::ROUND_HALF_DOWN, '1.2'],
            'half down above tie' => ['1.251', 1, Decimal::ROUND_HALF_DOWN, '1.3'],
            'half down negative tie' => ['-1.25', 1, Decimal::ROUND_HALF_DOWN, '-1.2'],
            'half even tie to even' => ['1.25', 1, Decimal::ROUND_HALF_EVEN, '1.2'],
            'half even tie to even up' => ['1.35', 1, Decimal::ROUND_HALF_EVEN, '1.4'],
            'half even negative' => ['-2.5', 0, Decimal::ROUND_HALF_EVEN, '-2'],
            'half even carry' => ['9.5', 0, Decimal::ROUND_HALF_EVEN, '10'],
            'half even above tie' => ['2.5000001', 0, Decimal::ROUND_HALF_EVEN, '3'],
            'half odd tie' => ['2.5', 0, Decimal::ROUND_HALF_ODD, '3'],
            'half odd tie stays' => ['1.5', 0, Decimal::ROUND_HALF_ODD, '1'],
            'down' => ['1.29', 1, Decimal::ROUND_DOWN, '1.2'],
            'down negative' => ['-1.29', 1, Decimal::ROUND_DOWN, '-1.2'],
            'up' => ['1.21', 1, Decimal::ROUND_UP, '1.3'],
            'up negative' => ['-1.21', 1, Decimal::ROUND_UP, '-1.3'],
            'up exact' => ['1.20', 1, Decimal::ROUND_UP, '1.2'],
            'pads' => ['1.5', 3, Decimal::ROUND_HALF_EVEN, '1.500'],
            'to zero keeps scale' => ['-0.004', 2, Decimal::ROUND_HALF_EVEN, '0.00'],
            'tens' => ['15', -1, Decimal::ROUND_HALF_UP, '20'],
            'tens down' => ['14.99', -1, Decimal::ROUND_HALF_UP, '10'],
            'hundreds negative' => ['-1250', -2, Decimal::ROUND_HALF_UP, '-1300'],
            'hundreds half even' => ['1250', -2, Decimal::ROUND_HALF_EVEN, '1200'],
            'tens below' => ['4', -1, Decimal::ROUND_HALF_UP, '0'],
            'floor at scale' => ['1.25', 1, Decimal::ROUND_FLOOR, '1.2'],
            'floor tens' => ['15', -1, Decimal::ROUND_FLOOR, '10'],
            'ceil tens negative' => ['-15', -1, Decimal::ROUND_CEIL, '-10'],
        ];
    }

    /**
     * @param string $value
     * @param int $scale
     * @param int $mode
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('withScaleProvider')]
    public function testWithScale(string $value, int $scale, int $mode, string $expected): void
    {
        $result = (new Decimal($value))->withScale($scale, $mode);

        $this->assertSame($expected, (string)$result);
        $this->assertSame($scale, $result->scale());
    }

    /**
     * @return array
     */
    public static function withScaleProvider(): array
    {
        return [
            'pad' => ['1.5', 3, Decimal::ROUND_HALF_UP, '1.500'],
            'pad integer' => ['7', 2, Decimal::ROUND_HALF_UP, '7.00'],
            'same' => ['1.50', 2, Decimal::ROUND_HALF_UP, '1.50'],
            'round' => ['1.255', 2, Decimal::ROUND_HALF_UP, '1.26'],
            'to integer' => ['1.5', 0, Decimal::ROUND_HALF_UP, '2'],
            'floor at scale' => ['1.25', 1, Decimal::ROUND_FLOOR, '1.2'],
            'floor negative at scale' => ['-1.21', 1, Decimal::ROUND_FLOOR, '-1.3'],
            'ceil at scale' => ['1.21', 1, Decimal::ROUND_CEIL, '1.3'],
            'ceil negative at scale' => ['-1.29', 1, Decimal::ROUND_CEIL, '-1.2'],
            'ceil small negative' => ['-0.001', 2, Decimal::ROUND_CEIL, '0.00'],
            'half even' => ['0.125', 2, Decimal::ROUND_HALF_EVEN, '0.12'],
        ];
    }

    /**
     * @return void
     */
    public function testWithScaleDeclaredScale(): void
    {
        $result = Decimal::create('0.1', 4)->withScale(4);

        $this->assertSame('0.1000', (string)$result);
    }

    /**
     * @return void
     */
    public function testWithScaleNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal('1.5'))->withScale(-1);
    }

    /**
     * @return void
     */
    public function testWithScaleInvalidMode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal('1.5'))->withScale(2, PHP_ROUND_HALF_EVEN);
    }

    /**
     * @param string $value
     * @param int $places
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('shiftProvider')]
    public function testShift(string $value, int $places, string $expected): void
    {
        $this->assertSame($expected, (string)(new Decimal($value))->shift($places));
    }

    /**
     * @return array
     */
    public static function shiftProvider(): array
    {
        return [
            'none' => ['12.34', 0, '12.34'],
            'right' => ['12.34', 1, '123.4'],
            'right all' => ['12.34', 2, '1234'],
            'right beyond' => ['12.34', 4, '123400'],
            'left' => ['12.34', -1, '1.234'],
            'left all' => ['12.34', -2, '0.1234'],
            'left beyond' => ['12.34', -4, '0.001234'],
            'negative' => ['-0.05', 2, '-5'],
            'zero' => ['0', 3, '0'],
            'zero left' => ['0', -2, '0.00'],
            'integer left keeps zeros' => ['100', -2, '1.00'],
        ];
    }

    /**
     * @return void
     */
    public function testExponentLimit(): void
    {
        $this->assertSame(Decimal::MAX_EXPONENT + 1, strlen((string)new Decimal('1e' . Decimal::MAX_EXPONENT)));
        $this->assertSame(Decimal::MAX_EXPONENT, (new Decimal('1e-' . Decimal::MAX_EXPONENT))->scale());
    }

    /**
     * @param string $value
     *
     * @return void
     */
    #[DataProvider('exponentLimitProvider')]
    public function testExponentLimitExceeded(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exponent must be between');

        new Decimal($value);
    }

    /**
     * @return array
     */
    public static function exponentLimitProvider(): array
    {
        return [
            'positive' => ['1e' . (Decimal::MAX_EXPONENT + 1)],
            'negative' => ['1e-' . (Decimal::MAX_EXPONENT + 1)],
            'huge' => ['1e1000000000'],
            'beyond int' => ['1e99999999999999999999'],
        ];
    }

    /**
     * @return void
     */
    public function testTryCreate(): void
    {
        $this->assertSame('1.50', (string)Decimal::tryCreate('1.50'));
        $this->assertSame('1.5', (string)Decimal::tryCreate('1.56', 1));
        $this->assertNull(Decimal::tryCreate(null));
        $this->assertNull(Decimal::tryCreate(''));
        $this->assertNull(Decimal::tryCreate('abc'));
        $this->assertNull(Decimal::tryCreate('1,5'));
        $this->assertNull(Decimal::tryCreate(NAN));
    }

    /**
     * @return void
     */
    public function testSum(): void
    {
        $this->assertSame('6.75', (string)Decimal::sum(['1.5', 2, Decimal::create('3.25')]));
        $this->assertSame('0', (string)Decimal::sum([]));
        $this->assertSame('0.3', (string)Decimal::sum(new ArrayIterator(['0.1', '0.2'])));
    }

    /**
     * @return void
     */
    public function testAverage(): void
    {
        $this->assertSame('2.00', (string)Decimal::average(['1', '2', '3'], 2));
        $this->assertSame('0.333', (string)Decimal::average(['0', '0', '1'], 3));
    }

    /**
     * @return void
     */
    public function testAverageEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::average([], 2);
    }

    /**
     * @return void
     */
    public function testMinMax(): void
    {
        $values = ['2.5', '-1', '2.50', 7, '-1.0'];

        $this->assertSame('-1', (string)Decimal::min($values));
        $this->assertSame('7', (string)Decimal::max($values));
        $this->assertSame('2.5', (string)Decimal::max(['2.5', '2.50']));
    }

    /**
     * @return void
     */
    public function testMinEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::min([]);
    }

    /**
     * @return void
     */
    public function testMaxEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::max([]);
    }

    /**
     * @param string $value
     * @param bool $even
     * @param bool $odd
     *
     * @return void
     */
    #[DataProvider('parityProvider')]
    public function testParity(string $value, bool $even, bool $odd): void
    {
        $decimal = new Decimal($value);

        $this->assertSame($even, $decimal->isEven());
        $this->assertSame($odd, $decimal->isOdd());
    }

    /**
     * @return array
     */
    public static function parityProvider(): array
    {
        return [
            'zero' => ['0', true, false],
            'even' => ['12', true, false],
            'odd' => ['-7', false, true],
            'even with zero decimals' => ['4.00', true, false],
            'odd with zero decimals' => ['3.0', false, true],
            'fraction' => ['2.5', false, false],
            'large' => ['123456789012345678901234567891', false, true],
        ];
    }

    /**
     * @return void
     */
    public function testBetween(): void
    {
        $decimal = new Decimal('5.0');

        $this->assertTrue($decimal->between(1, 10));
        $this->assertTrue($decimal->between('5', '5'));
        $this->assertTrue($decimal->between('5', 10));
        $this->assertFalse($decimal->between('5', 10, false));
        $this->assertFalse($decimal->between(1, '5.00', false));
        $this->assertTrue($decimal->between('4.99', '5.01', false));
        $this->assertFalse($decimal->between(6, 10));
    }

    /**
     * @return void
     */
    public function testClamp(): void
    {
        $this->assertSame('5', (string)(new Decimal('5'))->clamp(1, 10));
        $this->assertSame('1', (string)(new Decimal('-3.5'))->clamp(1, 10));
        $this->assertSame('10.00', (string)(new Decimal('12'))->clamp('1', '10.00'));
    }

    /**
     * @return void
     */
    public function testClampInvalidRange(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal('5'))->clamp(10, 1);
    }

    /**
     * @param string $value
     * @param array $ratios
     * @param array<string> $expected
     *
     * @return void
     */
    #[DataProvider('allocateProvider')]
    public function testAllocate(string $value, array $ratios, array $expected): void
    {
        $decimal = new Decimal($value);
        $shares = $decimal->allocate($ratios);

        $this->assertSame($expected, array_map('strval', $shares));
        $this->assertSame((string)$decimal, (string)Decimal::sum($shares)->withScale($decimal->scale()));
    }

    /**
     * @return array
     */
    public static function allocateProvider(): array
    {
        return [
            'even' => ['100.00', [1, 1], ['50.00', '50.00']],
            'thirds' => ['100.00', [1, 1, 1], ['33.34', '33.33', '33.33']],
            'weighted' => ['0.05', [70, 30], ['0.04', '0.01']],
            'decimal ratios' => ['10.00', ['0.7', '0.3'], ['7.00', '3.00']],
            'integer' => ['10', [1, 1, 1], ['4', '3', '3']],
            'negative' => ['-100.00', [1, 1, 1], ['-33.34', '-33.33', '-33.33']],
            'zero ratio gets nothing' => ['0.01', [0, 1, 1], ['0.00', '0.01', '0.00']],
            'keys' => ['1.00', ['a' => 1, 'b' => 3], ['a' => '0.25', 'b' => '0.75']],
            'zero' => ['0.00', [1, 2], ['0.00', '0.00']],
        ];
    }

    /**
     * @param array $ratios
     *
     * @return void
     */
    #[DataProvider('allocateInvalidProvider')]
    public function testAllocateInvalid(array $ratios): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal('1.00'))->allocate($ratios);
    }

    /**
     * @return array
     */
    public static function allocateInvalidProvider(): array
    {
        return [
            'empty' => [[]],
            'all zero' => [[0, 0]],
            'negative' => [[1, -1]],
            'not numeric' => [['a']],
        ];
    }

    /**
     * @return void
     */
    public function testSplit(): void
    {
        $shares = (new Decimal('10.00'))->split(3);

        $this->assertSame(['3.34', '3.33', '3.33'], array_map('strval', $shares));
        $this->assertSame(['5'], array_map('strval', (new Decimal('5'))->split(1)));
    }

    /**
     * @return void
     */
    public function testSplitInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal('10.00'))->split(0);
    }

    /**
     * A global bcmath scale set by the application must not leak into results.
     *
     * @return void
     */
    public function testGlobalBcScale(): void
    {
        $previous = bcscale(2);
        try {
            $this->assertSame('2', (string)(new Decimal('1.5'))->round());
            $this->assertSame('1.3', (string)(new Decimal('1.25'))->round(1));
            $this->assertSame('2', (string)(new Decimal('1.5'))->withScale(0));
            $this->assertSame('20', (string)(new Decimal('15'))->round(-1));
            $this->assertSame('2', (string)(new Decimal('1.5'))->ceil());
            $this->assertSame(['0.50', '0.50'], array_map('strval', (new Decimal('1.00'))->split(2)));
            $this->assertSame(['3.34', '3.33', '3.33'], array_map('strval', (new Decimal('10.00'))->split(3)));
            $this->assertSame('3', (string)Decimal::sum([1, 2]));
            $this->assertSame('1000', (string)new Decimal('1e3'));
            $this->assertSame('1e3', (new Decimal('1000'))->toScientific());
            $this->assertSame(PHP_INT_MAX, (new Decimal((string)PHP_INT_MAX))->toInt());
            $this->assertTrue((new Decimal('4'))->isEven());
            $this->assertSame('0.67', (string)(new Decimal('2'))->divide('3', 2, Decimal::ROUND_HALF_UP));
            $this->assertSame('1.05', (string)(new Decimal('1.03'))->roundToStep('0.05'));
            $this->assertSame(['3', '1'], array_map('strval', (new Decimal('7'))->divideWithRemainder(2)));
        } finally {
            bcscale($previous);
        }
    }

    /**
     * @param string $a
     * @param string $b
     * @param int $scale
     * @param int $mode
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('divideRoundedProvider')]
    public function testDivideRounded(string $a, string $b, int $scale, int $mode, string $expected): void
    {
        $result = (new Decimal($a))->divide($b, $scale, $mode);

        $this->assertSame($expected, (string)$result);
        $this->assertSame($scale, $result->scale());
    }

    /**
     * @return array
     */
    public static function divideRoundedProvider(): array
    {
        return [
            'half up' => ['2', '3', 2, Decimal::ROUND_HALF_UP, '0.67'],
            'half up below' => ['1', '3', 2, Decimal::ROUND_HALF_UP, '0.33'],
            'half up negative' => ['-2', '3', 2, Decimal::ROUND_HALF_UP, '-0.67'],
            'both negative' => ['-2', '-3', 2, Decimal::ROUND_HALF_UP, '0.67'],
            'exact pads' => ['1', '4', 4, Decimal::ROUND_HALF_UP, '0.2500'],
            'tie half up' => ['1', '8', 2, Decimal::ROUND_HALF_UP, '0.13'],
            'tie half down' => ['1', '8', 2, Decimal::ROUND_HALF_DOWN, '0.12'],
            'tie half even' => ['1', '8', 2, Decimal::ROUND_HALF_EVEN, '0.12'],
            'tie half even up' => ['3', '8', 2, Decimal::ROUND_HALF_EVEN, '0.38'],
            'tie half odd' => ['1', '8', 2, Decimal::ROUND_HALF_ODD, '0.13'],
            'up' => ['1', '3', 2, Decimal::ROUND_UP, '0.34'],
            'down' => ['2', '3', 2, Decimal::ROUND_DOWN, '0.66'],
            'floor negative' => ['-1', '3', 2, Decimal::ROUND_FLOOR, '-0.34'],
            'ceil negative' => ['-2', '3', 2, Decimal::ROUND_CEIL, '-0.66'],
            'ceil positive' => ['1', '3', 2, Decimal::ROUND_CEIL, '0.34'],
            'to integer' => ['7', '2', 0, Decimal::ROUND_HALF_UP, '4'],
            'to integer even' => ['5', '2', 0, Decimal::ROUND_HALF_EVEN, '2'],
            'decimal operands' => ['19.99', '0.3', 3, Decimal::ROUND_HALF_UP, '66.633'],
            'small to zero' => ['-0.001', '3', 2, Decimal::ROUND_HALF_UP, '0.00'],
            'carry' => ['9.999', '1', 2, Decimal::ROUND_HALF_UP, '10.00'],
            'large' => ['100000000000000000000', '3', 1, Decimal::ROUND_HALF_UP, '33333333333333333333.3'],
        ];
    }

    /**
     * @return void
     */
    public function testDivideWithoutModeCutsOff(): void
    {
        $this->assertSame('0.66', (string)(new Decimal('2'))->divide('3', 2));
        $this->assertSame('0.66', (string)(new Decimal('2'))->divide('3', 2, null));
    }

    /**
     * @return void
     */
    public function testDivideRoundedInvalidMode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal('2'))->divide('3', 2, PHP_ROUND_HALF_EVEN);
    }

    /**
     * @return void
     */
    public function testDivideRoundedNegativeScale(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal('2'))->divide('3', -1, Decimal::ROUND_HALF_UP);
    }

    /**
     * @return void
     */
    public function testDivideRoundedByZero(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new Decimal('2'))->divide('0.0', 2, Decimal::ROUND_HALF_UP);
    }

    /**
     * @param string $a
     * @param string $b
     * @param string $quotient
     * @param string $remainder
     *
     * @return void
     */
    #[DataProvider('divideWithRemainderProvider')]
    public function testDivideWithRemainder(string $a, string $b, string $quotient, string $remainder): void
    {
        $decimal = new Decimal($a);
        [$q, $r] = $decimal->divideWithRemainder($b);

        $this->assertSame($quotient, (string)$q);
        $this->assertSame($remainder, (string)$r);
        $this->assertTrue($q->multiply($b)->add($r)->equals($decimal));
    }

    /**
     * @return array
     */
    public static function divideWithRemainderProvider(): array
    {
        return [
            'integers' => ['7', '2', '3', '1'],
            'exact' => ['8', '2', '4', '0'],
            'negative dividend' => ['-7', '2', '-3', '-1'],
            'negative divisor' => ['7', '-2', '-3', '1'],
            'decimal divisor' => ['10', '0.3', '33', '0.1'],
            'decimal dividend' => ['10.50', '3', '3', '1.50'],
            'smaller than divisor' => ['1.5', '2', '0', '1.5'],
            'zero' => ['0.00', '3', '0', '0.00'],
        ];
    }

    /**
     * @return void
     */
    public function testDivideWithRemainderByZero(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new Decimal('2'))->divideWithRemainder(0);
    }

    /**
     * @param string $value
     * @param string $step
     * @param int $mode
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('roundToStepProvider')]
    public function testRoundToStep(string $value, string $step, int $mode, string $expected): void
    {
        $this->assertSame($expected, (string)(new Decimal($value))->roundToStep($step, $mode));
    }

    /**
     * @return array
     */
    public static function roundToStepProvider(): array
    {
        return [
            'cash down' => ['1.02', '0.05', Decimal::ROUND_HALF_UP, '1.00'],
            'cash up' => ['1.03', '0.05', Decimal::ROUND_HALF_UP, '1.05'],
            'cash tie' => ['1.025', '0.05', Decimal::ROUND_HALF_UP, '1.05'],
            'cash tie even' => ['1.025', '0.05', Decimal::ROUND_HALF_EVEN, '1.00'],
            'cash negative' => ['-1.03', '0.05', Decimal::ROUND_HALF_UP, '-1.05'],
            'exact' => ['1.10', '0.05', Decimal::ROUND_UP, '1.10'],
            'up' => ['1.01', '0.05', Decimal::ROUND_UP, '1.05'],
            'floor' => ['1.09', '0.05', Decimal::ROUND_FLOOR, '1.05'],
            'quarter' => ['7.4', '0.25', Decimal::ROUND_HALF_UP, '7.50'],
            'integer step' => ['17', '5', Decimal::ROUND_HALF_UP, '15'],
            'integer step tie' => ['17.5', '5', Decimal::ROUND_HALF_UP, '20'],
            'to zero' => ['0.02', '0.05', Decimal::ROUND_HALF_UP, '0.00'],
        ];
    }

    /**
     * @param string $step
     *
     * @return void
     */
    #[DataProvider('roundToStepInvalidProvider')]
    public function testRoundToStepInvalid(string $step): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Decimal('1.02'))->roundToStep($step);
    }

    /**
     * @return array
     */
    public static function roundToStepInvalidProvider(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-0.05'],
        ];
    }

    /**
     * @param float $value
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('createFromFloatProvider')]
    public function testCreateFromFloat(float $value, string $expected): void
    {
        $decimal = Decimal::createFromFloat($value);

        $this->assertSame($expected, (string)$decimal);
        $this->assertSame($value, (float)(string)$decimal);
    }

    /**
     * @return array
     */
    public static function createFromFloatProvider(): array
    {
        return [
            'simple' => [1.1, '1.1'],
            'integer' => [5.0, '5'],
            'negative' => [-23.44, '-23.44'],
            'zero' => [0.0, '0'],
            'beyond precision ini' => [1234567890.1234567, '1234567890.1234567'],
            'sum error visible' => [0.1 + 0.2, '0.30000000000000004'],
            'small' => [1.5e-7, '0.00000015'],
            'large' => [1e25, '10000000000000000000000000'],
        ];
    }

    /**
     * @return void
     */
    public function testCreateFromFloatCommaLocale(): void
    {
        $previous = (string)setlocale(LC_NUMERIC, '0');
        if (setlocale(LC_NUMERIC, 'de_DE.UTF-8', 'de_DE.utf8', 'de_DE') === false) {
            $this->markTestSkipped('No locale with a decimal comma installed.');
        }

        try {
            $this->assertSame('1.1', (string)Decimal::createFromFloat(1.1));
        } finally {
            setlocale(LC_NUMERIC, $previous);
        }
    }

    /**
     * @return void
     */
    public function testCreateFromFloatNonFinite(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::createFromFloat(INF);
    }

    /**
     * @return void
     */
    public function testZeroAndOne(): void
    {
        $this->assertSame('0', (string)Decimal::zero());
        $this->assertSame('1', (string)Decimal::one());
        $this->assertTrue(Decimal::zero()->isZero());
    }

    /**
     * @return void
     */
    public function testAllocateWithZeroScaleDeclared(): void
    {
        $decimal = new Decimal('1.5', 0);
        $shares = $decimal->split(2);

        $this->assertSame(['1', '0'], array_map('strval', $shares));
    }
}
