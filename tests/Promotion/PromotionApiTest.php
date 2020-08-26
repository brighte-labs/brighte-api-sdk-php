<?php

namespace BrighteCapital\Api\Tests;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Promotion\Exceptions\BadRequestException;
use BrighteCapital\Api\Promotion\Exceptions\PromotionException;
use BrighteCapital\Api\Promotion\Exceptions\RecordNotFoundException;
use BrighteCapital\Api\Promotion\Models\ApplicationPromotion;
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

    /**@covers \BrighteCapital\Api\Promotion\PromotionApi::__construct */
    protected function setUp()
    {
        parent::setUp();
        $this->apiClient = $this->createMock(BrighteApi::class);
        $this->api = new PromotionApi(new NullLogger(), $this->apiClient);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->applicationPromotion =
            new ApplicationPromotion(10, 1, 5, 'Brighte_pay');
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
     */
    public function testApplyPromotionReturnsPromotionCode()
    {
        $promoEntity = [
            'id' => 10,
            'code' => 'code'
        ];

        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->applicationPromotion->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_CREATED);
        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($promoEntity));

        $expected = json_decode(json_encode($promoEntity));
        $actual = $this->api->applyPromotion($this->applicationPromotion);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \BrighteCapital\Api\Promotion\PromotionApi::applyPromotion
     * @covers \BrighteCapital\Api\AbstractApi::__construct
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     * @covers \BrighteCapital\Api\Promotion\Exceptions\BadRequestException::__construct
     */
    public function testApplyPromotionThrowGenericException()
    {
        $this->apiClient->expects($this->once())->method('post')->with(
            '/promotions/applications',
            json_encode($this->applicationPromotion->toArray())
        )->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_BAD_REQUEST);

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
            'code' => 'code'
        ];

        $this->apiClient->expects($this->once())->method('get')
            ->with('/promotions/10')->willReturn($this->response);

        $this->response->expects($this->once())->method('getStatusCode')
            ->willReturn(StatusCodeInterface::STATUS_OK);
        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($promoEntity));

        $expected = json_decode(json_encode($promoEntity));
        $this->assertEquals($expected, $this->api->getPromotion(10));
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
}
