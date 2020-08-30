<?php

namespace BrighteCapital\Api\Promotion\Models;

class Application
{
    /** @var string application ID */
    public $applicationId;

    /** @var string vendor ID */
    public $vendorId;

    /** @var string product type */
    public $product;

    /** @var bool isGreenCategory */
    public $isGreenCategory;

    /**
     * Application constructor.
     * @param string $applicationId
     * @param string $vendorId
     * @param string $product
     * @param bool $isGreenCategory
     */
    public function __construct(string $applicationId, string $vendorId, string $product, bool $isGreenCategory = true)
    {
        $this->applicationId = $applicationId;
        $this->vendorId = $vendorId;
        $this->product = $product;
        $this->isGreenCategory = $isGreenCategory;
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
            'isGreenCategory' => $this->isGreenCategory,
        ];
    }
}
