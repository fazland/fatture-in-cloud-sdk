<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\API;

use Fazland\FattureInCloud\Model;

final class API extends Resource
{
    /**
     * Gets customer API methods.
     */
    public function customer(): Subject
    {
        return new Subject($this->client, Model\Subject\Subject::CUSTOMER);
    }

    /**
     * Gets supplier API methods.
     */
    public function supplier(): Subject
    {
        return new Subject($this->client, Model\Subject\Subject::SUPPLIER);
    }

    /**
     * Gets document API methods.
     *
     * @param string $type The document type
     */
    public function document(string $type): Document
    {
        return new Document($this->client, $type);
    }

    /**
     * Gets good API methods.
     */
    public function good(): Good
    {
        return new Good($this->client);
    }
}
