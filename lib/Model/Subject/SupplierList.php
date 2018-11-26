<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Subject;

use Fazland\FattureInCloud\Client\ClientInterface;

final class SupplierList extends AbstractList
{
    /**
     * {@inheritdoc}
     */
    protected function createSubject(ClientInterface $client, array $data): Subject
    {
        $obj = new Supplier();

        $obj->fromArray($data);
        \Closure::bind(function () use ($client): void {
            $this->client = $client;
        }, $obj, Subject::class);

        return $obj;
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(): string
    {
        return 'fornitori';
    }
}
