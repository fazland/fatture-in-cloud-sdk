<?php

declare(strict_types=1);

namespace Fazland\FattureInCloud\Tests\API;

use Fazland\FattureInCloud\API\Purchase;
use Fazland\FattureInCloud\Client\ClientInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use function GuzzleHttp\Psr7\stream_for;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;

class PurchaseTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|ClientInterface
     */
    private $client;

    /**
     * @var Purchase
     */
    private $purchase;

    protected function setUp(): void
    {
        $this->client = $this->prophesize(ClientInterface::class);
        $this->purchase = new Purchase($this->client->reveal());
    }

    public function testItShouldCreateAPurchase(): void
    {
        $purchase = new \Fazland\FattureInCloud\Model\Purchase\Purchase();
        $this->client->request('POST', 'acquisti/nuovo', $purchase)
            ->willReturn($response = $this->prophesize(ResponseInterface::class))
        ;

        $response->getBody()->willReturn(stream_for(\json_encode([
            'new_id' => '11223344',
        ])));

        $this->purchase->create($purchase);

        self::assertEquals('11223344', $purchase->id);
    }

    public function testItShouldUpdateAPurchase(): void
    {
        $purchase = new \Fazland\FattureInCloud\Model\Purchase\Purchase();

        $update = \json_decode(\json_encode($purchase), true);

        $update['id'] = '11223344';

        $this->client->request('POST', 'acquisti/modifica', $update);

        $this->purchase->update('11223344', $purchase);

        self::assertEquals('11223344', $purchase->id);
    }

    public function testItShouldDeleteAPurchase(): void
    {
        $purchase = new \Fazland\FattureInCloud\Model\Purchase\Purchase();
        (\Closure::bind(function (): void {
            $this->id = '11223344';
        }, $purchase, \Fazland\FattureInCloud\Model\Purchase\Purchase::class))();

        $this->client->request('POST', 'acquisti/elimina', ['id' => '11223344']);

        $this->purchase->delete($purchase);

        self::assertNull($purchase->id);
    }
}
