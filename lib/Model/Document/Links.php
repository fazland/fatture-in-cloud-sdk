<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class Links
{
    /**
     * Link to the document in PDF format.
     *
     * @var null|string
     */
    public $document;

    /**
     * Link to the transport document in PDF format.
     *
     * @var null|string
     */
    public $transportDocument;

    /**
     * Link to the accompanying invoice document in PDF format.
     *
     * @var null|string
     */
    public $accompanyingInvoice;

    /**
     * Link to the attachment if present.
     *
     * @var null|string
     */
    public $attachment;
}
