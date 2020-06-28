<?php

declare(strict_types=1);

namespace BrighteCapital\SlimCore\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Models\Category;
use BrighteCapital\Api\Models\Vendor;
use BrighteCapital\Api\VendorApi;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\VendorApi
 */
class VendorApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $brighteApi;

    /** @var \BrighteCapital\Api\VendorApi */
    protected $vendorApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brighteApi = $this->createMock(BrighteApi::class);
        $this->vendorApi = new VendorApi($this->logger, $this->brighteApi);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendors
     */
    public function testGetVendors(): void
    {
        $providedVendor = [
            'id' => 1,
            'tradingName' => 'Solar Installers Inc.',
            'salesforceAccountId' => 'salesforce-account-id',
            'accountsEmail' => 'accounts@solar.inc',
            'slug' => 'solar-installers-inc',
        ];
        $response = new Response(200, [], json_encode([$providedVendor]));
        $this->brighteApi->expects(self::once())->method('get')->with('/vendors')->willReturn($response);
        $vendors = $this->vendorApi->getVendors();
        self::assertCount(1, $vendors);
        self::assertInstanceOf(Vendor::class, $vendors[1]);
        self::assertEquals(1, $vendors[1]->id);
        self::assertEquals('Solar Installers Inc.', $vendors[1]->tradingName);
        self::assertEquals('salesforce-account-id', $vendors[1]->salesforceAccountId);
        self::assertEquals('accounts@solar.inc', $vendors[1]->accountsEmail);
        self::assertEquals('solar-installers-inc', $vendors[1]->slug);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendors
     * @covers ::logResponse
     */
    public function testGetVendorsFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getVendors: 404: Not found'
        );
        $this->brighteApi->expects(self::once())->method('get')->with('/vendors')->willReturn($response);
        $vendors = $this->vendorApi->getVendors();
        self::assertCount(0, $vendors);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorAgentIds
     */
    public function testGetVendorAgents(): void
    {
        $providedAgents = [['userId' => 2], ['userId' => 3], ['userId' => 4]];
        $response = new Response(200, [], json_encode($providedAgents));
        $this->brighteApi->expects(self::once())->method('get')->with('/vendors/1/agents')->willReturn($response);
        $agentIds = $this->vendorApi->getVendorAgentIDs(1);
        self::assertCount(3, $agentIds);
        self::assertEquals([2, 3, 4], $agentIds);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorAgentIDs
     * @covers ::logResponse
     */
    public function testGetVendorAgentsFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getVendorAgentIDs: 404: Not found'
        );
        $this->brighteApi->method('get')->willReturn($response);
        $agents = $this->vendorApi->getVendorAgentIDs(1);
        self::assertCount(0, $agents);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorCategories
     */
    public function testGetVendorCategories(): void
    {
        $providedVendor = ['id' => 1, 'name' => 'Solar Systems', 'slug' => 'solar-systems'];
        $response = new Response(200, [], json_encode([$providedVendor]));
        $this->brighteApi->expects(self::once())->method('get')->with('/vendors/1/categories')->willReturn($response);
        $categories = $this->vendorApi->getVendorCategories(1);
        self::assertCount(1, $categories);
        self::assertInstanceOf(Category::class, $categories[0]);
        self::assertEquals(1, $categories[0]->id);
        self::assertEquals('Solar Systems', $categories[0]->name);
        self::assertEquals('solar-systems', $categories[0]->slug);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorCategories
     * @covers ::logResponse
     */
    public function testGetVendorCategoriesFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getVendorCategories: 404: Not found'
        );
        $this->brighteApi->method('get')->willReturn($response);
        $categories = $this->vendorApi->getVendorCategories(1);
        self::assertCount(0, $categories);
    }
}
