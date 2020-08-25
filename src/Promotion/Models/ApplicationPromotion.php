<?php

namespace BrighteCapital\Api\Promotion\Models;

class ApplicationPromotion
{
    /** @var int promotion ID */
    public $promotionId;

    /** @var int application ID */
    public $applicationId;

    /** @var int vendor ID */
    public $vendorId;

    /** @var string product type */
    public $product;

    /**
     * ApplicationPromotion constructor.
     * @param int $promotionId promotion Id
     * @param int $applicationId application id
     * @param int $vendorId vendor id
     * @param string $product product type
     */
    public function __construct(int $promotionId, int $applicationId, int $vendorId, string $product)
    {
        $this->promotionId = $promotionId;
        $this->applicationId = $applicationId;
        $this->vendorId = $vendorId;
        $this->product = $product;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'promotionId' => $this->promotionId,
            'applicationId' => $this->applicationId,
            'vendorId' => $this->vendorId,
            'product' => $this->product
        ];
    }
}
