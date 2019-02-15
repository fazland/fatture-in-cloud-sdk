<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Tests\Util;

use Fazland\FattureInCloud\Util\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testDecodeShouldWorkCorrectly(): void
    {
        self::assertEquals((object) [
            'test' => 'foo',
            'bar' => 12.0,
        ], Json::decode('{"test":"foo","bar":12.0}'));

        self::assertEquals([
            'test' => 'foo',
            'bar' => 12.0,
        ], Json::decode('{"test":"foo","bar":12.0}', true));
    }

    /**
     * @expectedException \Fazland\FattureInCloud\Exception\InvalidJSONException
     */
    public function testDecodeShouldThrowIfJsonIsInvalid(): void
    {
        Json::decode('test_x{', false);
    }
}
