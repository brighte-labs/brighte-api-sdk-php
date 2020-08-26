<?php

namespace BrighteCapital\Api\Tests\Promotion\Models;

use BrighteCapital\Api\Promotion\Models\ApplicationPromotion;
use PHPUnit\Framework\TestCase;

class ApplicationPromotionTest extends TestCase
{
    /**
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\ApplicationPromotion::__construct
     */
    public function testToArrayReturnsAssocArray()
    {
        $applicationPromotion = new ApplicationPromotion(10, 1, 5, 'Brighte_pay');
        $expected = [
            'promotionId' => 10,
            'applicationId' => 1,
            'vendorId' => 5,
            'product' => 'Brighte_pay',
        ];

        $this->assertEquals($expected, $applicationPromotion->toArray());
    }
}
