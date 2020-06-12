<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Util;

use Fazland\FattureInCloud\Exception\InvalidJSONException;

final class Json
{
    /**
     * Decodes a JSON string into an object/array.
     *
     * @return mixed
     *
     * @throws InvalidJSONException
     */
    public static function decode(string $json, bool $assoc = false)
    {
        $returnValue = @\json_decode($json, $assoc);

        if (null === $returnValue && JSON_ERROR_NONE !== \json_last_error()) {
            throw new InvalidJSONException('Cannot decode JSON: '.\json_last_error_msg());
        }

        return $returnValue;
    }
}
