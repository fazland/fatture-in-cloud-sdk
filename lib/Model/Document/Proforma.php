<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class Proforma extends Document
{
    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'proforma';
    }
}
