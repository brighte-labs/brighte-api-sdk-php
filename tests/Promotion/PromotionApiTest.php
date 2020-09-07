<?php

namespace BrighteCapital\Api\Tests;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Exceptions\BadRequestException;
use BrighteCapital\Api\Exceptions\RecordNotFoundException;
use BrighteCapital\Api\Promotion\Exceptions\PromotionException;
use BrighteCapital\Api\Promotion\Models\Application;
use BrighteCapital\Api\Promotion\Models\Promotion;
use BrighteCapital\Api\Promotion\PromotionApi;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;

/**@coversDefaultClass  \BrighteCapital\Api\Promotion\PromotionApi */
class PromotionApiTest extends TestCase
{
    /**'
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $apiClient;
    /**
     * @var \BrighteCapital\Api\Promotion\PromotionApi
     */
    private $api;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $response;
    /**
     * @var \BrighteCapital\Api\Promotion\Models\Application
     */
    private $application;

    /**
     * @covers ::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     */
    protected function setUp()
    {
        parent::setUp();
        $this->apiClient = $this->createMock(BrighteApi::class);
        $this->api = new PromotionApi(new NullLogger(), $this->apiClient);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->application =
            new Application('1', '5', 'Brighte_pay');
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @covers \BrighteCapital\Api\Exceptions\BadRequestException::__construct
     */
    public function testApplyPromotionThrowsBadException()
    {
        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->application->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_BAD_REQUEST);

        $this->expectException(BadRequestException::class);
        $this->api->applyPromotion($this->application);
    }

    /**
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\Promotion\Models\Application::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     */
    public function testApplyPromotionReturnsNullForNoContentStatusCode()
    {
        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->application->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_NO_CONTENT);

        $this->assertNull($this->api->applyPromotion($this->application));
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @covers \BrighteCapital\Api\Exceptions\BadRequestException::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\PromotionException::__construct
     */
    public function testApplyPromotionThrowsPromotionException()
    {
        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->application->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode');


        $this->expectException(PromotionException::class);
        $this->api->applyPromotion($this->application);
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @uses   \BrighteCapital\Api\Promotion\Models\Promotion::toArray()
     */
    public function testApplyPromotionReturnsPromotionCode()
    {
        $application = new Application(1,'E10', 'BGL', 'gold20Test');

        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->application->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_CREATED);
        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($application->toArray()));

        $actual = $this->api->applyPromotion($this->application);

        $this->assertEquals($application, $actual);

        $this->assertInstanceOf(Application::class, $actual);
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\PromotionException::__construct
     */
    public function testApplyPromotionThrowsExceptionWhenMappingJsonResponseToPromotionEntity()
    {
        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->application->toArray())
        )->willReturn($this->response);


        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(201);

        $this->response->expects($this->exactly(1))
            ->method('getBody')
            ->willReturn([]);

        $this->expectException(PromotionException::class);
        $this->api->applyPromotion($this->application);
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::getPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     */
    public function testGetPromotionReturnSinglePromotion()
    {
        $promoEntity = [
            'id' => 10,
            'code' => 'code',
        ];

        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions/10')->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_OK);
        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($promoEntity));

        $promotion = new Promotion();
        $promotion->id = 10;
        $promotion->code = 'code';

        $this->assertEquals($promotion, $this->api->getPromotion(10));
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::getPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @covers \BrighteCapital\Api\Exceptions\RecordNotFoundException::__construct
     */
    public function testGetPromotionThrowsRecordNotFoundException()
    {
        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions/10')->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode');

        $this->expectException(RecordNotFoundException::class);
        $this->api->getPromotion(10);
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::getPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\PromotionException::__construct
     */
    public function testGetPromotionThrowsPromotionException()
    {
        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions/10')->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_OK);


        $this->response->expects($this->once())
            ->method('getBody')->willReturn(new \stdClass());

        $this->expectException(PromotionException::class);
        $this->api->getPromotion(10);
    }

    /**
     * @covers   \BrighteCapital\Api\Promotion\PromotionApi::getPromotions
     * @uses     \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @uses     \BrighteCapital\Api\AbstractApi::__construct
     * @uses     \BrighteCapital\Api\BrighteApi::get
     */
    public function testGetPromotionsReturnListOfPromotions()
    {
        $promotionsList = [
            [
                'id' => 10,
                'code' => 'code',
            ]
        ];

        $promotionOne = new Promotion();
        $promotionOne->id = 10;
        $promotionOne->code = 'code';

        $expected = [
            0 => $promotionOne,
        ];

        $vendorId = 5;
        $product = 'Brighte_pay';
        $promoCode = 'code';
        $query = http_build_query(compact('vendorId', 'product', 'promoCode'));

        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions?' . $query)->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_OK);
        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($promotionsList));

        $actual = $this->api->getPromotions($query);
        $this->assertEquals($expected, $actual);

        $this->assertInstanceOf(Promotion::class, $expected[0]);
    }

    /**
     * @covers   \BrighteCapital\Api\Promotion\PromotionApi::getPromotions
     * @covers   \BrighteCapital\Api\Promotion\Exceptions\PromotionException::__construct
     * @uses     \BrighteCapital\Api\Promotion\Models\Application::__construct
     * @uses     \BrighteCapital\Api\AbstractApi::__construct
     * @uses     \BrighteCapital\Api\BrighteApi::get
     * uses     \BrighteCapital\Api\Promotion\Models\Application::__construct
     *
     */

    public function testGetPromotionsThrowPromotionException()
    {
        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions?')->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_OK);
        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn('sdf');

        $this->expectException(PromotionException::class);
        $this->api->getPromotions();
    }

    /**
     * @covers   \BrighteCapital\Api\Promotion\PromotionApi::getPromotions
     * @uses     \BrighteCapital\Api\AbstractApi::__construct
     * @uses     \BrighteCapital\Api\BrighteApi::get
     * @uses     \BrighteCapital\Api\Promotion\Models\Application::__construct
     */
    public function testGetPromotionsReturnEmptyArray()
    {
        $promotionsList = [];
        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions?')->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_OK);
        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($promotionsList));

        $this->assertEquals([], $this->api->getPromotions());
    }


    /**
     * @covers   \BrighteCapital\Api\Promotion\PromotionApi::getPromotions
     * @uses     \BrighteCapital\Api\AbstractApi::__construct
     * @uses     \BrighteCapital\Api\BrighteApi::get
     * @uses     \BrighteCapital\Api\Promotion\Models\Application::__construct
     */
    public function testGetPromotionsReturnEmptyArrayForNon200StatusCodes()
    {
        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions?')->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_BAD_REQUEST);

        $this->assertEquals([], $this->api->getPromotions());
    }
}
