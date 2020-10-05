<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

use Fazland\FattureInCloud\Util\Money\PreciseMoney;

final class Good implements \JsonSerializable
{
    /**
     * Product identifier.
     *
     * @var string
     */
    public $id;

    /**
     * Product code.
     *
     * @var string
     */
    public $code;

    /**
     * Product name.
     *
     * @var string
     */
    public $name;

    /**
     * Measurement unit.
     *
     * @var string
     */
    public $mu;

    /**
     * Quantity.
     *
     * @var float
     */
    public $qty;

    /**
     * Product description.
     *
     * @var string
     */
    public $description;

    /**
     * Product category.
     *
     * @var string
     */
    public $category;

    /**
     * Net price (mandatory if document vatIncluded is false).
     *
     * @var PreciseMoney
     */
    public $netPrice;

    /**
     * Gross price (mandatory if document vatIncluded is true).
     *
     * @var PreciseMoney
     */
    public $grossPrice;

    /**
     * Vat rate code.
     *
     * @var int
     */
    public $vatCode;

    /**
     * Vat percentile (read-only).
     *
     * @var float
     */
    public $vatAmount;

    /**
     * Whether this product is taxable or not.
     *
     * @var bool
     */
    public $taxable;

    /**
     * Discount (percentage).
     *
     * @var float
     */
    public $discount;

    /**
     * @var bool
     */
    public $applyWithholdingAndContributions;

    /**
     * Document ordering. ASC from 0.
     *
     * @var int
     */
    public $order;

    /**
     * If a discount is present, highlight it in red.
     *
     * @var bool
     */
    public $highlightDiscount;

    /**
     * Whether this product is in the transport document.
     *
     * @var bool
     */
    public $inTransportDocument;

    /**
     * Whether to update the warehouse or not.
     * Ignored if not linked to product list or warehouse is disabled.
     *
     * @var bool
     */
    public $fromWarehouse;

    /**
     * Initial amount of goods in stock.
     *
     * @var int
     */
    public $initialStock;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return \array_filter([
            'id' => $this->id,
            'codice' => $this->code,
            'cod' => $this->code,
            'nome' => $this->name,
            'um' => $this->mu,
            'quantita' => $this->qty,
            'descrizione' => $this->description,
            'desc' => $this->description,
            'categoria' => $this->category,
            'prezzo_netto' => null !== $this->netPrice ? \sprintf('%.5f', $this->netPrice->getAmount() / 100.0) : null,
            'prezzo_lordo' => null !== $this->grossPrice ? \sprintf('%.5f', $this->grossPrice->getAmount() / 100.0) : null,
            'cod_iva' => $this->vatCode,
            'tassabile' => $this->taxable,
            'sconto' => $this->discount,
            'applica_ra_contributi' => $this->applyWithholdingAndContributions,
            'ordine' => $this->order,
            'sconto_rosso' => $this->highlightDiscount,
            'in_ddt' => $this->inTransportDocument,
            'magazzino' => $this->fromWarehouse,
            'giacenza_iniziale' => $this->initialStock,
        ], static function ($value): bool {
            return null !== $value && '' !== $value;
        });
    }
}
