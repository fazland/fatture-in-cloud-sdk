<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Model\Subject\Customer;
use Fazland\FattureInCloud\Model\Subject\CustomerList;
use Fazland\FattureInCloud\Model\Subject\Subject;
use Fazland\FattureInCloud\Model\Subject\Supplier;
use Fazland\FattureInCloud\Model\Subject\SupplierList;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Money\Currency;
use Money\CurrencyPair;
use Money\Money;

/**
 * @property null|EmbeddedTransportDocument $transportDocument
 * @property null|float $netAmount
 * @property null|float $vatAmount
 * @property null|float $grossAmount
 * @property null|float $withholdingAmount
 * @property null|float $withholdingOtherAmount
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
     * The net amount of this document.
     *
     * @var float
     */
    private $netAmount;

    /**
     * The vat amount of this document.
     *
     * @var float
     */
    private $vatAmount;

    /**
     * The vat amount of this document.
     *
     * @var float
     */
    private $grossAmount;

    /**
     * The withholding tax amount of this document.
     *
     * @var float
     */
    private $withholdingAmount;

    /**
     * The withholding other amount of this document.
     *
     * @var float
     */
    private $withholdingOtherAmount;

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

    public function __get($name)
    {
        switch ($name) {
            case 'transportDocument':
                return $this->transportDocument;

            case 'netAmount':
                return $this->netAmount;

            case 'vatAmount':
                return $this->vatAmount;

            case 'grossAmount':
                return $this->grossAmount;

            case 'withholdingAmount':
                return $this->withholdingAmount;

            case 'withholdingOtherAmount':
                return $this->withholdingOtherAmount;

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
     * Gets the document details.
     *
     * @param string $token
     * @param ClientInterface $client
     *
     * @return Document
     */
    public static function get(string $token, ClientInterface $client): self
    {
        $path = '/'.static::getType().'/dettagli';

        $response = $client->request('POST', $path, [
            'token' => $token,
        ]);

        $result = Json::decode((string) $response->getBody(), true);

        $obj = new static();
        $obj->client = $client;
        $obj->fromArray($result);

        return $obj;
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

        $path = '/'.static::getType().'/dettagli';
        $response = $client->request('POST', $path, [
            'token' => $this->token,
        ]);

        $this->fromArray(Json::decode((string) $response->getBody(), true));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
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

    /**
     * Initializes the object from a response array.
     *
     * @param array $body
     */
    protected function fromArray(array $body): void
    {
        $this->autocompleteSubject = false;
        $this->autosaveSubject = false;

        $this->id = $body['id'];
        $this->token = $body['token'];

        if (isset($body['id_cliente'])) {
            $subject = new Customer();
            $subject->id = $body['id_cliente'];
        } else {
            $subject = new Supplier();
            $subject->id = $body['id_fornitore'];
        }

        $subject->name = $body['nome'];
        $subject->address->street = $body['indirizzo_via'];
        $subject->address->zip = $body['indirizzo_cap'];
        $subject->address->city = $body['indirizzo_citta'];
        $subject->address->province = $body['indirizzo_provincia'];
        $subject->address->extra = $body['indirizzo_extra'] ?? null;
        $subject->country = $body['paese'];
        $subject->vatNumber = $body['piva'];
        $subject->fiscalCode = $body['cf'];

        $this->subject = $subject;

        $this->language = $body['lingua'] ?? null;
        $this->number = $body['numero'];
        $this->date = \DateTimeImmutable::createFromFormat('d/m/Y', $body['data']);
        $this->currency = new Currency($body['valuta']);

        if (isset($body['valuta_cambio'])) {
            $this->exchangeRatio = new CurrencyPair(new Currency('EUR'), new Currency($body['valuta']), $body['valuta_cambio']);
        }

        $this->vatIncluded = $body['prezzi_ivati'];
        $this->netAmount = $body['importo_netto'];
        $this->vatAmount = $body['importo_iva'];
        $this->grossAmount = $body['importo_totale'];

        $this->withholdingTaxRatio = $body['rit_acconto'] ?? null;
        $this->withholdingTaxIncome = $body['imponibile_ritenuta'] ?? null;
        $this->withholdingOtherRatio = $body['rit_altra'] ?? null;

        $this->withholdingAmount = $body['importo_rit_acconto'] ?? null;
        $this->withholdingOtherAmount = $body['importo_rit_altra'] ?? null;

        $this->stamp = isset($body['marca_bollo']) ? new Money($body['marca_bollo'] * 100, $this->currency) : null;
        $this->documentSubject = $body['oggetto_visibile'] ?? null;
        $this->documentInternalSubject = $body['oggetto_interno'] ?? null;
        $this->notes = $body['note'] ?? null;
        $this->hideExpiration = $body['nascondi_scadenza'] ?? null;

        if ($body['ddt'] ?? false) {
            $this->transportDocument = new EmbeddedTransportDocument();
            $this->transportDocument->templateId = $body['ddt_id_template'] ?? null;

            $this->transportDocument->number = $body['ddt_numero'] ?? null;
            $this->transportDocument->date = isset($body['ddt_data']) ? \DateTimeImmutable::createFromFormat('d/m/Y', $body['ddt_data']) : null;
            $this->transportDocument->packs = $body['ddt_colli'] ?? null;
            $this->transportDocument->weight = $body['ddt_peso'] ?? null;
            $this->transportDocument->causal = $body['ddt_causale'] ?? null;
            $this->transportDocument->place = $body['ddt_luogo'] ?? null;
            $this->transportDocument->transporterData = $body['ddt_trasportatore'] ?? null;
            $this->transportDocument->annotations = $body['ddt_annotazioni'] ?? null;
        }

        $this->accompanyingInvoice = $body['ftacc'] ?? false;
        $this->templateId = $body['template_id'] ?? null;
        $this->accompanyingInvoiceTemplateId = $body['ftacc_id_template'] ?? null;

        $this->paymentMethod = new PaymentMethod();
        if ($this->showPaymentInfo = $body['mostra_info_pagamento'] ?? false) {
            $this->paymentMethod->name = $body['metodo_pagamento'];
            $this->paymentMethod->title = \implode("\n", \array_filter([
                $body['metodo_titolo1'],
                $body['metodo_titolo2'],
                $body['metodo_titolo3'],
                $body['metodo_titolo4'],
                $body['metodo_titolo5']
            ])) ?: null;
            $this->paymentMethod->description = \implode("\n", \array_filter([
                $body['metodo_desc1'],
                $body['metodo_desc2'],
                $body['metodo_desc3'],
                $body['metodo_desc4'],
                $body['metodo_desc5']
            ])) ?: null;
        }

        $this->showTotals = $body['mostra_totali'] ?? null;
        $this->showPayWithPayPal = $body['mostra_bottone_paypal'] ?? null;
        $this->showPayWithBankTransfer = $body['mostra_bottone_bonifico'] ?? null;
        $this->showNotifyPaymentExecuted = $body['mostra_bottone_notifica'] ?? null;

        $this->goods = [];
        $this->payments = [];
        foreach ($body['lista_articoli'] ?? [] as $item) {
            $good = new Good();
            $good->id = $item['id'] ?? null;
            $good->code = $item['codice'] ?? null;
            $good->name = $item['nome'] ?? null;
            $good->mu = $item['um'] ?? null;
            $good->qty = $item['quantita'] ?? null;
            $good->description = $item['descrizione'] ?? null;
            $good->category = $item['categoria'] ?? null;
            $good->netPrice = isset($item['prezzo_netto']) ? new Money($item['prezzo_netto'] * 100, $this->currency) : null;
            $good->grossPrice = isset($item['prezzo_lordo']) ? new Money($item['prezzo_lordo'] * 100, $this->currency) : null;
            $good->vatAmount = isset($item['valore_iva']) ? new Money($item['valore_iva'] * 100, $this->currency) : null;
            $good->taxable = $item['tassabile'] ?? null;
            $good->discount = $item['sconto'] ?? null;
            $good->applyWithholdingAndContributions = $item['applica_ra_contributi'] ?? null;
            $good->order = $item['ordine'] ?? null;
            $good->highlightDiscount = $item['sconto_rosso'] ?? null;
            $good->inTransportDocument = $item['in_ddt'] ?? null;
            $good->fromWarehouse = $item['magazzino'] ?? null;

            $this->goods[] = $good;
        }

        foreach ($body['lista_pagamenti'] ?? [] as $item) {
            $payment = new Payment();
            $payment->dueDate = isset($item['data_scadenza']) ? \DateTimeImmutable::createFromFormat('d/m/Y', $item['data_scadenza']) : null;
            $payment->amount = $item['importo'] ?? null;
            $payment->method = $item['metodo'] ?? null;
            $payment->settlementDate = isset($item['data_saldo']) ? \DateTimeImmutable::createFromFormat('d/m/Y', $item['data_saldo']) : null;

            $this->payments[] = $payment;
        }

        $this->links = new Links();
        $this->links->document = $body['link_doc'] ?? null;
        $this->links->transportDocument = $body['link_ddt'] ?? null;
        $this->links->accompanyingInvoice = $body['link_ftacc'] ?? null;
        $this->links->attachment = $body['link_allegato'] ?? null;

        if ($body['PA'] ?? false) {
            $this->publicAdministration = new PublicAdministration();
            $this->publicAdministration->type = $body['PA_tipo_cliente'] ?? null;
            $this->publicAdministration->documentType = $body['PA_tipo'] ?? null;
            $this->publicAdministration->documentNumber = $body['PA_numero'] ?? null;
            $this->publicAdministration->date = isset($body['PA_data']) ? \DateTimeImmutable::createFromFormat('d/m/Y', $body['PA_data']) : null;
            $this->publicAdministration->cup = $body['PA_cup'] ?? null;
            $this->publicAdministration->cig = $body['PA_cig'] ?? null;
            $this->publicAdministration->destinationCode = $body['PA_codice'] ?? null;
            $this->publicAdministration->vatCollectability = $body['PA_esigibilita'] ?? null;
            $this->publicAdministration->paymentMethod = $body['PA_modalita_pagamento'] ?? null;
            $this->publicAdministration->creditInstitution = $body['PA_istituto_credito'] ?? null;
            $this->publicAdministration->iban = $body['PA_iban'] ?? null;
            $this->publicAdministration->payee = $body['PA_beneficiario'] ?? null;
            $this->publicAdministration->tsStatus = ($body['PA_ts'] ?? false) ? $body['PA_ts_stato'] ?? null : null;
        } else {
            $this->publicAdministration = null;
        }

        $this->splitPayment = $body['split_payment'] ?? false;
    }
}
