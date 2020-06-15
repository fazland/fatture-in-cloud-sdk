<?php

declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Purchase;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Exception\NotFoundException;
use Fazland\FattureInCloud\Util\Json;

/**
 * @property string    $id
 * @property \DateTime $date
 * @property \DateTime $expireNext
 */
class Purchase implements \JsonSerializable
{
    const PURCHASE = 'acquisti';

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    public $idSupplier;

    /**
     * @var string
     */
    public $invoiceNumber;

    /**
     * @var string
     */
    public $accrualDate;

    /**
     * @var string
     */
    public $name;

    /**
     * @var \DateTimeInterface
     */
    private $date;

    /**
     * @var string
     */
    public $category;

    /**
     * @var string
     */
    public $netAmount;

    /**
     * @var string
     */
    public $vatAmount;

    /**
     * @var string
     */
    public $withholdingTax;

    /**
     * @var string
     */
    public $retirementTax;

    /**
     * @var int
     */
    public $deductibleTax;

    /**
     * @var int
     */
    public $deductibleVat;

    /**
     * @var string
     */
    public $depreciation;

    /**
     * @var string
     */
    public $costCentre;

    /**
     * @var string
     */
    public $totalAmount;

    /**
     * @var string
     */
    public $currency;

    /**
     * @var string
     */
    public $currencyChange;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $fileAttached;

    /**
     * @var string
     */
    public $linkAttached;

    /**
     * @var \DateTimeInterface
     */
    private $expireNext;

    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $payed;

    /**
     * @var array|Payment[]
     */
    public $listPayments = [];

    /**
     * The original data, as fetched from the APIs.
     *
     * @var array
     */
    private $originalData;
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        switch ($name) {
            case 'id':
                return $this->id;
                break;
            case 'date':
                return $this->date;
                break;
            case 'expireNext':
                return $this->expireNext;
                break;
            default:
                throw new \Error('Undefined property "'.$name.'"');
        }
    }

    public function __set($name, $value)
    {
        if (null !== $value) {
            switch ($name) {
                case 'date':
                case 'expireNext':
                    $value = \str_replace('/', '-', $value);
                    $value = new \DateTimeImmutable($value);
                    break;
                default:
                    throw new \Error('Undefined property "'.$name.'"');
            }
        }

        $accessor = function &() use ($name, $value) {
            $this->$name = $value;

            return $this->$name;
        };
        $return = &$accessor();

        return $return;
    }

    /**
     * Fetches a subject from the API.
     *
     * @return Subject
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public static function get(string $id, ClientInterface $client): self
    {
        $obj = new static();
        $obj->client = $client;

        $path = self::PURCHASE.'/dettagli';

        $response = $client->request(
            'POST',
            $path,
            [
                'id' => $id,
            ]
        );

        $result = Json::decode((string) $response->getBody(), true);
        $purchase = $result['dettagli_documento'];

        if (empty($purchase)) {
            throw new NotFoundException('Resource id #'.$id.' has not been found');
        }

        return $obj->fromArray($purchase);
    }

    public function jsonSerialize()
    {
        return \array_filter(
            [
                'id' => $this->id,
                'tipo' => $this->type,
                'saldato' => $this->payed,
                'anno_competenza' => $this->accrualDate,
                'id_fornitore' => $this->idSupplier,
                'nome' => $this->name,
                'data' => $this->date ? $this->date->format('d/m/Y') : null,
                'descrizione' => $this->description,
                'categoria' => $this->category,
                'prossima_scadenza' => $this->expireNext ? $this->expireNext->format('d/m/Y') : null,
                'file_allegato' => $this->fileAttached,
                'link_allegato' => $this->linkAttached,
                'importo_netto' => $this->netAmount,
                'importo_iva' => $this->vatAmount,
                'importo_totale' => $this->totalAmount,
                'ritenuta_acconto' => $this->withholdingTax,
                'ritenuta_previdenziale' => $this->retirementTax,
                'deducibilita_tasse' => $this->deductibleTax,
                'deducibilita_iva' => $this->deductibleVat,
                'ammortamento' => $this->depreciation,
                'centro_costo' => $this->costCentre,
                'numero_fattura' => $this->invoiceNumber,
                'valuta' => $this->currency,
                'valuta_cambio' => $this->currencyChange,
                'lista_pagament' => \array_filter(
                    $this->listPayments,
                    function (Payment $payment) {
                        return $payment->jsonSerialize();
                    }
                ),
            ],
            static function ($value): bool {
                return null !== $value && '' !== $value;
            }
        );
    }

    /**
     * Creates a Purchase from a response array.
     *
     * @return Purchase
     */
    public function fromArray(array $data): self
    {
        $this->originalData = $data;
        \ksort($this->originalData);

        $this->id = $data['id'] ?? null;
        $this->type = $data['tipo'] ?? null;
        $this->payed = $data['saldato'] ?? null;
        $this->accrualDate = $data['anno_competenza'] ?? null;
        $this->idSupplier = $data['id_fornitore'] ?? null;
        $this->name = $data['nome'] ?? null;
        $this->__set('date', $data['data'] ?? null);
        $this->description = $data['descrizione'] ?? null;
        $this->category = $data['categoria'] ?? null;
        $this->__set('expireNext', $data['prossima_scadenza'] ?? null);
        $this->fileAttached = $data['file_allegato'] ?? null;
        $this->linkAttached = $data['link_allegato'] ?? null;
        $this->netAmount = $data['importo_netto'] ?? null;
        $this->vatAmount = $data['importo_iva'] ?? null;
        $this->totalAmount = $data['importo_totale'] ?? null;
        $this->withholdingTax = $data['ritenuta_acconto'] ?? null;
        $this->retirementTax = $data['ritenuta_previdenziale'] ?? null;
        $this->deductibleTax = $data['deducibilita_tasse'] ?? null;
        $this->deductibleVat = $data['detraibilita_iva'] ?? null;
        $this->depreciation = $data['ammortamento'] ?? null;
        $this->costCentre = $data['centro_costo'] ?? null;
        $this->invoiceNumber = $data['numero_fattura'] ?? null;
        $this->currency = $data['valuta'] ?? null;
        $this->currencyChange = $data['cambio_valuta'] ?? null;

        if (isset($data['lista_pagamenti'])) {
            foreach ($data['lista_pagamenti'] as $dataPayment) {
                $payment = new Payment();
                $payment->fromArray($dataPayment);
                $this->listPayments[] = $payment;
            }
        }

        return $this;
    }
}
