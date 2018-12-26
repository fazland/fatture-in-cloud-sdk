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

        $currency = $currency ?? new Currency('EUR');
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
