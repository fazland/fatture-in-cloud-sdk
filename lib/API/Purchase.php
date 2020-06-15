<?php

declare(strict_types=1);

namespace Fazland\FattureInCloud\API;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Model;
use Fazland\FattureInCloud\Util\Json;

final class Purchase extends Resource
{
    /**
     * @var string
     */
    private $type = 'acquisti';

    public function __construct(ClientInterface $client)
    {
        parent::__construct($client);
    }

    /**
     * Gets a subject list.
     *
     * @return Model\Subject\AbstractList
     */
    public function list(array $filter = []): Model\Purchase\PurchaseList
    {
        return new Model\Purchase\PurchaseList($this->client, $filter);
    }

    public function get($idOrPurchase)
    {
        return Model\Purchase\Purchase::get(
            $idOrPurchase instanceof Model\Purchase\Purchase ? $idOrPurchase->id : $idOrPurchase,
            $this->client
        );
    }

    public function create(Model\Purchase\Purchase $purchase): void
    {
        $path = $this->type.'/nuovo';

        $response = $this->client->request('POST', $path, $purchase);

        $result = Json::decode((string) $response->getBody(), true);
        (\Closure::bind(
            function ($id, $client): void {
                $this->id = $id;
                $this->client = $client;
            },
            $purchase,
            Model\Purchase\Purchase::class
        ))(
            $result['new_id'],
            $this->client
        );
    }

    public function update($id, $update): void
    {
        $path = $this->type.'/modifica';

        $data = \json_decode(\json_encode($update), true);

        $data['id'] = $id;

        $this->client->request('POST', $path, $data);

        if ($update instanceof Model\Purchase\Purchase) {
            (\Closure::bind(
                function ($id): void {
                    $this->id = $id;
                },
                $update,
                Model\Purchase\Purchase::class
            ))(
                $id
            );
        }
    }

    public function delete($idOrPurchase): void
    {
        $path = $this->type.'/elimina';
        $this->client->request(
            'POST',
            $path,
            [
                'id' => $idOrPurchase instanceof Model\Purchase\Purchase ? $idOrPurchase->id : $idOrPurchase,
            ]
        );

        if ($idOrPurchase instanceof Model\Purchase\Purchase) {
            (\Closure::bind(
                function (): void {
                    $this->id = null;
                },
                $idOrPurchase,
                Model\Purchase\Purchase::class
            ))();
        }
    }
}
