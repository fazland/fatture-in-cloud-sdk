<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\API;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Model;
use Fazland\FattureInCloud\Util\Json;

final class Subject extends Resource
{
    /**
     * @var string
     */
    private $type;

    public function __construct(ClientInterface $client, string $type)
    {
        parent::__construct($client);

        $this->type = $type;
    }

    /**
     * Gets a subject list.
     *
     * @param array $filter
     *
     * @return Model\Subject\AbstractList
     */
    public function list(array $filter = []): Model\Subject\AbstractList
    {
        if (Model\Subject\Subject::CUSTOMER === $this->type) {
            return new Model\Subject\CustomerList($this->client, $filter);
        }

        return new Model\Subject\SupplierList($this->client, $filter);
    }

    /**
     * Creates a new subject.
     *
     * @param Model\Subject\Subject $subject
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function create(Model\Subject\Subject $subject): void
    {
        $path = $this->type.'/nuovo';

        $response = $this->client->request('POST', $path, $subject);

        $result = Json::decode((string) $response->getBody(), true);
        (\Closure::bind(function ($id, $client): void {
            $this->id = $id;
            $this->client = $client;
        }, $subject, Subject::class))($result['id'], $this->client);
    }

    /**
     * Updates a subject.
     *
     * @param int|string $id
     * @param array|Model\Subject\Subject $update
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function update($id, $update): void
    {
        $path = $this->type.'/modifica';

        $update = \json_decode(\json_encode($update), true);
        $update['id'] = $id;

        $this->client->request('POST', $path, $update);

        if ($update instanceof Model\Subject\Subject) {
            (\Closure::bind(function ($id, $client): void {
                $this->id = $id;
                $this->client = $client;
            }, $update, Subject::class))($id, $this->client);
        }
    }

    /**
     * Creates a new subject.
     *
     * @param string|Model\Subject\Subject $idOrSubject
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function delete($idOrSubject): void
    {
        $path = $this->type.'/elimina';
        $this->client->request('POST', $path, [
            'id' => $idOrSubject instanceof Model\Subject\Subject ? $idOrSubject->id : $idOrSubject
        ]);

        if ($idOrSubject instanceof Model\Subject\Subject) {
            (\Closure::bind(function (): void {
                $this->id = null;
                $this->client = null;
            }, $idOrSubject, Subject::class))();
        }
    }
}
