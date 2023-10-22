<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Exceptions\AuthenticationFailedException;
use BrighteCapital\Api\Models\IdentityTokenResponse;
use BrighteCapital\Api\Models\User;
use Fig\Http\Message\StatusCodeInterface;

class IdentityApi extends \BrighteCapital\Api\AbstractApi
{

    public const PATH = '/identity';

    public function getUser(int $userId): ?User
    {
        $response = $this->brighteApi->get(sprintf('%s/users/%d', self::PATH, $userId), '', [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());

        $user = new User();
        $user->id = $result->id ?? null;
        $user->remoteId = $result->remoteId ?? null;
        $user->role = $result->role ?? null;
        $user->firstName = $result->firstName ?? null;
        $user->lastName = $result->lastName ?? null;
        $user->email = $result->email ?? null;
        $user->phone = $result->phone ?? null;
        $user->sfContactId = $result->sfContactId ?? null;
        $user->uid = $result->uid ?? null;

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

        $response = $this->brighteApi->post(sprintf('%s/users', self::PATH), $body, '', [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());

        $user->id = (int) $result->id ?? null;

        return $user;
    }

    public function getUserTokenByCode(string $code): IdentityTokenResponse
    {
        $body = \json_encode([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->brighteApi->legacyClientId
        ]);

        $response = $this->brighteApi->post(sprintf('%s/token', self::PATH), $body, '', [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            throw new AuthenticationFailedException(\json_decode((string) $response->getBody())->error);
        }

        $result = json_decode((string) $response->getBody());

        return new IdentityTokenResponse(
            (string) $result->access_token,
            (string) $result->refresh_token,
            (int) $result->expires_in,
            (string) $result->token_type
        );
    }

    public function refreshToken(string $refreshToken): IdentityTokenResponse
    {
        $body = \json_encode(['refreshToken' => $refreshToken]);

        $response = $this->brighteApi->post(sprintf('%s/refresh', self::PATH), $body, '', [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            throw new AuthenticationFailedException(\json_decode((string) $response->getBody())->code);
        }

        $result = json_decode((string) $response->getBody());

        return new IdentityTokenResponse(
            (string) $result->access_token,
            (string) $result->refresh_token,
            (int) $result->expires_in,
            (string) $result->token_type
        );
    }

    public function getUserByMobileAndOrEmail(string $mobile, string $email): ?User
    {
        $body = json_encode([
            'mobile' => $mobile,
            'email' => $email,
        ]);

        $response = $this->brighteApi->post(
            sprintf('%s/users/get-user-by-mobile-and-or-email', self::PATH),
            $body,
            '',
            [],
            self::PATH
        );

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $result = json_decode((string) $response->getBody());

        $user = new User();
        $user->id = $result->id ?? null;
        $user->remoteId = $result->remoteId ?? null;
        $user->uid = $result->uid ?? null;
        $user->firstName = $result->firstName ?? null;
        $user->lastName = $result->lastName ?? null;
        $user->email = $result->email ?? null;
        $user->phone = $result->mobile ?? null;
        $user->role = $result->role ?? null;

        return $user;
    }
}
