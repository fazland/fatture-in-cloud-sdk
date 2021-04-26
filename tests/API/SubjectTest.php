<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Tests\API;

use Fazland\FattureInCloud\API\Subject;
use Fazland\FattureInCloud\Client\ClientInterface;
use function GuzzleHttp\Psr7\stream_for;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;

class SubjectTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|ClientInterface
     */
    private $client;

    /**
     * @var Subject
     */
    private $subject;

    protected function setUp(): void
    {
        $this->client = $this->prophesize(ClientInterface::class);
        $this->subject = new Subject($this->client->reveal(), 'foo');
    }

    public function testItShouldCreateASubject(): void
    {
        $subject = new ConcreteSubject();
        $this->client->request('POST', 'foo/nuovo', $subject)
            ->willReturn($response = $this->prophesize(ResponseInterface::class))
        ;

        $response->getBody()->willReturn(stream_for(\json_encode([
            'id' => 'foobar_id',
        ])));

        $this->subject->create($subject);

        self::assertEquals('foobar_id', $subject->id);
    }
}

class ConcreteSubject extends \Fazland\FattureInCloud\Model\Subject\Subject
{
}
