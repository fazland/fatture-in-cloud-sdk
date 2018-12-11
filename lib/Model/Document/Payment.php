<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class Payment implements \JsonSerializable
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

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return \array_filter([
            'data_scadenza' => null !== $this->dueDate ? $this->dueDate->format('d/m/Y') : null,
            'importo' => $this->amount,
            'metodo' => $this->method,
            'data_saldo' => null !== $this->settlementDate ? $this->settlementDate->format('d/m/Y') : null,
        ]);
    }
}
