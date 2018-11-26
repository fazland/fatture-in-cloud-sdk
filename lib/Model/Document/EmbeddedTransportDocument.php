<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

class EmbeddedTransportDocument
{
    /**
     * The template identifier.
     *
     * @var string
     */
    public $templateId;

    /**
     * The transport document number.
     *
     * @var string
     */
    public $number;

    /**
     * Date of current transport document.
     *
     * @var \DateTimeInterface
     */
    public $date;

    /**
     * Number of packs.
     *
     * @var string
     */
    public $packs;

    /**
     * Weight of packs.
     *
     * @var string
     */
    public $weight;

    /**
     * Causal.
     *
     * @var string
     */
    public $causal;

    /**
     * The shipping place.
     *
     * @var string
     */
    public $place;

    /**
     * The transporter data.
     *
     * @var string
     */
    public $transporterData;

    /**
     * Other annotations.
     *
     * @var string
     */
    public $annotations;
}
