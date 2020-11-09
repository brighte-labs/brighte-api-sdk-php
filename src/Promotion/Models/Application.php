<?php

namespace BrighteCapital\Api\Promotion\Models;

class Application
{
    /** @var string application ID */
    public $id;

    /** @var string vendor ID */
    public $vendorId;

    /** @var string product type */
    public $product;

    /** @var string promotion code */
    public $code;

    /** @var string applicaiton type */
    public $applicationType;

    /**
     * Application constructor.
     * @param string $id application remote id
     * @param string $vendorId vendor remote id
     * @param string $product product code name
     * @param string|null $applicationType promo code
     * @param string|null $code promo code
     */
    public function __construct(
        string $id,
        string $vendorId,
        string $product,
        string $applicationType = '',
        string $code = null
    ) {
        $this->id = $id;
        $this->vendorId = $vendorId;
        $this->product = $product;
        $this->code = $code;
        $this->applicationType = $applicationType;
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vendorId' => $this->vendorId,
            'product' => $this->product,
            'code' => $this->code,
            'applicationType' => $this->applicationType,
        ];
    }
}
