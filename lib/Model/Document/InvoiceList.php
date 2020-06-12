<?php

declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Document;

use Fazland\FattureInCloud\Client\ClientInterface;

class InvoiceList extends AbstractList
{
    protected function createDocument(ClientInterface $client, array $data): Document
    {
        $obj = new Invoice();
        $obj->fromArray($data);

        (\Closure::bind(function () use ($client): void {
            $this->client = $client;
        }, $obj, Document::class))();

        return $obj;
    }

    protected function getType(): string
    {
        return Document::INVOICE;
    }
}
