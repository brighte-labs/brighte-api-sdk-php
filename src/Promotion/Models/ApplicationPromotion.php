<?php

namespace BrighteCapital\Api\Promotion\Models;

class ApplicationPromotion
{
    /** @var int application ID */
    public $applicationId;

    /** @var int vendor ID */
    public $vendorId;

    /** @var string product type */
    public $product;

    /** @var string product variant */
    public $product_variant;

    /**
     * ApplicationPromotion constructor.
     * @param int $applicationId application id
     * @param int $vendorId vendor id
     * @param string $product product type
     * @param null $variant
     */
    public function __construct(int $applicationId, int $vendorId, string $product, $variant = null)
    {
        $this->applicationId = $applicationId;
        $this->vendorId = $vendorId;
        $this->product = $product;
        $this->product_variant = $variant;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'applicationId' => $this->applicationId,
            'vendorId' => $this->vendorId,
            'product' => $this->product,
            'product_variant' => $this->product_variant,
        ];
    }
}
