<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Model\Subject\Address;
use Fazland\FattureInCloud\Model\Subject\Customer;
use Fazland\FattureInCloud\Model\Subject\Subject;
use Fazland\FattureInCloud\Model\Subject\Supplier;
use Fazland\FattureInCloud\Util\Json;
use Fazland\FattureInCloud\Util\Money\MoneyUtil;
use Fazland\FattureInCloud\Util\Money\PreciseMoney;
use Money\Currency;
use Money\CurrencyPair;

/**
 * @property EmbeddedTransportDocument|null $transportDocument
 * @property PreciseMoney|null              $netAmount
 * @property PreciseMoney|null              $vatAmount
 * @property PreciseMoney|null              $grossAmount
 * @property PreciseMoney|null              $withholdingAmount
 * @property PreciseMoney|null              $withholdingOtherAmount
 * @property Links                          $links
 */
abstract class Document implements \JsonSerializable
{
    public const TOTALS_SHOW_ALL = 'tutti';
    public const TOTALS_SHOW_NETS = 'netti';
    public const TOTALS_HIDE_ALL = 'nessuno';

    public const INVOICE = 'fatture';
    public const PROFORMA = 'proforma';
    public const ORDER = 'ordini';
    public const QUOTATION = 'preventivi';
    public const RECEIPT = 'ricevute';
    public const REPORT = 'rapporti';
    public const CREDITNOTE = 'ndc';
    public const SUPPLIERORDER = 'ordforn';
    public const TRANSPORTDOCUMENT = 'ddt';

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
     * @var string
     */
    public $name;

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
     * INPS compensation (not available in TransportDocument and SupplierOrder)
     *
     * @var float
     */
    public $compensation;

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
     * @var PreciseMoney
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
     * Name of the revenue center.
     *
     * @var string
     */
    public $revenueCenter;

    /**
     * Name of the cost center.
     *
     * @var string
     */
    public $costCenter;

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
     * @var EmbeddedTransportDocument|null
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
     * @var PreciseMoney
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
     * @var PreciseMoney
     */
    private $grossAmount;

    /**
     * The withholding tax amount of this document.
     *
     * @var PreciseMoney
     */
    private $withholdingAmount;

    /**
     * The withholding other amount of this document.
     *
     * @var PreciseMoney
     */
    private $withholdingOtherAmount;

    /**
     * @var Links
     */
    private $links;

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
        $this->links = new Links();
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
            case 'netAmount':
            case 'vatAmount':
            case 'grossAmount':
            case 'withholdingAmount':
            case 'withholdingOtherAmount':
            case 'links':
                return $this->$name;

            default:
                throw new \Error('Undefined property "'.$name.'"');
        }
    }

    public function &__set($name, $value)
    {
        switch ($name) {
            case 'transportDocument':
                if (null !== $value && ! $value instanceof EmbeddedTransportDocument) {
                    throw new \TypeError(\sprintf('transportDocument must be of type %s or null. %s passed.', EmbeddedTransportDocument::class, \is_object($value) ? \get_class($value) : \gettype($value)));
                }

                break;

            default:
                throw new \Error('Undefined property "'.$name.'"');
        }

        $accessor = function &() use ($name, $value) {
            $this->$name = $value;

            return $this->$name;
        };
        $return = &$accessor();

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name): bool
    {
        return isset($this->$name);
    }

    /**
     * Gets the document details.
     *
     * @return Document
     */
    public static function get(string $token, ClientInterface $client): self
    {
        $path = static::getType().'/dettagli';

        $response = $client->request('POST', $path, [
            'token' => $token,
        ]);

        $result = Json::decode((string) $response->getBody(), true);

        $obj = new static();
        $obj->client = $client;
        $obj->fromArray($result['dettagli_documento']);

        return $obj;
    }

    /**
     * Creates a new object on the API server.
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function create(ClientInterface $client): self
    {
        $client->api()
            ->document(static::getType())
            ->create($this)
        ;

        $path = static::getType().'/dettagli';
        $response = $client->request('POST', $path, [
            'token' => $this->token,
        ]);

        $this->fromArray(Json::decode((string) $response->getBody(), true)['dettagli_documento']);

        return $this;
    }

    /**
     * Flushes the modifications to the APIs.
     *
     * @return $this
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function save(): self
    {
        $fields = \json_decode(\json_encode($this), true);
        \ksort($fields);

        foreach (['lista_articoli', 'lista_pagamenti'] as $field) {
            if (empty($fields[$field])) {
                continue;
            }

            \array_walk($fields[$field], 'ksort');
        }

        $update = \array_map('unserialize', \array_diff_assoc(\array_map('serialize', $fields), \array_map('serialize', $this->originalData)));
        if (0 === \count($update)) {
            return $this;
        }

        $this->client->api()
            ->document(static::getType())
            ->update($this->token, $update)
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        if (null === $this->subject) {
            throw new \RuntimeException('Subject is not defined');
        }

        $address = $this->subject->address->jsonSerialize();
        $address = \array_combine(
            \array_map(static function (string $key): string {
                return 'indirizzo_'.$key;
            }, \array_keys($address)),
            \array_values($address)
        );

        $payment = $this->paymentMethod->jsonSerialize();

        if (0 === \count($this->goods)) {
            throw new \RuntimeException('No products added');
        }

        return \array_filter([
            'id_cliente' => $this instanceof SupplierOrder ? null : $this->subject->id,
            'id_fornitore' => $this instanceof SupplierOrder ? $this->subject->id : null,
            'nome' => $this->subject->name,
            'paese' => $this->subject->country,
            'paese_iso' => $this->subject->countryIso,
            'lingua' => $this->language,
            'piva' => $this->subject->vatNumber,
            'cf' => $this->subject->fiscalCode,
            'autocompila_anagrafica' => $this->autocompleteSubject,
            'salva_anagrafica' => $this->autosaveSubject,
            'numero' => $this->number,
            'data' => null !== $this->date ? $this->date->format('d/m/Y') : null,
            'valuta' => $this->currency->getCode(),
            'valuta_cambio' => null !== $this->exchangeRatio ? \sprintf('%.5f', $this->exchangeRatio->getConversionRatio()) : null,
            'prezzi_ivati' => $this->vatIncluded ?? null,
            'rivalsa' => $this->compensation ?? null,
            'rit_acconto' => $this->withholdingTaxRatio,
            'imponibile_ritenuta' => $this->withholdingTaxIncome,
            'rit_altra' => $this->withholdingOtherRatio,
            'marca_bollo' => null !== $this->stamp ? (float) $this->stamp->getAmount() : null,
            'oggetto_visibile' => $this->documentSubject,
            'oggetto_interno' => $this->documentInternalSubject,
            'centro_ricavo' => $this->revenueCenter,
            'centro_costo' => $this->costCenter,
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
            'extra_anagrafica' => \array_filter([
                'mail' => $this->subject->mail,
                'tel' => $this->subject->phone,
                'fax' => $this->subject->fax,
            ]),
            'split_payment' => $this->splitPayment,
        ] + $address + $payment);
    }

    /**
     * Gets the document type.
     */
    abstract public static function getType(): string;

    /**
     * Initializes the object from a response array.
     */
    public function fromArray(array $body): void
    {
        $this->autocompleteSubject = false;
        $this->autosaveSubject = false;

        $this->originalData = $body;
        unset($this->originalData['token']);

        foreach (['lista_articoli', 'lista_pagamenti'] as $field) {
            if (empty($this->originalData[$field])) {
                continue;
            }

            \array_walk($this->originalData[$field], 'ksort');
            $this->originalData[$field] = \array_map(static function (array $element): array {
                return \array_filter($element, static function ($value): bool {
                    return null !== $value && '' !== $value;
                });
            }, $this->originalData[$field]);
        }

        if (isset($this->originalData['lista_articoli'])) {
            foreach ($this->originalData['lista_articoli'] as &$good) {
                unset($good['valore_iva']);
            }
            unset($good);
        }

        $this->id = $body['id'];
        $this->token = $body['token'];
        $this->name = $body['nome'] ?? null;

        $subject = isset($body['id_cliente']) ? new Customer() : new Supplier();
        (\Closure::bind(function ($id) {
            $this->id = $id;
        }, $subject, Subject::class))($body['id_cliente'] ?? $body['id_fornitore'] ?? null);

        $subject->name = $body['nome'];
        $subject->address = new Address();
        $subject->address->street = $body['indirizzo_via'] ?? null;
        $subject->address->zip = $body['indirizzo_cap'] ?? null;
        $subject->address->city = $body['indirizzo_citta'] ?? null;
        $subject->address->province = $body['indirizzo_provincia'] ?? null;
        $subject->address->extra = $body['indirizzo_extra'] ?? null;
        $subject->country = $body['paese'] ?? null;
        $subject->vatNumber = $body['piva'] ?? null;
        $subject->fiscalCode = $body['cf'] ?? null;

        $this->subject = $subject;

        $this->language = $body['lingua'] ?? null;
        $this->number = $body['numero'];
        $this->date = \DateTimeImmutable::createFromFormat('d/m/Y', $body['data']);
        $this->currency = new Currency($body['valuta']);

        if (isset($body['valuta_cambio'])) {
            $this->exchangeRatio = new CurrencyPair(new Currency('EUR'), new Currency($body['valuta']), $body['valuta_cambio']);
        }

        $this->vatIncluded = $body['prezzi_ivati'] ?? null;
        $this->netAmount = isset($body['importo_netto']) ? MoneyUtil::toMoney($body['importo_netto'], $this->currency) : null;
        $this->vatAmount = isset($body['importo_iva']) ? MoneyUtil::toMoney($body['importo_iva'], $this->currency) : null;
        $this->grossAmount = MoneyUtil::toMoney($body['importo_totale'], $this->currency);

        $this->compensation = $body['rivalsa'] ?? null;

        $this->withholdingTaxRatio = $body['rit_acconto'] ?? null;
        $this->withholdingTaxIncome = $body['imponibile_ritenuta'] ?? null;
        $this->withholdingOtherRatio = $body['rit_altra'] ?? null;

        $this->withholdingAmount = isset($body['importo_rit_acconto']) ? MoneyUtil::toMoney($body['importo_rit_acconto'], $this->currency) : null;
        $this->withholdingOtherAmount = isset($body['importo_rit_altra']) ? MoneyUtil::toMoney($body['importo_rit_altra'], $this->currency) : null;

        $this->stamp = isset($body['marca_bollo']) ? MoneyUtil::toMoney($body['marca_bollo'], $this->currency) : null;
        $this->documentSubject = $body['oggetto_visibile'] ?? null;
        $this->documentInternalSubject = $body['oggetto_interno'] ?? null;
        $this->revenueCenter = $body['centro_ricavo'] ?? null;
        $this->costCenter = $body['centro_costo'] ?? null;
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
                $body['metodo_titolo1'] ?? null,
                $body['metodo_titolo2'] ?? null,
                $body['metodo_titolo3'] ?? null,
                $body['metodo_titolo4'] ?? null,
                $body['metodo_titolo5'] ?? null,
            ])) ?: null;
            $this->paymentMethod->description = \implode("\n", \array_filter([
                $body['metodo_desc1'] ?? null,
                $body['metodo_desc2'] ?? null,
                $body['metodo_desc3'] ?? null,
                $body['metodo_desc4'] ?? null,
                $body['metodo_desc5'] ?? null,
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
            $good->netPrice = isset($item['prezzo_netto']) ? MoneyUtil::toMoney($item['prezzo_netto'], $this->currency) : null;
            $good->grossPrice = isset($item['prezzo_lordo']) ? MoneyUtil::toMoney($item['prezzo_lordo'], $this->currency) : null;
            $good->vatAmount = $item['valore_iva'] ?? null;
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
