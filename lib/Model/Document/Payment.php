<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class Payment
{
    /**
     * Payment due date.
     *
     * @var \DateTimeInterface
     */
    public $dueDate;

    /**
     * Amount of this payment.
     *
     * @var string
     */
    public $amount = 'auto';

    /**
     * Account name or
     * 'not' if not payed
     * 'rev' if reversed.
     *
     * @var string
     */
    public $method;

    /**
     * Date of payment.
     *
     * @var \DateTimeInterface
     */
    public $settlementDate;
}
