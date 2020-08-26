<?php

namespace BrighteCapital\Api\Promotion\Models;

class Promotion
{
    /** @var int */
    public $id;

    /** @var string */
    public $code;

    /** @var array */
    public $products;

    /** @var int */
    public $type_id;

    /** @var string */
    public $description;

    /** @var string */
    public $contents;

    /** @var string */
    public $display_title;

    /** @var string */
    public $display_text;

    /** @var string */
    public $start_date;

    /** @var \string */
    public $end_date;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'products' => $this->products,
            'type_id' => $this->type_id,
            'description' => $this->description,
            'contents' => $this->contents,
            'display_title' => $this->display_title,
            'display_text' => $this->display_text,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
