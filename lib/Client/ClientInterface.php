<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Client;

use Fazland\FattureInCloud\API\API;
use Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    /**
     * Sends a request to the API.
     *
     * @param string $method
     * @param string $path
     * @param $requestData
     * @param array $headers
     *
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function request(string $method, string $path, $requestData, array $headers = []): ResponseInterface;

    /**
     * Gets API accessors.
     *
     * @return API
     */
    public function api(): API;
}
