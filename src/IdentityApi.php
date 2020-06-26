<?php

declare(strict_types = 1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\User;
use Fig\Http\Message\StatusCodeInterface;

class IdentityApi extends \BrighteCapital\Api\AbstractApi
{

    public const PATH = '/identity';

    public function getUser(int $userId): ?User
    {
        $response = $this->brighteApi->get(sprintf('%s/users/%d', self::PATH, $userId));
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }
        
        $result = json_decode((string) $response->getBody());

        $user = new User;
        $user->id = $result->id ?? null;
        $user->role = $result->role ?? null;
        $user->firstName = $result->firstName ?? null;
        $user->lastName = $result->lastName ?? null;
        $user->email = $result->email ?? null;
        $user->phone = $result->phone ?? null;
        $user->sfContactId = $result->sfContactId ?? null;

        return $user;
    }

    public function createUser(User $user): ?User
    {
        $body = json_encode([
            'email' => $user->email,
            'mobile' => $user->phone,
            'role' => $user->role,
            'firstName' => $user->firstName ?? '',
            'lastName' => $user->lastName ?? '',
        ]);

        $response = $this->brighteApi->post(sprintf('%s/users', self::PATH), $body);
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }
        
        $result = json_decode((string) $response->getBody());

        $user->id = (int) $result->id ?? null;

        return $user;
    }

}
