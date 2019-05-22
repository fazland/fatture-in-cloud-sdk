<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\API;

use Fazland\FattureInCloud\Client\ClientInterface;
use Fazland\FattureInCloud\Model;
use Fazland\FattureInCloud\Util\Json;

final class Document extends Resource
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
     * Creates a new document.
     *
     * @param Model\Document\Document $document
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function create(Model\Document\Document $document): void
    {
        $path = $this->type.'/nuovo';
        $response = $this->client->request('POST', $path, $document);

        $result = Json::decode((string) $response->getBody(), true);
        (\Closure::bind(function ($id, $token, $client): void {
            $this->id = $id;
            $this->token = $token;
            $this->client = $client;
        }, $document, Model\Document\Document::class))($result['new_id'], $result['token'], $this->client);
    }

    /**
     * Updates a subject.
     *
     * @param string                      $token
     * @param array|Model\Subject\Subject $update
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function update(string $token, $update): void
    {
        $path = $this->type.'/modifica';

        $update = \json_decode(\json_encode($update), true);
        $update['token'] = $token;

        $this->client->request('POST', $path, $update);

        if ($update instanceof Model\Document\Document) {
            (\Closure::bind(function ($token, $client): void {
                $this->token = $token;
                $this->client = $client;
            }, $update, Model\Subject\Subject::class))($token, $this->client);
        }
    }

    /**
     * Deletes a document.
     *
     * @param string|Model\Document\Document $tokenOrDocument
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function delete($tokenOrDocument): void
    {
        $path = $this->type.'/elimina';
        $this->client->request('POST', $path, [
            'token' => $tokenOrDocument instanceof Model\Document\Document ? $tokenOrDocument->token : $tokenOrDocument,
        ]);

        if ($tokenOrDocument instanceof Model\Document\Document) {
            (\Closure::bind(function (): void {
                $this->id = null;
                $this->client = null;
            }, $tokenOrDocument, Model\Subject\Subject::class))();
        }
    }
}
