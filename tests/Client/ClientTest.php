<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Tests\Client;

use Fazland\FattureInCloud\Client\Client;
use Fazland\FattureInCloud\Exception\Request\BadRequestException;
use Fazland\FattureInCloud\Exception\Request\BlockedException;
use Fazland\FattureInCloud\Exception\Request\ForbiddenException;
use Fazland\FattureInCloud\Exception\Request\IncorrectDataException;
use Fazland\FattureInCloud\Exception\Request\LicenseExpiredException;
use Fazland\FattureInCloud\Exception\Request\LicensePlanInsufficient;
use Fazland\FattureInCloud\Exception\Request\LimitExceededException;
use Fazland\FattureInCloud\Exception\Request\MandatoryParameterMissingException;
use Fazland\FattureInCloud\Exception\Request\NotFoundException;
use Fazland\FattureInCloud\Exception\Request\RateLimitExceededException;
use Fazland\FattureInCloud\Exception\Request\UnauthorizedException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientTest extends TestCase
{
    /**
     * @var ClientInterface|ObjectProphecy
     */
    private $http;

    /**
     * @var Client
     */
    private $client;

    protected function setUp(): void
    {
        $this->http = $this->prophesize(ClientInterface::class);
        $this->client = new Client('uid', 'key', $this->http->reveal());
    }

    public function testRequestShouldAppendUidKeyAndCorrectHeaders(): void
    {
        $this->http->sendRequest(Argument::any())
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], '{"ok":1}'));

        $this->client->request('POST', '/', '{}');

        $this->http->sendRequest(Argument::that(function (RequestInterface $arg): bool {
            self::assertJsonStringEqualsJsonString(\json_encode([
                'api_uid' => 'uid',
                'api_key' => 'key',
            ]), (string) $arg->getBody());
            self::assertEquals('application/json', $arg->getHeader('Content-Type')[0] ?? '');
            self::assertEquals('application/json', $arg->getHeader('Accept')[0] ?? '');

            return true;
        }))->shouldHaveBeenCalledOnce();
    }

    public function provideInvalidResponses(): iterable
    {
        yield [new Response(500, ['Content-Type' => 'application/json'], '{}')];
        yield [new Response(200, ['Content-Type' => 'text/html'], 'HTML here')];
        yield [new Response(200, [], '{}')];
    }

    /**
     * @dataProvider provideInvalidResponses
     * @expectedException \Fazland\FattureInCloud\Exception\Request\BadResponseException
     */
    public function testRequestShouldThrowIfResponseIsInvalid(ResponseInterface $response): void
    {
        $this->http->sendRequest(Argument::any())->willReturn($response);
        $this->client->request('POST', '/', '{}');
    }

    public function provideErrorCodes(): iterable
    {
        yield [1000, UnauthorizedException::class];
        yield [1001, MandatoryParameterMissingException::class];
        yield [1100, BadRequestException::class];
        yield [2000, LicenseExpiredException::class];
        yield [2002, RateLimitExceededException::class];
        yield [2004, BlockedException::class];
        yield [2005, LicensePlanInsufficient::class];
        yield [2006, ForbiddenException::class];
        yield [4000, NotFoundException::class];
        yield [4001, LimitExceededException::class];
        yield [5000, IncorrectDataException::class];
    }

    /**
     * @dataProvider provideErrorCodes
     */
    public function testRequestShouldThrowAppropriateExceptionOnError(int $errorCode, string $class): void
    {
        $this->expectException($class);

        $body = [
            'error' => 'Test Error',
            'error_code' => $errorCode,
        ];
        $this->http->sendRequest(Argument::any())
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], \json_encode($body)));

        $this->client->request('POST', '/', '{}');
    }
}
