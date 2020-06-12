<?php

declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Purchase;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Util\Json;

class PurchaseList implements \IteratorAggregate
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var array
     */
    private $filters;

    /**
     * @var array
     */
    private $collection;

    public function __construct(ClientInterface $client, array $filters = [])
    {
        $this->client = $client;
        $this->filters = $filters;
        $this->collection = [];
    }

    protected function createPurchase(ClientInterface $client, array $data): Purchase
    {
        $obj = new Purchase();
        $obj->fromArray($data);

        (\Closure::bind(
            function () use ($client): void {
                $this->client = $client;
            },
            $obj,
            Purchase::class
        ))();

        return $obj;
    }
    /**
     * Yields subjects from response array.
     *
     * @param array $data
     *
     * @return \Generator
     */
    private function fromResponse(array $data): \Generator
    {
        foreach ($data as $datum) {
            $subject = $this->createPurchase($this->client, $datum);
            if (isset($this->collection[$subject->id])) {
                continue;
            }

            yield $this->collection[$subject->id] = $subject;
        }
    }

    protected function getType(): string
    {
        return Purchase::PURCHASE;
    }

    public function getIterator()
    {
        $response = $this->client->request('POST', $this->getType().'/lista', $this->filters);
        $data = Json::decode((string)$response->getBody(), true);

        yield from $this->fromResponse($data['lista_documenti']);
    }
}