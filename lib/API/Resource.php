<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\API;

use Fazland\FattureInCloud\Client\ClientInterface;

abstract class Resource
{
    /**
     * @var ClientInterface
     */
    protected $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }
}
