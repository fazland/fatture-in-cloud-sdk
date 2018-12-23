<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Client;

use Fazland\FattureInCloud\Exception\Request\BadResponseException;
use Fazland\FattureInCloud\Exception\Request\RequestException;
use Fazland\FattureInCloud\Util\Json;
use Http\Client\Exception\HttpException;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\ResponseInterface;

class Client implements ClientInterface
{
    /**
     * @var MessageFactory
     */
    private static $messageFactory;

    /**
     * @var HttpClientInterface
     */
    private $http;

    /**
     * @var string
     */
    private $uid;

    /**
     * @var string
     */
    private $key;

    public function __construct(string $uid, string $key, HttpClientInterface $http)
    {
        $this->http = $http;
        $this->uid = $uid;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $method, string $path, $requestData, array $headers = []): ResponseInterface
    {
        if (! \is_string($requestData)) {
            $requestData = \json_encode($requestData);
        }

        $requestData = \json_decode($requestData, true);
        $requestData['api_uid'] = $this->uid;
        $requestData['api_key'] = $this->key;

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        $request = self::getMessageFactory()->createRequest($method, $path, $headers, \json_encode($requestData));
        try {
            $response = $this->http->sendRequest($request);

            if (
                200 !== $response->getStatusCode() ||
                empty($contentTypeHeader = $response->getHeader('Content-Type')) ||
                !\preg_match('#^application/json#', $contentTypeHeader[0])
            ) {
                throw new BadResponseException($request, $response);
            }
        } catch (HttpException $e) {
            throw new BadResponseException($e->getRequest(), $e->getResponse());
        }

        $body = Json::decode((string) $response->getBody());
        if (isset($body->error) || isset($body->error_code)) {
            throw RequestException::create($request, $response);
        }

        return $response;
    }

    /**
     * Gets a message factory.
     *
     * @return MessageFactory
     */
    private static function getMessageFactory(): MessageFactory
    {
        if (null === self::$messageFactory) {
            self::$messageFactory = MessageFactoryDiscovery::find();
        }

        return self::$messageFactory;
    }
}
