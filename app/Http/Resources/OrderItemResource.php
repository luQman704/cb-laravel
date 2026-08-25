<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'product_name'      => $this->product_name,
            'product_reference' => $this->product_reference,
            'quantity'          => $this->quantity,
            'unit_price'        => $this->unit_price,
            'tax_rate'          => $this->tax_rate,
            'line_total'        => $this->line_total,
        ];
    }
}
