<?php declare(strict_types=1);

namespace Fazland\FattureInCloud\Exception\Request;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RequestException extends \RuntimeException
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $body = json_decode((string) $response->getBody());
        parent::__construct('Error while executing request: '.($body->error ?? $response->getReasonPhrase() ?: 'Unknown error'));

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Creates the correct exception for the given response.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return RequestException
     */
    public static function create(RequestInterface $request, ResponseInterface $response): self
    {
        $body = json_decode((string) $response->getBody());
        switch ($body->error_code) {
            case 1000:
                $class = UnauthorizedException::class;
                break;

            case 1001:
                $class = MandatoryParameterMissingException::class;
                break;

            case 1100:
                $class = BadRequestException::class;
                break;

            case 2000:
                $class = LicenseExpiredException::class;
                break;

            case 2002:
                $class = RateLimitExceededException::class;
                break;

            case 2004:
                $class = BlockedException::class;
                break;

            case 2005:
                $class = LicensePlanInsufficient::class;
                break;

            case 2006:
                $class = ForbiddenException::class;
                break;

            case 4000:
                $class = NotFoundException::class;
                break;

            case 4001:
                $class = LimitExceededException::class;
                break;

            case 5000:
                $class = IncorrectDataException::class;
                break;
        }

        if (! isset($class)) {
            $class = self::class;
        }

        return new $class($request, $response);
    }

    /**
     * Gets the request originating the exception.
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Gets the failing response.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
