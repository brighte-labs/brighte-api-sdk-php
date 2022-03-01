<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use JsonMapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use stdClass;

use function json_decode;

abstract class AbstractApi
{

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $brighteApi;
    /**
     * @var JsonMapper
     */
    protected $jsonMapper;


    public function __construct(LoggerInterface $logger, BrighteApi $brighteApi, JsonMapper $mapper = null)
    {
        $this->logger = $logger;
        $this->brighteApi = $brighteApi;
        $this->jsonMapper = $mapper ?: new JsonMapper();
    }

    protected function logResponse(string $function, ResponseInterface $response): void
    {
        $body = json_decode((string) $response->getBody()) ?? new stdClass();
        $message = sprintf(
            '%s->%s: %d: %s',
            self::class,
            $function,
            $response->getStatusCode(),
            $body->message ?? $response->getReasonPhrase()
        );
        $this->logger->warning($message);
    }

    protected function logGraphqlResponse(string $function, ResponseInterface $response): void
    {
        $body = json_decode((string) $response->getBody()) ?? new stdClass();
        $message = sprintf(
            '%s->%s: %d: %s',
            self::class,
            $function,
            $response->getStatusCode(),
            $body->errors[0]->message ?? $response->getReasonPhrase()
        );
        $this->logger->warning($message);
    }
}
