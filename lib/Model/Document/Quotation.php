<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class Quotation extends Document
{
    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'preventivi';
    }
}
