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

    public $promotionTyped;

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

    /** @var string */
    public $created;

    /** @var \string */
    public $modified;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'products' => $this->products,
            'promotionTypeId' => $this->promotion_type_id,
            'description' => $this->description,
            'contents' => $this->contents,
            'displayTitle' => $this->display_title,
            'displayText' => $this->display_text,
            'start' => $this->start,
            'end' => $this->end,
            'created' => $this->created,
            'modified' => $this->modified,
        ];
    }
}
