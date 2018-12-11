<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

use Fazland\FattureInCloud\Model\Subject\Subject;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Money\Currency;
use Money\CurrencyPair;
use Money\Money;

/**
 * @property null|EmbeddedTransportDocument $transportDocument
 */
abstract class Document implements \JsonSerializable
{
    public const TOTALS_SHOW_ALL = 'tutti';
    public const TOTALS_SHOW_NETS = 'netti';
    public const TOTALS_HIDE_ALL = 'nessuno';

    /**
     * Document identifier.
     *
     * @var string
     */
    public $id;

    /**
     * Permanent identifier of the document.
     *
     * @var string
     */
    public $token;

    /**
     * Customer or supplier.
     *
     * @var Subject
     */
    public $subject;

    /**
     * Language (2-digit code: it, en, de).
     *
     * @var string
     */
    public $language;

    /**
     * Invoice number and series.
     * If only a series (only non-numeric chars) is specified, the next
     * invoice number for that series will be used.
     *
     * @var string
     */
    public $number;

    /**
     * Invoice date. If missing the current date will be used.
     *
     * @var \DateTimeInterface
     */
    public $date;

    /**
     * The currency of this document. Default EUR.
     *
     * @var Currency
     */
    public $currency;

    /**
     * The exchange ratio on EUR.
     * Will use the current exchange ratio if null.
     *
     * @var CurrencyPair
     */
    public $exchangeRatio;

    /**
     * Whether the prices on the document are VAT included.
     *
     * @var bool
     */
    public $vatIncluded;

    /**
     * The withholding tax ratio.
     *
     * @var float
     */
    public $withholdingTaxRatio;

    /**
     * The withholding tax income ratio upon total.
     *
     * @var float
     */
    public $withholdingTaxIncome;

    /**
     * The other withholding ratio (if applicable).
     *
     * @var float
     */
    public $withholdingOtherRatio;

    /**
     * Value of the stamp.
     *
     * @var Money
     */
    public $stamp;

    /**
     * Visible document subject (ex: shown in invoice).
     *
     * @var string
     */
    public $documentSubject;

    /**
     * Invisible (internal) document subject.
     *
     * @var string
     */
    public $documentInternalSubject;

    /**
     * Notes (in HTML).
     *
     * @var string
     */
    public $notes;

    /**
     * Whether to hide or not the document expiration.
     *
     * @var bool
     */
    public $hideExpiration;

    /**
     * Whether a accompanying invoice is present.
     *
     * @var bool
     */
    public $accompanyingInvoice;

    /**
     * Template identifier.
     *
     * @var string
     */
    public $templateId;

    /**
     * Transport document embedded of this document.
     *
     * @var null|EmbeddedTransportDocument
     */
    private $transportDocument;

    /**
     * Accompanying invoice template identifier.
     *
     * @var string
     */
    public $accompanyingInvoiceTemplateId;

    /**
     * Show payment info.
     *
     * @var string
     */
    public $showPaymentInfo;

    /**
     * Payment method name.
     *
     * @var PaymentMethod
     */
    public $paymentMethod;

    /**
     * Show totals on the document (only for quotations, reports and supplier orders).
     *
     * @var string
     */
    public $showTotals;

    /**
     * Show the "Pay with PayPal" button (only for receipts, invoices, proforma, orders).
     *
     * @var bool
     */
    public $showPayWithPayPal;

    /**
     * Show the "Pay with immediate bank transfer" button (only for receipts, invoices, proforma, orders).
     *
     * @var bool
     */
    public $showPayWithBankTransfer;

    /**
     * Show the "Notify payment executed" button (only for receipts, invoices, proforma, orders).
     *
     * @var bool
     */
    public $showNotifyPaymentExecuted;

    /**
     * Goods array.
     *
     * @var Good[]
     */
    public $goods;

    /**
     * Payments array.
     *
     * @var Payment[]
     */
    public $payments;

    /**
     * Public administration (also in case of electronic invoices).
     *
     * @var PublicAdministration
     */
    public $publicAdministration;

    /**
     * Split payment.
     *
     * @var bool
     */
    public $splitPayment;

    /**
     * Whether to fill missing subject fields automatically.
     *
     * @var bool
     */
    private $autocompleteSubject;

    /**
     * Whether to save/update subject automatically.
     *
     * @var bool
     */
    private $autosaveSubject;

    /**
     * The client used to retrieve this object.
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * The original data, as fetched from the APIs.
     *
     * @var array
     */
    private $originalData;

    public function __construct()
    {
        $this->currency = new Currency('EUR');
        $this->paymentMethod = new PaymentMethod();
        $this->goods = [];
        $this->payments = [];
        $this->autocompleteSubject = false;
        $this->autosaveSubject = false;
    }

    public function addProduct(Good $good): self
    {
        $this->goods[] = $good;

        return $this;
    }

    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;

        return $this;
    }

    public function enableAutocompleteSubject(): void
    {
        $this->autocompleteSubject = true;
    }

    public function enableAutosaveSubject(): void
    {
        $this->autosaveSubject = true;
    }

    public function &__get($name)
    {
        switch ($name) {
            case 'transportDocument':
                return $this->transportDocument;

            default:
                throw new \Error('Undefined property "'.$name.'"');
        }
    }

    public function &__set($name, $value)
    {
        switch ($name) {
            case 'transportDocument':
                if (null !== $value && ! $value instanceof EmbeddedTransportDocument) {
                    throw new \TypeError(sprintf(
                        'transportDocument must be of type %s or null. %s passed.',
                        EmbeddedTransportDocument::class,
                        is_object($value) ? get_class($value) : gettype($value)
                    ));
                }

                return $this->transportDocument = $value;

            default:
                throw new \Error('Undefined property "'.$name.'"');
        }
    }

    /**
     * Creates a new object on the API server.
     *
     * @param ClientInterface $client
     *
     * @return self
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function create(ClientInterface $client): self
    {
        $this->client = $client;
        $path = '/'.static::getType().'/nuovo';

        $response = $this->client->request('POST', $path, $this);

        $result = Json::decode((string) $response->getBody(), true);
        $this->id = $result['id'];
        $this->token = $result['token'];

        return $this;
    }

    public function jsonSerialize()
    {
        if (null === $this->subject) {
            throw new \RuntimeException('Subject is not defined');
        }

        $address = $this->subject->address->jsonSerialize();
        $address = \array_combine(
            \array_map(function (string $key): string { return 'indirizzo_'.$key; }, \array_keys($address)),
            \array_values($address)
        );

        $payment = $this->paymentMethod->jsonSerialize();

        if (0 === \count($this->goods)) {
            throw new \RuntimeException('No products added');
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        return array_filter([
            'id_cliente' => $this instanceof SupplierOrder ? null : $this->subject->id,
            'id_fornitore' => $this instanceof SupplierOrder ? $this->subject->id : null,
            'nome' => $this->subject->name,
            'paese' => $this->subject->country,
            'lingua' => $this->language,
            'piva' => $this->subject->vatNumber,
            'cf' => $this->subject->fiscalCode,
            'autocompila_anagrafica' => $this->autocompleteSubject,
            'salva_anagrafica' => $this->autosaveSubject,
            'numero' => $this->number,
            'data' => null !== $this->date ? $this->date->format('d/m/Y') : null,
            'valuta' => $this->currency->getCode(),
            'valuta_cambio' => null !== $this->exchangeRatio ? $this->exchangeRatio->getConversionRatio() : null,
            'prezzi_ivati' => $this->vatIncluded,
            'rit_acconto' => $this->withholdingTaxRatio,
            'imponibile_ritenuta' => $this->withholdingTaxIncome,
            'rit_altra' => $this->withholdingOtherRatio,
            'marca_bollo' => null !== $this->stamp ? (float) $this->stamp->getAmount() : null,
            'oggetto_visibile' => $this->documentSubject,
            'oggetto_interno' => $this->documentInternalSubject,
            'note' => $this->notes,
            'nascondi_scadenza' => $this->hideExpiration,
            'ddt' => null !== $this->transportDocument,
            'ftacc' => $this->accompanyingInvoice,
            'id_template' => $this->templateId,
            'ddt_id_template' => null !== $this->transportDocument ? $this->transportDocument->templateId : null,
            'ftacc_id_template' => $this->accompanyingInvoiceTemplateId,
            'mostra_info_pagamento' => $this->showPaymentInfo,
            'mostra_totali' => $this->showTotals,
            'mostra_bottone_paypal' => $this->showPayWithPayPal,
            'mostra_bottone_bonifico' => $this->showPayWithBankTransfer,
            'mostra_bottone_notifica' => $this->showNotifyPaymentExecuted,
            'lista_articoli' => $this->goods,
            'lista_pagamenti' => $this->payments,
            'ddt_numero' => null !== $this->transportDocument ? $this->transportDocument->number : null,
            'ddt_data' => null !== $this->transportDocument && null !== $this->transportDocument->date ?
                $this->transportDocument->date->format('d/m/Y') : null,
            'ddt_colli' => null !== $this->transportDocument ? $this->transportDocument->packs : null,
            'ddt_peso' => null !== $this->transportDocument ? $this->transportDocument->weight : null,
            'ddt_causale' => null !== $this->transportDocument ? $this->transportDocument->causal : null,
            'ddt_luogo' => null !== $this->transportDocument ? $this->transportDocument->place : null,
            'ddt_trasportatore' => null !== $this->transportDocument ? $this->transportDocument->transporterData : null,
            'ddt_annotazioni' => null !== $this->transportDocument ? $this->transportDocument->annotations : null,
            'PA' => null !== $this->publicAdministration,
            'PA_tipo_cliente' => null !== $this->publicAdministration ? $this->publicAdministration->type : null,
            'PA_tipo' => null !== $this->publicAdministration ? $this->publicAdministration->documentType : null,
                'PA_numero' => null !== $this->publicAdministration ? $this->publicAdministration->documentNumber : null,
            'PA_data' => null !== $this->publicAdministration && null !== $this->publicAdministration->date ?
                $this->publicAdministration->date->format('d/m/Y') : null,
            'PA_cup' => null !== $this->publicAdministration ? $this->publicAdministration->cup : null,
            'PA_cig' => null !== $this->publicAdministration ? $this->publicAdministration->cig : null,
            'PA_codice' => null !== $this->publicAdministration ? $this->publicAdministration->destinationCode : null,
            'PA_pec' => null !== $this->publicAdministration ? $this->publicAdministration->certifiedEmail : null,
            'PA_esigibilita' => null !== $this->publicAdministration ? $this->publicAdministration->vatCollectability : null,
            'PA_modalita_pagamento' => null !== $this->publicAdministration ? $this->publicAdministration->paymentMethod : null,
            'PA_istituto_credito' => null !== $this->publicAdministration ? $this->publicAdministration->creditInstitution : null,
            'PA_iban' => null !== $this->publicAdministration ? $this->publicAdministration->iban : null,
            'PA_beneficiario' => null !== $this->publicAdministration ? $this->publicAdministration->payee : null,
            'extra_anagrafica' => array_filter([
                'mail' => $this->subject->mail,
                'tel' => $this->subject->phone ? $phoneUtil->format($this->subject->phone, PhoneNumberFormat::E164) : null,
                'fax' => $this->subject->fax ? $phoneUtil->format($this->subject->fax, PhoneNumberFormat::E164) : null,
            ]),
            'split_payment' => $this->splitPayment,
        ] + $address + $payment);
    }

    /**
     * Gets the document type.
     *
     * @return string
     */
    abstract public static function getType(): string;
}
