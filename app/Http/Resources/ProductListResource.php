<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $coverImage = $this->images->firstWhere('is_cover', true);
        $coverUrl = $coverImage ? '/storage/products/' . $coverImage->filename : null;

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'reference'       => $this->reference,
            'price_ex_tax'    => round((float) $this->price, 2),
            'price_inc_tax'   => round((float) $this->price * 1.15, 2),
            'active'          => $this->active,
            'cover_image_url' => $coverUrl,
            'stock_qty'       => $this->stock ? $this->stock->quantity : 0,
        ];
    }
}
