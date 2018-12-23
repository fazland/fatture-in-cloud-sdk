<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Exception;

/**
 * Exception thrown when a document searched by id was not found.
 */
final class NotFoundException extends \RuntimeException implements ExceptionInterface
{
}
