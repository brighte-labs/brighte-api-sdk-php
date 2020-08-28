<?php

namespace BrighteCapital\Api\Tests;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Promotion\Exceptions\BadRequestException;
use BrighteCapital\Api\Promotion\Exceptions\PromotionException;
use BrighteCapital\Api\Promotion\Exceptions\RecordNotFoundException;
use BrighteCapital\Api\Promotion\Models\ApplicationPromotion;
use BrighteCapital\Api\Promotion\Models\Promotion;
use BrighteCapital\Api\Promotion\PromotionApi;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;

/**@coversDefaultClass  \BrighteCapital\Api\Promotion\PromotionApi */
class PromotionApiTest extends TestCase
{
    /**
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
     * @var \BrighteCapital\Api\Promotion\Models\ApplicationPromotion
     */
    private $applicationPromotion;

    /**
     * @covers ::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     */
    protected function setUp()
    {
        parent::setUp();
        $this->apiClient = $this->createMock(BrighteApi::class);
        $this->api = new PromotionApi(new NullLogger(), $this->apiClient);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->applicationPromotion =
            new ApplicationPromotion(1, 5, 'Brighte_pay');
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\BadRequestException::__construct
     */
    public function testApplyPromotionThrowsBadException()
    {
        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->applicationPromotion->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_BAD_REQUEST);

        $this->expectException(BadRequestException::class);
        $this->api->applyPromotion($this->applicationPromotion);
    }

    /**
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     */
    public function testApplyPromotionReturnsNullForNoContentStatusCode()
    {
        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->applicationPromotion->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_NO_CONTENT);

        $this->assertNull($this->api->applyPromotion($this->applicationPromotion));
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     * @uses   \BrighteCapital\Api\Promotion\Models\Promotion::toArray()
     * @uses   \Averor\JsonMapper\JsonMapper::map()
     */
    public function testApplyPromotionReturnsPromotionCode()
    {
        $promotion = new Promotion();
        $promotion->id = 1;
        $promotion->code = 10;
        $promotion->products[] = 'Brighte_pay';
        $promotion->products[] = 'BGL';

        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->applicationPromotion->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_CREATED);
        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($promotion->toArray()));

        $actual = $this->api->applyPromotion($this->applicationPromotion);

        $this->assertEquals($promotion, $actual);

        $this->assertInstanceOf(Promotion::class, $actual);
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\BadRequestException::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\PromotionException::__construct
     */
    public function testApplyPromotionThrowGenericException()
    {
        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->applicationPromotion->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode');


        $this->expectException(PromotionException::class);
        $this->api->applyPromotion($this->applicationPromotion);
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::getPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
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
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\RecordNotFoundException::__construct
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
     * @covers   \BrighteCapital\Api\Promotion\PromotionApi::getPromotions
     * @covers   \BrighteCapital\Api\AbstractApi::__construct
     * @covers   \BrighteCapital\Api\Promotion\Models\Promotion::__construct
     * @uses     \BrighteCapital\Api\BrighteApi::get
     * @uses     \Averor\JsonMapper\JsonMapper::mapArray()
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
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::getPromotions
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\PromotionException::__construct
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

        $actual = $this->api->getPromotions();
        $this->assertEquals([], $actual);
    }


    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::getPromotions
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\PromotionException::__construct
     */
    public function testGetPromotionsThrowsException()
    {
        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions?')->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode')
            ->willReturn(null);

        $this->expectException(PromotionException::class);
        $this->assertEquals([], $this->api->getPromotions());
    }
}
