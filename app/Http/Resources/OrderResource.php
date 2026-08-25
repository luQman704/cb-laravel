<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status,
            'subtotal'       => $this->subtotal,
            'tax_amount'     => $this->tax_amount,
            'shipping_cost'  => $this->shipping_cost,
            'total'          => $this->total,
            'payment_method' => $this->payment_method,
            'currency'       => 'ZAR',
            'ship_firstname' => $this->ship_firstname,
            'ship_lastname'  => $this->ship_lastname,
            'ship_address1'  => $this->ship_address1,
            'ship_address2'  => $this->ship_address2,
            'ship_city'      => $this->ship_city,
            'ship_postcode'  => $this->ship_postcode,
            'ship_country'   => $this->ship_country,
            'ship_phone'     => $this->ship_phone,
            'items'          => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at'     => $this->created_at,
        ];
    }
}
