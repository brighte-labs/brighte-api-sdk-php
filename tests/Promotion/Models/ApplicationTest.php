<?php

namespace BrighteCapital\Api\Tests\Promotion\Models;

use BrighteCapital\Api\Promotion\Models\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    /**
     * @covers \BrighteCapital\Api\Promotion\Models\Application::toArray
     * @covers \BrighteCapital\Api\Promotion\Models\Application::__construct
     */
    public function testToArrayReturnsAssocArray()
    {
        $applicationPromotion = new Application('E451', 'E5', 'Brighte_pay', 'pre-approval', 'somecode');
        $expected = [
            'id' => 'E451',
            'vendorId' => 'E5',
            'product' => 'Brighte_pay',
            'code' => 'somecode',
            'applicationType' => 'pre-approval'
        ];

        $this->assertEquals($expected, $applicationPromotion->toArray());
    }
}
