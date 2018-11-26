<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

final class Receipt extends Document
{
    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'ricevute';
    }
}
