<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Tests;

use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PactTestingHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $client = new Client();
        return $client->send($request);
    }
}
