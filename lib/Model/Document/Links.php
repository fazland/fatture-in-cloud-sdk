<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class Links
{
    /**
     * Link to the document in PDF format.
     *
     * @var string|null
     */
    public $document;

    /**
     * Link to the transport document in PDF format.
     *
     * @var string|null
     */
    public $transportDocument;

    /**
     * Link to the accompanying invoice document in PDF format.
     *
     * @var string|null
     */
    public $accompanyingInvoice;

    /**
     * Link to the attachment if present.
     *
     * @var string|null
     */
    public $attachment;
}
