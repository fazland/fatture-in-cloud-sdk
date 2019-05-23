<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\API;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Model;
use Fazland\FattureInCloud\Util\Json;

final class Good extends Resource
{
    /**
     * @var string
     */
    private $type = 'prodotti';

    public function __construct(ClientInterface $client)
    {
        parent::__construct($client);
    }

    /**
     * Creates a new good.
     *
     * @param Model\Document\Good $good
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function create(Model\Document\Good $good): void
    {
        $response = $this->client->request('POST', $path = $this->type.'/nuovo', $good);

        $result = Json::decode((string) $response->getBody(), true);
        (\Closure::bind(function ($id): void {
            $this->id = $id;
        }, $good, Model\Document\Good::class))($result['id']);
    }

    /**
     * Updates a good.
     *
     * @param int|string                $id
     * @param array|Model\Document\Good $update
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function update($id, $update): void
    {
        $path = $this->type.'/modifica';

        $update = \json_decode(\json_encode($update), true);
        $update['id'] = $id;

        $this->client->request('POST', $path, $update);

        if ($update instanceof Model\Document\Good) {
            (\Closure::bind(function ($id): void {
                $this->id = $id;
            }, $update, Model\Document\Good::class))($id, $this->client);
        }
    }

    /**
     * Deletes a good.
     *
     * @param string|Model\Document\Good $idOrGood
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function delete($idOrGood): void
    {
        $path = $this->type.'/elimina';
        $this->client->request('POST', $path, [
            'id' => $idOrGood instanceof Model\Document\Good ? $idOrGood->id : $idOrGood,
        ]);

        if ($idOrGood instanceof Model\Document\Good) {
            (\Closure::bind(function (): void {
                $this->id = null;
            }, $idOrGood, Model\Document\Good::class))();
        }
    }
}
