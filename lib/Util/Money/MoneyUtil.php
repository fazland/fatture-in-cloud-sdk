<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Util\Money;

use Money\Currencies;
use Money\Currency;
use Money\Money;

final class MoneyUtil
{
    /**
     * @var Currencies
     */
    private static $currencies;

    /**
     * Converts an amount to a money instance.
     *
     * @param $amount
     * @param Currency|null $currency
     *
     * @return PreciseMoney
     */
    public static function toMoney($amount, Currency $currency = null): PreciseMoney
    {
        if ($amount instanceof Money || $amount instanceof PreciseMoney) {
            $currency = $amount->getCurrency();
            $amount = $amount->getAmount();
        }

        if (null === $currency) {
            throw new \ArgumentCountError(\sprintf(
                'Too few arguments to function %s(), 1 passed, 2 expected.',
                __METHOD__
            ));
        }

        $subUnit = self::getCurrencies()->subunitFor($currency);

        return new PreciseMoney($amount * (10 ** $subUnit), $currency);
    }

    private static function getCurrencies(): Currencies
    {
        if (null === self::$currencies) {
            self::$currencies = new Currencies\AggregateCurrencies([
                new Currencies\ISOCurrencies(),
                new Currencies\BitcoinCurrencies(),
            ]);
        }

        return self::$currencies;
    }
}
