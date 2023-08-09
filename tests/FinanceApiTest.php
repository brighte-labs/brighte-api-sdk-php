<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\FinanceApi;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\FinanceApi
 */
class FinanceApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $brighteApi;

    /** @var \BrighteCapital\Api\FinanceApi */
    protected $financeApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brighteApi = $this->createMock(BrighteApi::class);
        $this->financeApi = new FinanceApi($this->logger, $this->brighteApi);
    }

    /**
     * @covers ::__construct
     * @covers ::getApplicationId
     */
    public function testGetApplicationId(): void
    {
        $consumerApplicationRemoteId = '10200';
        $expectedApplicationId = ['id' => 'c678681e-4261-22ee-9ad5-0242ac110004'];

        $response = new Response(200, [], json_encode($expectedApplicationId));

        $this->brighteApi->expects(self::once())->method('get')
            ->with('/finance/applications/account/' . $consumerApplicationRemoteId)
            ->willReturn($response);

        $result = $this->financeApi->getApplicationId($consumerApplicationRemoteId);

        self::assertEquals($expectedApplicationId['id'], $result);
    }

    /**
     * @covers ::__construct
     * @covers ::getApplicationId
     * @covers ::logResponse
     */
    public function testGetApplicationIdFail(): void
    {
        $consumerApplicationRemoteId = '10200';
        $expectedResponse = ["message" => "unauthorized"];

        $response = new Response(401, [], json_encode($expectedResponse));

        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getApplicationId: 401: unauthorized'
        );

        $this->brighteApi->expects(self::once())->method('get')
            ->with('/finance/applications/account/' . $consumerApplicationRemoteId)
            ->willReturn($response);

        $result = $this->financeApi->getApplicationId($consumerApplicationRemoteId);

        self::assertNull($result);
    }

    /**
     * @covers ::__construct
     * @covers ::addJointApplicant
     */
    public function testAddJointApplicant(): void
    {
        $applicant = [
            'first_name' => 'John',
            'email' => 'john.ken@test.co',
            'mobile' => '0412345678'
        ];

        $inputData = [
            'firstName' => $applicant['first_name'],
            'email' => $applicant['email'],
            'mobile' => $applicant['mobile'],
            'present' => false,
        ];

        $applicationId = 'c678681e-4261-22ee-9ad5-0242ac110004';

        $expectedApplicantId = ['id' => '2f1994ae-326b-11ee-9465-0242ac110004'];

        $response = new Response(201, [], json_encode($expectedApplicantId));

        $this->brighteApi->expects(self::once())->method('post')
            ->with('/finance/applications/' . $applicationId . '/applicants', json_encode($inputData))
            ->willReturn($response);

        $result = $this->financeApi->addJointApplicant($applicant, $applicationId);

        self::assertEquals($expectedApplicantId['id'], $result);
    }

    /**
     * @covers ::__construct
     * @covers ::addJointApplicant
     * @covers ::logResponse
     */
    public function testAddJointApplicantFail(): void
    {
        $applicant = [
            'first_name' => 'John',
            'email' => 'john.ken@test.co',
            'mobile' => '0412345678'
        ];

        $inputData = [
            'firstName' => $applicant['first_name'],
            'email' => $applicant['email'],
            'mobile' => $applicant['mobile'],
            'present' => false,
        ];

        $applicationId = 'c678681e-4261-22ee-9ad5-0242ac110004';

        $expectedResponse = ['message' => 'Application not found'];

        $response = new Response(404, [], json_encode($expectedResponse));

        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->addJointApplicant: 404: Application not found'
        );

        $this->brighteApi->expects(self::once())->method('post')
            ->with('/finance/applications/' . $applicationId . '/applicants', json_encode($inputData))
            ->willReturn($response);

        $result = $this->financeApi->addJointApplicant($applicant, $applicationId);

        self::assertNull($result);
    }
}
