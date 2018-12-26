<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Tests\Util\Money;

use Fazland\FattureInCloud\Util\Money\PreciseMoney;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Money\Calculator;
use Money\Currency;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

final class PreciseMoneyTest extends TestCase
{
    private const AMOUNT = 10;
    private const OTHER_AMOUNT = 5;
    private const CURRENCY = 'EUR';
    private const OTHER_CURRENCY = 'USD';

    /**
     * @var Calculator|ObjectProphecy
     */
    private $calculator;

    /**
     * @var PreciseMoney
     */
    private $money;

    protected function setUp(): void
    {
        $this->calculator = $this->prophesize(Calculator::class);

        // Override the calculator for testing
        $reflection = new \ReflectionProperty(PreciseMoney::class, 'calculator');
        $reflection->setAccessible(true);
        $reflection->setValue(null, $this->calculator->reveal());

        $this->money = new PreciseMoney(self::AMOUNT, new Currency(self::CURRENCY));
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionProperty(PreciseMoney::class, 'calculator');
        $reflection->setAccessible(true);
        $reflection->setValue(null, null);
    }

    public function testItIsInitializable(): void
    {
        self::assertInstanceOf(PreciseMoney::class, $this->money);
        self::assertInstanceOf(\JsonSerializable::class, $this->money);
    }

    public function testItHasCorrectAmount(): void
    {
        self::assertEquals(self::AMOUNT, $this->money->getAmount());
    }

    public function testItHasCurrency(): void
    {
        $currency = $this->money->getCurrency();

        self::assertInstanceOf(Currency::class, $currency);
        self::assertTrue($currency->equals(new Currency(self::CURRENCY)));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testItThrowsAnExceptionWhenAmountIsNotNumeric(): void
    {
        new PreciseMoney('ONE', new Currency(self::CURRENCY));
    }

    public function provideValidValueForConstruction(): iterable
    {
        yield [ 5 ];
        yield [ '1.5' ];
        yield [ '5' ];
        yield [ '5.00' ];
        yield [ 5.50 ];
    }

    /**
     * @dataProvider provideValidValueForConstruction
     * @doesNotPerformAssertions
     */
    public function testCanBeConstructedWithValidValue($value): void
    {
        new PreciseMoney($value, new Currency(self::CURRENCY));
    }

    public function testCurrencyEquality(): void
    {
        self::assertTrue($this->money->isSameCurrency(new PreciseMoney(self::AMOUNT, new Currency(self::CURRENCY))));
        self::assertFalse($this->money->isSameCurrency(new PreciseMoney(self::AMOUNT, new Currency(self::OTHER_CURRENCY))));
    }

    public function testEqualsToAnotherMoney(): void
    {
        self::assertTrue($this->money->equals(new PreciseMoney(self::AMOUNT, new Currency(self::CURRENCY))));
        self::assertTrue($this->money->equals(new Money(self::AMOUNT, new Currency(self::CURRENCY))));
    }

    public function provideOtherMoney(): iterable
    {
        yield [ new PreciseMoney(self::AMOUNT, new Currency(self::CURRENCY)) ];
        yield [ new Money(self::AMOUNT, new Currency(self::CURRENCY)) ];
    }

    /**
     * @dataProvider provideOtherMoney
     */
    public function testComparesTwoAmounts($other): void
    {
        $this->calculator->compare((string) self::AMOUNT, (string) self::AMOUNT)->willReturn(0);

        self::assertEquals(0, $this->money->compare($other));
        self::assertFalse($this->money->greaterThan($other));
        self::assertTrue($this->money->greaterThanOrEqual($other));
        self::assertFalse($this->money->lessThan($other));
        self::assertTrue($this->money->lessThanOrEqual($other));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsWhenCurrencyIsDifferentDuringComparison(): void
    {
        $this->calculator->compare(Argument::type('string'), Argument::type('string'))->shouldNotBeCalled();
        $this->money->compare(new PreciseMoney(self::AMOUNT + 1, new Currency(self::OTHER_CURRENCY)));
    }

    public function testItAddsAnOtherMoney(): void
    {
        $result = self::AMOUNT + self::OTHER_AMOUNT;
        $this->calculator->add((string) self::AMOUNT, (string) self::OTHER_AMOUNT)->willReturn((string) $result);
        $money = $this->money->add(new PreciseMoney(self::OTHER_AMOUNT, new Currency(self::CURRENCY)));

        self::assertInstanceOf(PreciseMoney::class, $money);
        self::assertEquals((string) $result, $money->getAmount());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsAnExceptionWhenCurrencyIsDifferentDuringAddition(): void
    {
        $this->calculator->add(Argument::type('string'), Argument::type('string'))->shouldNotBeCalled();
        $this->money->add(new PreciseMoney(self::AMOUNT, new Currency(self::OTHER_CURRENCY)));
    }

    public function testItSubtractsAnOtherMoney(): void
    {
        $result = self::AMOUNT - self::OTHER_AMOUNT;

        $this->calculator->subtract((string) self::AMOUNT, (string) self::OTHER_AMOUNT)->willReturn((string) $result);
        $money = $this->money->subtract(new PreciseMoney(self::OTHER_AMOUNT, new Currency(self::CURRENCY)));

        self::assertInstanceOf(PreciseMoney::class, $money);
        self::assertEquals((string) $result, $money->getAmount());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsAnExceptionWhenCurrencyIsDifferentDuringsubtractition(): void
    {
        $this->calculator->subtract(Argument::type('string'), Argument::type('string'))->shouldNotBeCalled();
        $this->money->subtract(new PreciseMoney(self::AMOUNT, new Currency(self::OTHER_CURRENCY)));
    }

    public function testItMultipliesTheAmount(): void
    {
        $money = new PreciseMoney(1, new Currency(self::CURRENCY));

        $this->calculator->multiply('1', 5)->willReturn(5);
        $this->calculator->round(5, PreciseMoney::ROUND_HALF_UP)->willReturn(5);

        $money = $money->multiply(5);

        self::assertInstanceOf(PreciseMoney::class, $money);
        self::assertEquals('5', $money->getAmount());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testItThrowsAnExceptionWhenOperandIsInvalidDuringMultiplication(): void
    {
        $this->calculator->multiply(Argument::type('string'), Argument::type('numeric'))->shouldNotBeCalled();
        $this->calculator->round(Argument::type('string'), Argument::type('integer'))->shouldNotBeCalled();

        $this->money->multiply('INVALID_OPERAND');
    }

    public function testItDividesTheAmount(): void
    {
        $money = new PreciseMoney(4, new Currency(self::CURRENCY));

        $this->calculator->compare((string) (1 / 2), '0')->willReturn(1 / 2 > 1);
        $this->calculator->divide('4', 1 / 2)->willReturn(2);
        $this->calculator->round(2, PreciseMoney::ROUND_HALF_UP)->willReturn(2);

        $money = $money->divide(1 / 2, PreciseMoney::ROUND_HALF_UP);

        self::assertInstanceOf(PreciseMoney::class, $money);
        self::assertEquals('2', $money->getAmount());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testItThrowsAnExceptionWhenOperandIsInvalidDuringDivision(): void
    {
        $this->calculator->compare(Argument::type('string'), Argument::type('string'))->shouldNotBeCalled();
        $this->calculator->divide(Argument::type('string'), Argument::type('numeric'))->shouldNotBeCalled();
        $this->calculator->round(Argument::type('string'), Argument::type('integer'))->shouldNotBeCalled();

        $this->money->divide('INVALID_OPERAND');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testItThrowsAnExceptionWhenDivisorIsZero(): void
    {
        $this->calculator->compare(0, '0')->willThrow(\InvalidArgumentException::class);
        $this->calculator->divide(Argument::type('string'), Argument::type('numeric'))->shouldNotBeCalled();
        $this->calculator->round(Argument::type('string'), Argument::type('integer'))->shouldNotBeCalled();

        $this->money->divide(0);
        $this->shouldThrow(\InvalidArgumentException::class)->duringDivide(0);
    }

    public function testItAllocatesAmount(): void
    {
        $money = new PreciseMoney(100, new Currency(self::CURRENCY));

        $this->calculator->share(Argument::type('numeric'), Argument::type('int'), Argument::type('int'))->will(function($args) {
            return (int) floor($args[0] * $args[1] / $args[2]);
        });

        $this->calculator->subtract(Argument::type('numeric'), Argument::type('int'))->will(function($args) {
            return (string) $args[0] - $args[1];
        });

        $this->calculator->add(Argument::type('numeric'), Argument::type('int'))->will(function($args) {
            return (string) ($args[0] + $args[1]);
        });

        $this->calculator->compare(Argument::type('numeric'), Argument::type('int'))->will(function($args) {
            return ($args[0] < $args[1]) ? -1 : (($args[0] > $args[1]) ? 1 : 0);
        });

        $this->calculator->absolute(Argument::type('numeric'))->will(function($args) {
            return ltrim($args[0], '-');
        });

        $this->calculator->multiply(Argument::type('numeric'), Argument::type('int'))->will(function($args) {
            return (string) $args[0] * $args[1];
        });

        $allocated = $money->allocate([1, 1, 1]);
        self::assertIsArray($allocated);
        self::assertEqualAllocation($allocated, [34, 33, 33]);
    }

    public function testItAllocatesAmountToNTargets(): void
    {
        $money = new PreciseMoney(15, new Currency(self::CURRENCY));

        $this->calculator->share(Argument::type('numeric'), Argument::type('int'), Argument::type('int'))->will(function($args) {
            return (int) floor($args[0] * $args[1] / $args[2]);
        });

        $this->calculator->subtract(Argument::type('numeric'), Argument::type('int'))->will(function($args) {
            return $args[0] - $args[1];
        });

        $this->calculator->add(Argument::type('numeric'), Argument::type('int'))->will(function($args) {
            return $args[0] + $args[1];
        });

        $this->calculator->compare(Argument::type('numeric'), Argument::type('int'))->will(function($args) {
            return ($args[0] < $args[1]) ? -1 : (($args[0] > $args[1]) ? 1 : 0);
        });

        $allocated = $money->allocateTo(2);
        self::assertIsArray($allocated);
        self::assertEqualAllocation($allocated, [8, 7]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsWhenAllocationTargetIsNegative(): void
    {
        $this->money->allocateTo(-1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsWhenAllocationTargetIsEmpty(): void
    {
        $this->money->allocate([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsWhenAllocationRatioIsNegative(): void
    {
        $this->money->allocate([-1]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsWhenAllocationTotalIsZero(): void
    {
        $this->money->allocate([0, 0]);
    }

    public function testItHasComparators(): void
    {
        $money = new PreciseMoney(1, new Currency(self::CURRENCY));

        $this->calculator->compare(Argument::type('numeric'), Argument::type('int'))->will(function($args) {
            return ($args[0] < $args[1]) ? -1 : (($args[0] > $args[1]) ? 1 : 0);
        });

        self::assertFalse($money->isZero());
        self::assertTrue($money->isPositive());
        self::assertFalse($money->isNegative());
    }

    public function testAbsoluteAmount(): void
    {
        $money = new PreciseMoney(-1.5, new Currency(self::CURRENCY));

        $this->calculator->absolute('-1.5')->willReturn('1.5');

        $money = $money->absolute();

        self::assertInstanceOf(PreciseMoney::class, $money);
        self::assertEquals('1.5', $money->getAmount());
    }

    private static function assertEqualAllocation($subject, $value): void
    {
        /** @var PreciseMoney $money */
        foreach ($subject as $key => $money) {
            $compareTo = new PreciseMoney($value[$key], $money->getCurrency());
            self::assertTrue($money->equals($compareTo));
        }
    }
}
