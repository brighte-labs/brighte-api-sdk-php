<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\Exceptions\AuthenticationFailedException;
use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\IdentityApi;
use BrighteCapital\Api\Models\IdentityTokenResponse;
use BrighteCapital\Api\Models\User;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\IdentityApi
 */
class IdentityApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi|MockObject */
    protected $brighteApi;

    /** @var \BrighteCapital\Api\IdentityApi */
    protected $identityApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brighteApi = $this->createMock(BrighteApi::class);
        $this->identityApi = new IdentityApi($this->logger, $this->brighteApi);
    }

    /**
     * @covers ::__construct
     * @covers ::getUser
     */
    public function testgetUser(): void
    {
        $providedUser = [
            'id' => 1,
            'remoteId' => '11',
            'firstName' => 'Joe',
            'middleName' => 'Rogan',
            'lastName' => 'Customer',
            'email' => 'joe@test.com',
            'phone' => '0412312412',
            'role' => 'CONSUMER',
            'sfContactId' => 'salesforce-contact-id',
            'uid' => 'universal-id',
        ];
        $response = new Response(200, [], json_encode($providedUser));
        $this->brighteApi->expects(self::once())->method('get')
            ->with('/identity/users/1')->willReturn($response);
        $user = $this->identityApi->getUser(1);
        self::assertInstanceOf(User::class, $user);
        self::assertEquals(1, $user->id);
        self::assertEquals('11', $user->remoteId);
        self::assertEquals('Joe', $user->firstName);
        self::assertEquals('Rogan', $user->middleName);
        self::assertEquals('Customer', $user->lastName);
        self::assertEquals('joe@test.com', $user->email);
        self::assertEquals('0412312412', $user->phone);
        self::assertEquals('CONSUMER', $user->role);
        self::assertEquals('salesforce-contact-id', $user->sfContactId);
        self::assertEquals('universal-id', $user->uid);
    }

    /**
     * @covers ::__construct
     * @covers ::getUser
     * @covers ::logResponse
     */
    public function testgetUserFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getUser: 404: Not found'
        );
        $this->brighteApi->expects(self::once())->method('get')
            ->with('/identity/users/1')->willReturn($response);
        $user = $this->identityApi->getUser(1);
        self::assertNull($user);
    }

    /**
     * @covers ::__construct
     * @covers ::createUser
     */
    public function testCreateUser(): void
    {
        $expectedBody = [
            'email' => 'joe@test.com',
            'mobile' => '0412312412',
            'role' => 'CONSUMER',
            'firstName' => 'Joe',
            'lastName' => 'Customer',
        ];
        $providedUser = new User();
        $providedUser->firstName = 'Joe';
        $providedUser->lastName = 'Customer';
        $providedUser->email = 'joe@test.com';
        $providedUser->phone = '0412312412';
        $providedUser->role = 'CONSUMER';
        $response = new Response(200, [], json_encode($providedUser));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/identity/users', json_encode($expectedBody))
            ->willReturn($response);
        $this->identityApi->createUser($providedUser);
    }

    /**
     * @covers ::__construct
     * @covers ::createUser
     * @covers ::logResponse
     */
    public function testCreateUserFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->createUser: 404: Not found'
        );
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/identity/users')->willReturn($response);
        $user = $this->identityApi->createUser(new User());
        self::assertNull($user);
    }

    /**
     * @covers ::__construct
     * @covers ::getUserTokenByCode
     * @covers \BrighteCapital\Api\Models\IdentityTokenResponse::__construct
     */
    public function testGetUserTokenByCode(): void
    {
        $code = 'one-time-authorization-code';
        $expectedBody = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => 'test-client',
        ];
        $responseObject = new IdentityTokenResponse(
            'access-token',
            'refresh-token',
            900,
            'bearer'
        );
        $this->brighteApi->legacyClientId = 'test-client';
        $response = new Response(200, [], json_encode([
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_in' => 900,
            'token_type' => 'bearer',
        ]));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/identity/token', json_encode($expectedBody))
            ->willReturn($response);
        self::assertEquals(
            $responseObject,
            $this->identityApi->getUserTokenByCode($code)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getUserTokenByCode
     * @covers \BrighteCapital\Api\Models\IdentityTokenResponse::__construct
     * @uses BrighteCapital\Api\AbstractApi
     */
    public function testGetUserTokenByCodeFailure(): void
    {
        $code = 'one-time-authorization-code';
        $expectedBody = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => 'test-client',
        ];
        $this->brighteApi->legacyClientId = 'test-client';
        $response = new Response(401, [], json_encode([
            'error' => 'invalid_grant',
            'error_description' => 'NO_USER_SESSION_FOUND',
        ]));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/identity/token', json_encode($expectedBody))
            ->willReturn($response);
        $this->expectException(AuthenticationFailedException::class);
        $this->identityApi->getUserTokenByCode($code);
    }

    /**
     * @covers ::__construct
     * @covers ::refreshToken
     * @covers \BrighteCapital\Api\Models\IdentityTokenResponse::__construct
     */
    public function testRefreshToken(): void
    {
        $refreshToken = 'fake-refresh-token';
        $expectedBody = ['refreshToken' => $refreshToken];
        $responseObject = new IdentityTokenResponse(
            'access-token',
            'refresh-token',
            900,
            'bearer'
        );
        $response = new Response(200, [], json_encode([
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_in' => 900,
            'token_type' => 'bearer',
        ]));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/identity/refresh', json_encode($expectedBody))
            ->willReturn($response);
        self::assertEquals(
            $responseObject,
            $this->identityApi->refreshToken($refreshToken)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::refreshToken
     * @uses BrighteCapital\Api\AbstractApi
     */
    public function testRefreshTokenFail(): void
    {
        $refreshToken = 'fake-refresh-token';
        $expectedBody = ['refreshToken' => $refreshToken];
        $response = new Response(401, [], json_encode([
            'code' => 'jwt expired'
        ]));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/identity/refresh', json_encode($expectedBody))
            ->willReturn($response);
        $this->expectException(AuthenticationFailedException::class);
        $this->identityApi->refreshToken($refreshToken);
    }

    /**
     * @covers ::__construct
     * @covers ::getUserByMobileAndOrEmail
     */
    public function testGetUserByMobileAndOrEmail(): void
    {
        $user = [
            'id' => 1,
            'remoteId' => '11',
            'uid' => 'universal-id',
            'firstName' => 'Joe',
            'middleName' => null,
            'lastName' => 'Customer',
            'email' => 'joe@test.com',
            'mobile' => '0412312412',
            'role' => 'CONSUMER',
        ];

        $response = new Response(200, [], json_encode($user));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/identity/users/get-user-by-mobile-and-or-email', json_encode([
                'mobile' => '0412312412',
                'email' => 'joe@test.com',
            ]))
            ->willReturn($response);
        $user = $this->identityApi->getUserByMobileAndOrEmail('0412312412', 'joe@test.com');

        self::assertInstanceOf(User::class, $user);
        self::assertEquals(1, $user->id);
        self::assertEquals('11', $user->remoteId);
        self::assertEquals('universal-id', $user->uid);
        self::assertEquals('Joe', $user->firstName);
        self::assertEquals(null, $user->middleName);
        self::assertEquals('Customer', $user->lastName);
        self::assertEquals('joe@test.com', $user->email);
        self::assertEquals('0412312412', $user->phone);
        self::assertEquals('CONSUMER', $user->role);
    }

    /**
     * @covers ::__construct
     * @covers ::getUserByMobileAndOrEmail
     */
    public function testGetUserByMobileAndOrEmailFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('/identity/users/get-user-by-mobile-and-or-email', json_encode([
                'mobile' => '0412312412',
                'email' => 'joe@test.com',
            ]))
            ->willReturn($response);

        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getUserByMobileAndOrEmail: 404: Not found'
        );

        $user = $this->identityApi->getUserByMobileAndOrEmail('0412312412', 'joe@test.com');
        self::assertEquals(null, $user);
    }
}
