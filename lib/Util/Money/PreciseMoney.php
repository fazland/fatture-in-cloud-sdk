<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Util\Money;

use Money\Calculator;
use Money\Calculator\BcMathCalculator;
use Money\Calculator\GmpCalculator;
use Money\Currency;
use Money\Money;
use Money\Number;

/**
 * PreciseMoney Value Object.
 *
 * Imported from moneyphp/money precise branch
 */
final class PreciseMoney implements \JsonSerializable
{
    public const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    public const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    public const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    public const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;
    public const ROUND_UP = 5;
    public const ROUND_DOWN = 6;
    public const ROUND_HALF_POSITIVE_INFINITY = 7;
    public const ROUND_HALF_NEGATIVE_INFINITY = 8;

    /**
     * Internal value.
     *
     * @var string
     */
    private $amount;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var Calculator
     */
    private static $calculator;

    /**
     * @var array
     */
    private static $calculators = [
        BcMathCalculator::class,
        GmpCalculator::class,
    ];

    /**
     * @param int|float|string $amount   Amount, expressed in the smallest units of $currency (eg cents)
     * @param Currency         $currency
     *
     * @throws \InvalidArgumentException If amount is not integer
     */
    public function __construct($amount, Currency $currency)
    {
        if (! \is_int($amount) && ! \is_float($amount) && ! \is_string($amount)) {
            throw new \InvalidArgumentException('Amount must be a string');
        }

        $this->amount = (string) Number::fromString((string) $amount);
        $this->currency = $currency;
    }

    /**
     * Convenience factory method for a Money object.
     *
     * <code>
     * $fiveDollar = PreciseMoney::USD(500);
     * </code>
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return self
     *
     * @throws \InvalidArgumentException If amount is not integer
     */
    public static function __callStatic($method, $arguments): self
    {
        return new self($arguments[0], new Currency($method));
    }

    /**
     * Returns a new Money instance based on the current one using the Currency.
     *
     * @param string $amount
     *
     * @return self
     *
     * @throws \InvalidArgumentException If amount is not integer
     */
    private function newInstance($amount): self
    {
        return new self($amount, $this->currency);
    }

    /**
     * Checks whether a Money has the same Currency as this.
     *
     * @param Money|PreciseMoney $other
     *
     * @return bool
     */
    public function isSameCurrency($other): bool
    {
        if (! $other instanceof Money && ! $other instanceof self) {
            throw new \TypeError(\sprintf(
                'Argument 1 passed to %s must be of type Money or PreciseMoney. %s passed',
                __METHOD__,
                \is_object($other) ? \get_class($other) : \gettype($other)
            ));
        }

        return $this->currency->equals($other->getCurrency());
    }

    /**
     * Asserts that a Money has the same currency as this.
     *
     * @param PreciseMoney $other
     *
     * @throws \InvalidArgumentException If $other has a different currency
     */
    private function assertSameCurrency($other): void
    {
        if (! $this->isSameCurrency($other)) {
            throw new \InvalidArgumentException('Currencies must be identical');
        }
    }

    /**
     * Checks whether the value represented by this object equals to the other.
     *
     * @param Money|PreciseMoney $other
     *
     * @return bool
     */
    public function equals($other): bool
    {
        return $this->isSameCurrency($other) && $this->amount === $other->getAmount();
    }

    /**
     * Returns an integer less than, equal to, or greater than zero
     * if the value of this object is considered to be respectively
     * less than, equal to, or greater than the other.
     *
     * @param Money|PreciseMoney $other
     *
     * @return int
     */
    public function compare($other): int
    {
        $this->assertSameCurrency($other);

        return $this->getCalculator()->compare($this->amount, $other->getAmount());
    }

    /**
     * Checks whether the value represented by this object is greater than the other.
     *
     * @param Money|PreciseMoney $other
     *
     * @return bool
     */
    public function greaterThan($other): bool
    {
        return 1 === $this->compare($other);
    }

    /**
     * @param Money|PreciseMoney $other
     *
     * @return bool
     */
    public function greaterThanOrEqual($other): bool
    {
        return $this->compare($other) >= 0;
    }

    /**
     * Checks whether the value represented by this object is less than the other.
     *
     * @param Money|PreciseMoney $other
     *
     * @return bool
     */
    public function lessThan($other): bool
    {
        return -1 === $this->compare($other);
    }

    /**
     * @param PreciseMoney $other
     *
     * @return bool
     */
    public function lessThanOrEqual($other): bool
    {
        return $this->compare($other) <= 0;
    }

    /**
     * Returns the value represented by this object.
     *
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Returns the currency of this object.
     *
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Returns a new Money object that represents
     * the sum of this and an other Money object.
     *
     * @param Money|PreciseMoney $addend
     *
     * @return self
     */
    public function add($addend): self
    {
        $this->assertSameCurrency($addend);

        return new self($this->getCalculator()->add($this->amount, $addend->getAmount()), $this->currency);
    }

    /**
     * Returns a new Money object that represents
     * the difference of this and an other Money object.
     *
     * @param Money|PreciseMoney $subtrahend
     *
     * @return self
     */
    public function subtract($subtrahend): self
    {
        $this->assertSameCurrency($subtrahend);

        return new self($this->getCalculator()->subtract($this->amount, $subtrahend->getAmount()), $this->currency);
    }

    /**
     * Asserts that the operand is integer or float.
     *
     * @param float|int|string $operand
     *
     * @throws \InvalidArgumentException If $operand is neither integer nor float
     */
    private function assertOperand($operand): void
    {
        if (! \is_numeric($operand)) {
            throw new \InvalidArgumentException(\sprintf(
                'Operand should be a numeric value, "%s" given.',
                \is_object($operand) ? \get_class($operand) : \gettype($operand)
            ));
        }
    }

    /**
     * Returns a new Money object that represents
     * the multiplied value by the given factor.
     *
     * @param float|int|string $multiplier
     *
     * @return self
     */
    public function multiply($multiplier): self
    {
        $this->assertOperand($multiplier);

        $product = $this->getCalculator()->multiply($this->amount, $multiplier);

        return $this->newInstance($product);
    }

    /**
     * Returns a new Money object that represents
     * the divided value by the given factor.
     *
     * @param float|int|string $divisor
     *
     * @return self
     */
    public function divide($divisor): self
    {
        $this->assertOperand($divisor);

        if (0 === $this->getCalculator()->compare((string) $divisor, '0')) {
            throw new \InvalidArgumentException('Division by zero');
        }

        $quotient = $this->getCalculator()->divide($this->amount, $divisor);

        return $this->newInstance($quotient);
    }

    /**
     * Allocate the money according to a list of ratios.
     *
     * @param array $ratios
     *
     * @return self[]
     */
    public function allocate(array $ratios): array
    {
        if (0 === \count($ratios)) {
            throw new \InvalidArgumentException('Cannot allocate to none, ratios cannot be an empty array');
        }

        $remainder = $this->amount;
        $results = [];
        $total = \array_sum($ratios);

        if ($total <= 0) {
            throw new \InvalidArgumentException('Cannot allocate to none, sum of ratios must be greater than zero');
        }

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw new \InvalidArgumentException('Cannot allocate to none, ratio must be zero or positive');
            }

            $share = $this->getCalculator()->share($this->amount, $ratio, $total);
            $results[] = $this->newInstance($share);
            $remainder = $this->getCalculator()->subtract($remainder, $share);
        }

        for ($i = 0; 1 === $this->getCalculator()->compare($remainder, 0); ++$i) {
            $results[$i]->amount = (string) $this->getCalculator()->add($results[$i]->amount, 1);
            $remainder = $this->getCalculator()->subtract($remainder, 1);
        }

        return $results;
    }

    /**
     * Allocate the money among N targets.
     *
     * @param int $n
     *
     * @return self[]
     *
     * @throws \InvalidArgumentException If number of targets is not an integer
     */
    public function allocateTo(int $n): array
    {
        if ($n <= 0) {
            throw new \InvalidArgumentException('Cannot allocate to none, target must be greater than zero');
        }

        return $this->allocate(\array_fill(0, $n, 1));
    }

    /**
     * @return self
     */
    public function absolute(): self
    {
        return $this->newInstance($this->getCalculator()->absolute($this->amount));
    }

    /**
     * Checks if the value represented by this object is zero.
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return 0 === $this->getCalculator()->compare($this->amount, 0);
    }

    /**
     * Checks if the value represented by this object is positive.
     *
     * @return bool
     */
    public function isPositive(): bool
    {
        return 1 === $this->getCalculator()->compare($this->amount, 0);
    }

    /**
     * Checks if the value represented by this object is negative.
     *
     * @return bool
     */
    public function isNegative(): bool
    {
        return -1 === $this->getCalculator()->compare($this->amount, 0);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    /**
     * @param string $calculator
     */
    public static function registerCalculator($calculator): void
    {
        if (false === \is_a($calculator, Calculator::class, true)) {
            throw new \InvalidArgumentException('Calculator must implement '.Calculator::class);
        }

        \array_unshift(self::$calculators, $calculator);
    }

    /**
     * @return Calculator
     *
     * @throws \RuntimeException If cannot find calculator for money calculations
     */
    private static function initializeCalculator(): Calculator
    {
        $calculators = self::$calculators;

        foreach ($calculators as $calculator) {
            /** @var Calculator $calculator */
            if ($calculator::supported()) {
                return new $calculator();
            }
        }

        throw new \RuntimeException('Cannot find calculator for money calculations');
    }

    /**
     * @return Calculator
     */
    private function getCalculator(): Calculator
    {
        if (null === self::$calculator) {
            self::$calculator = self::initializeCalculator();
        }

        return self::$calculator;
    }
}
