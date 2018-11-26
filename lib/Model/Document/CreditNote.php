<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class CreditNote extends Document
{
    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'ndc';
    }
}
