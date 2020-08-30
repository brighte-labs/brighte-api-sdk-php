<?php

namespace BrighteCapital\Api\Promotion\Models;

class Promotion
{
    /** @var string */
    public $id;

    /** @var string */
    public $code;

    /** @var array */
    public $products;

    /** @var string */
    public $promotion_type_id;

    /** @var string */
    public $description;

    /** @var string */
    public $contents;

    /** @var string */
    public $display_title;

    /** @var string */
    public $display_text;

    /** @var string */
    public $start;

    /** @var \string */
    public $end;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'products' => $this->products,
            'promotion_type_id' => $this->promotion_type_id,
            'description' => $this->description,
            'contents' => $this->contents,
            'display_title' => $this->display_title,
            'display_text' => $this->display_text,
            'start' => $this->start,
            'end' => $this->end,
        ];
    }
}
