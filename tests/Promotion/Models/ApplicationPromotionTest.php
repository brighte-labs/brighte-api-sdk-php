<?php

namespace BrighteCapital\Api\Tests\Promotion\Models;

use BrighteCapital\Api\Promotion\Models\Application;
use PHPUnit\Framework\TestCase;

class ApplicationPromotionTest extends TestCase
{
    /**
     * @covers \BrighteCapital\Api\Promotion\Models\Application::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     */
    public function testToArrayReturnsAssocArray()
    {
        $applicationPromotion = new Application(1, 5, 'Brighte_pay', 'BPL-HI');
        $expected = [
            'applicationId' => 1,
            'vendorId' => 5,
            'product' => 'Brighte_pay',
            'product_variant' => 'BPL-HI'
        ];

        $this->assertEquals($expected, $applicationPromotion->toArray());
    }
}
