<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Model\Subject;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Util\Json;

abstract class AbstractList implements \IteratorAggregate
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
     * @var Subject[]
     */
    private $collection;

    public function __construct(ClientInterface $client, array $filters = [])
    {
        $this->client = $client;
        $this->filters = $filters;
        $this->collection = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        yield from array_values($this->collection);

        $filters = $this->filters;
        $currentPage = $filters['pagina'] ?? 1;
        $pages = $currentPage + 1;

        for ($i = $currentPage; $i < $pages; ++$i) {
            $filters['pagina'] = $i;

            $response = $this->client->request('POST', $this->getType().'/lista', $filters);
            $data = Json::decode((string) $response->getBody(), true);

            $pages = $data['numero_pagine'];
            yield from $this->fromResponse($data['lista_'.$this->getType()]);
        }
    }

    /**
     * Bulk creates some subjects.
     *
     * @param Subject ...$subjects
     *
     * @return $this
     */
    public function bulkCreate(Subject ...$subjects): self
    {
        $this->client->request('POST', $this->getType().'/importa', [
            'lista_soggetti' => $subjects,
        ]);

        return $this;
    }

    /**
     * Creates a subject object.
     *
     * @param ClientInterface $client
     * @param array           $data
     *
     * @return Subject
     */
    abstract protected function createSubject(ClientInterface $client, array $data): Subject;

    /**
     * Gets the subject type name ("clienti" or "fornitori").
     *
     * @return string
     */
    abstract protected function getType(): string;

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
            $subject = $this->createSubject($this->client, $datum);
            if (isset($this->collection[$subject->id])) {
                continue;
            }

            yield $this->collection[$subject->id] = $subject;
        }
    }
}
