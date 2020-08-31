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

    /** @var string promotion code */
    public $code;

    /**
     * Application constructor.
     * @param string $applicationId
     * @param string $vendorId
     * @param string $product
     * @param string|null code
     */
    public function __construct(string $applicationId, string $vendorId, string $product, string $code = null)
    {
        $this->applicationId = $applicationId;
        $this->vendorId = $vendorId;
        $this->product = $product;
        $this->code = $code;
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
            'code' => $this->code,
        ];
    }
}
