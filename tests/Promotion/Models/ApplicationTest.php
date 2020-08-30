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
        $applicationPromotion = new Application('E451', 'E5', 'Brighte_pay');
        $expected = [
            'applicationId' => 'E451',
            'vendorId' => 'E5',
            'product' => 'Brighte_pay',
            'isGreenCategory' => true
        ];

        $this->assertEquals($expected, $applicationPromotion->toArray());
    }
}
