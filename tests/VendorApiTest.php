<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Models\Category;
use BrighteCapital\Api\Models\PromoCode;
use BrighteCapital\Api\Models\Vendor;
use BrighteCapital\Api\Models\VendorFlag;
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
            'remoteId' => '11',
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
        self::assertEquals('11', $vendors[1]->remoteId);
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
     * @covers ::getVendor
     */
    public function testGetVendor(): void
    {
        $providedVendor = [
            'id' => 1,
            'remoteId' => '11',
            'tradingName' => 'Solar Installers Inc.',
            'salesforceAccountId' => 'salesforce-account-id',
            'accountsEmail' => 'accounts@solar.inc',
            'slug' => 'solar-installers-inc',
        ];
        $response = new Response(200, [], json_encode($providedVendor));
        $this->brighteApi->expects(self::once())->method('get')->with('/vendors/1')->willReturn($response);
        $vendor = $this->vendorApi->getVendor(1);
        self::assertInstanceOf(Vendor::class, $vendor);
        self::assertEquals(1, $vendor->id);
        self::assertEquals('11', $vendor->remoteId);
        self::assertEquals('Solar Installers Inc.', $vendor->tradingName);
        self::assertEquals('salesforce-account-id', $vendor->salesforceAccountId);
        self::assertEquals('accounts@solar.inc', $vendor->accountsEmail);
        self::assertEquals('solar-installers-inc', $vendor->slug);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendor
     * @covers ::logResponse
     */
    public function testGetVendorFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getVendor: 404: Not found'
        );
        $this->brighteApi->expects(self::once())->method('get')->with('/vendors/1')->willReturn($response);
        $vendor = $this->vendorApi->getVendor(1);
        self::assertNull($vendor);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorFlags
     */
    public function testGetVendorFlags(): void
    {
        $providedFlag = [
            'id' => 1,
            'flag' => 'test_flag',
            'description' => 'Test Flag',
        ];
        $response = new Response(200, [], json_encode([$providedFlag]));
        $this->brighteApi->expects(self::once())->method('get')->with('/vendors/1/flags')->willReturn($response);
        $flags = $this->vendorApi->getVendorFlags(1);
        self::assertCount(1, $flags);
        self::assertInstanceOf(VendorFlag::class, $flags[0]);
        self::assertEquals(1, $flags[0]->id);
        self::assertEquals('test_flag', $flags[0]->flag);
        self::assertEquals('Test Flag', $flags[0]->description);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorFlags
     * @covers ::logResponse
     */
    public function testGetVendorFlagsFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getVendorFlags: 404: Not found'
        );
        $this->brighteApi->expects(self::once())->method('get')->with('/vendors/1/flags')->willReturn($response);
        $flags = $this->vendorApi->getVendorFlags(1);
        self::assertEmpty($flags);
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
     * @covers ::getCategories
     */
    public function testGetCategories(): void
    {
        $providedVendor = ['id' => 1, 'name' => 'Solar Systems', 'slug' => 'solar-systems'];
        $response = new Response(200, [], json_encode([$providedVendor]));
        $this->brighteApi->expects(self::once())->method('get')->with('/categories')->willReturn($response);
        $categories = $this->vendorApi->getCategories();
        self::assertCount(1, $categories);
        self::assertInstanceOf(Category::class, $categories[1]);
        self::assertEquals(1, $categories[1]->id);
        self::assertEquals('Solar Systems', $categories[1]->name);
        self::assertEquals('solar-systems', $categories[1]->slug);
    }

    /**
     * @covers ::__construct
     * @covers ::getCategories
     * @covers ::logResponse
     */
    public function testGetCategoriesFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getCategories: 404: Not found'
        );
        $this->brighteApi->method('get')->willReturn($response);
        $categories = $this->vendorApi->getCategories(1);
        self::assertCount(0, $categories);
    }

    /**
     * @covers ::__construct
     * @covers ::getCategory
     */
    public function testGetCategory(): void
    {
        $result = ['id' => 1, 'name' => 'Solar Systems', 'slug' => 'solar-systems', 'group' => 'Green'];
        $response = new Response(200, [], json_encode($result));
        $this->brighteApi->expects(self::once())->method('get')->with('/categories/1')->willReturn($response);
        $category = $this->vendorApi->getCategory(1);
        self::assertInstanceOf(Category::class, $category);
        self::assertEquals(1, $category->id);
        self::assertEquals('Solar Systems', $category->name);
        self::assertEquals('solar-systems', $category->slug);
        self::assertEquals('Green', $category->group);
    }

    /**
     * @covers ::__construct
     * @covers ::getCategory
     * @covers ::logResponse
     */
    public function testGetCategoryFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getCategory: 404: Not found'
        );
        $this->brighteApi->method('get')->willReturn($response);
        $category = $this->vendorApi->getCategory(1);
        self::assertNull($category);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorPromos
     */
    public function testGetVendorPromos(): void
    {
        $providedVendorPromo = [
            'id' => 20,
            'code' => 'Xmas20',
            'type' => 'DeferredPayment',
            'start' => '2019-11-03 14:00:00',
            'end' => '2020-12-26 13:59:00',
        ];
        $response = new Response(200, [], json_encode([$providedVendorPromo]));
        $this->brighteApi->expects(
            self::once()
        )->method('get')->with('/vendors/1/promos?active=1')->willReturn($response);
        $vendorPromos = $this->vendorApi->getVendorPromos(1, true);
        self::assertCount(1, $vendorPromos);
        self::assertInstanceOf(PromoCode::class, $vendorPromos[20]);
        self::assertEquals(20, $vendorPromos[20]->id);
        self::assertEquals('Xmas20', $vendorPromos[20]->code);
        self::assertEquals('DeferredPayment', $vendorPromos[20]->type);
        self::assertEquals('2019-11-03 14:00:00', $vendorPromos[20]->start);
        self::assertEquals('2020-12-26 13:59:00', $vendorPromos[20]->end);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorPromos
     * @covers ::logResponse
     */
    public function testGetVendorPromosFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getVendorPromos: 404: Not found'
        );
        $this->brighteApi->method('get')->willReturn($response);
        $promos = $this->vendorApi->getVendorPromos(1, true);
        self::assertCount(0, $promos);
    }
}
