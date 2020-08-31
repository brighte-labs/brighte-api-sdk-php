<?php

namespace BrighteCapital\Api\Tests\Promotion\Models;

use BrighteCapital\Api\Promotion\Models\Promotion;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass  \BrighteCapital\Api\Promotion\Models\Promotion
 */
class PromotionTest extends TestCase
{
    /**
     * @covers \BrighteCapital\Api\Promotion\Models\Promotion::toArray
     */
    public function testToArrayReturnsArray()
    {
        $promotion = new Promotion();
        $promotion->id = 1;
        $promotion->code = 'code123';
        $promotion->products[] = 'Brighte_pay';
        $promotion->products[] = 'BGL';
        $promotion->productTypes = '{BGL}';
        $promotion->start = '2016-10-05 23:00:03';
        $promotion->end = '2016-10-05 23:00:03';
        $promotion->created = '2016-10-05 23:00:03';
        $promotion->modified = '2016-10-05 23:00:03';

        $expected = [
            'id' => 1,
            'code' => 'code123',

            'products' => [
                0 => 'Brighte_pay',
                1 => 'BGL'
            ],

            'promotionTypeId' => null,
            'description' => null,
            'contents' => null,
            'displayTitle' => null,
            'displayText' => null,
            'productTypes' => "{BGL}",
            'start' => '2016-10-05 23:00:03',
            'end' => '2016-10-05 23:00:03',
            'created' => '2016-10-05 23:00:03',
            'modified' => '2016-10-05 23:00:03',
        ];

        $this->assertEquals($expected, $promotion->toArray());
    }
}
