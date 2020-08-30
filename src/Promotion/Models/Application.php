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

    /** @var string isGreenCategory */
    public $isGreenCategory;

    /**
     * Application constructor.
     * @param int $applicationId application id
     * @param int $vendorId vendor id
     * @param string $product product type
     * @param bool $isGreenCategory
     */
    public function __construct(int $applicationId, int $vendorId, string $product, bool $isGreenCategory = true)
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
