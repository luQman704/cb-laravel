<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $coverImage = $this->images->firstWhere('is_cover', true);
        $coverUrl = $coverImage ? '/storage/products/' . $coverImage->filename : null;

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'reference'         => $this->reference,
            'price_ex_tax'      => round((float) $this->price, 2),
            'price_inc_tax'     => round((float) $this->price * 1.15, 2),
            'active'            => $this->active,
            'weight'            => $this->weight,
            'description'       => $this->description,
            'short_description' => $this->short_description,
            'cover_image_url'   => $coverUrl,
            'images'            => $this->images->map(fn ($img) => [
                'id'       => $img->id,
                'url'      => '/storage/products/' . $img->filename,
                'position' => $img->position,
                'is_cover' => $img->is_cover,
            ]),
            'categories'        => $this->categories->map(fn ($cat) => [
                'id'   => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ]),
            'stock'             => $this->stock ? [
                'quantity'           => $this->stock->quantity,
                'allow_out_of_stock' => $this->stock->allow_out_of_stock,
            ] : null,
            'created_at'        => $this->created_at,
        ];
    }
}
