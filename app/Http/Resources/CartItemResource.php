<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $coverImage = $product->images->firstWhere('is_cover', true);
        $coverUrl = $coverImage ? '/storage/products/' . $coverImage->filename : null;

        $unitPriceEx  = round((float) $product->price, 2);
        $unitPriceInc = round($unitPriceEx * 1.15, 2);
        $qty          = $this->quantity;

        return [
            'id'                  => $this->id,
            'product'             => [
                'id'              => $product->id,
                'name'            => $product->name,
                'slug'            => $product->slug,
                'reference'       => $product->reference,
                'cover_image_url' => $coverUrl,
            ],
            'quantity'            => $qty,
            'unit_price_ex_tax'   => $unitPriceEx,
            'unit_price_inc_tax'  => $unitPriceInc,
            'line_total_ex_tax'   => round($qty * $unitPriceEx, 2),
            'line_total_inc_tax'  => round($qty * $unitPriceInc, 2),
        ];
    }
}
