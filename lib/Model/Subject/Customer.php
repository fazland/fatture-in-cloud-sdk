<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Subject;

final class Customer extends Subject
{
    /**
     * Default payment terms (in days).
     *
     * @var int
     */
    public $paymentTerms;

    /**
     * Whether the payment happens at the end of the month (after payment terms are due).
     *
     * @var bool
     */
    public $endMonthPayment;

    /**
     * Default vat index value.
     *
     * @var float
     */
    public $defaultVatValue;

    /**
     * Default VAT description.
     *
     * @var string
     */
    public $defaultVatDescription;

    /**
     * Whether this customer is a public administration entity.
     *
     * @var bool
     */
    public $publicAdministration;

    /**
     * Public administration entity code (only if publicAdministration is true).
     *
     * @var string
     */
    public $publicAdministrationCode;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + \array_filter([
            'termini_pagamento' => $this->paymentTerms,
            'pagamento_fine_mese' => $this->endMonthPayment,
            'val_iva_default' => $this->defaultVatValue,
            'desc_iva_default' => $this->defaultVatDescription,
            'PA' => $this->publicAdministration,
            'PA_codice' => $this->publicAdministrationCode,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function fromArray(array $data): Subject
    {
        parent::fromArray($data);

        $this->paymentTerms = $data['termini_pagamento'] ?? null;
        $this->endMonthPayment = $data['pagamento_fine_mese'] ?? null;
        $this->defaultVatValue = $data['val_iva_default'] ?? null;
        $this->defaultVatDescription = $data['desc_iva_default'] ?? null;
        $this->publicAdministration = $data['PA'] ?? null;
        $this->publicAdministrationCode = $data['PA_codice'] ?? null;

        return $this;
    }
}
