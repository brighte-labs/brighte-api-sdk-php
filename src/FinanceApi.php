<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use Fig\Http\Message\StatusCodeInterface;

class FinanceApi extends \BrighteCapital\Api\AbstractApi
{
    public const PATH = '/finance';

    public function getApplicationId(string $remoteId): ?string
    {
        $response = $this->brighteApi->get(self::PATH . '/applications/account/' . $remoteId, '', [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());

        return $result->id ?? null;
    }

    public function addJointApplicant(array $applicant, string $applicationId): ?string
    {
        $body = json_encode([
            'firstName' => $applicant['first_name'],
            'email' => $applicant['email'],
            'mobile' => $applicant['mobile'],
            'present' => false,
        ]);

        $response = $this->brighteApi
            ->post(self::PATH . '/applications/' . $applicationId . '/applicants', $body, '', [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_CREATED) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());

        return $result->id ?? null;
    }
}
