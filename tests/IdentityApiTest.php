<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\IdentityApi;
use BrighteCapital\Api\Models\User;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\IdentityApi
 */
class IdentityApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
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
            'lastName' => 'Customer',
            'email' => 'joe@test.com',
            'phone' => '0412312412',
            'role' => 'CONSUMER',
            'sfContactId' => 'salesforce-contact-id',
        ];
        $response = new Response(200, [], json_encode($providedUser));
        $this->brighteApi->expects(self::once())->method('get')
            ->with('/identity/users/1')->willReturn($response);
        $user = $this->identityApi->getUser(1);
        self::assertInstanceOf(User::class, $user);
        self::assertEquals(1, $user->id);
        self::assertEquals('11', $user->remoteId);
        self::assertEquals('Joe', $user->firstName);
        self::assertEquals('Customer', $user->lastName);
        self::assertEquals('joe@test.com', $user->email);
        self::assertEquals('0412312412', $user->phone);
        self::assertEquals('CONSUMER', $user->role);
        self::assertEquals('salesforce-contact-id', $user->sfContactId);
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
}
