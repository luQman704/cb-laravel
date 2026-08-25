<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    private array $totals;

    public function __construct($resource, array $totals = [])
    {
        parent::__construct($resource);
        $this->totals = $totals;
    }

    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'items'            => CartItemResource::collection($this->items),
            'subtotal_ex_tax'  => $this->totals['subtotal_ex_tax'] ?? 0,
            'subtotal_inc_tax' => $this->totals['subtotal_inc_tax'] ?? 0,
            'item_count'       => $this->totals['item_count'] ?? 0,
            'currency'         => 'ZAR',
        ];
    }
}
