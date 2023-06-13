<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\PaymentMethod;
use Fig\Http\Message\StatusCodeInterface;

class PaymentApi extends \BrighteCapital\Api\AbstractApi
{

    public const PATH = '/payment';

    public function getMethod(string $methodId, int $userId): ?PaymentMethod
    {
        $params = http_build_query(compact('userId'));
        $response = $this->brighteApi->get(sprintf('%s-methods/%s', self::PATH, $methodId), $params, [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());

        $method = new PaymentMethod();
        $method->id = $result->id ?? null;
        $method->userId = $result->userId ?? null;
        $method->type = $result->type ?? null;
        $method->token = $result->token ?? null;
        $method->cardHolder = $result->cardHolder ?? null;
        $method->cardNumber = $result->cardNumber ?? null;
        $method->cardExpiry = $result->cardExpiry ?? null;
        $method->cardType = $result->cardType ?? null;
        $method->accountName = $result->accountName ?? null;
        $method->accountNumber = $result->accountNumber ?? null;
        $method->accountLast4 = $result->accountLast4 ?? null;
        $method->accountBsb = $result->accountBsb ?? null;
        $method->agreementText = $result->agreementText ?? null;
        $method->source = $result->source ?? null;

        return $method;
    }
}
