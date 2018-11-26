<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class Invoice extends Document
{
    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'fatture';
    }
}
