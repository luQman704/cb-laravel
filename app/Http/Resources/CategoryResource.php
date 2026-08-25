<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'parent_id'   => $this->parent_id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'active'      => $this->active,
            'children'    => $this->when(
                $this->relationLoaded('children'),
                fn () => CategoryResource::collection($this->children->where('active', true)->values())
            ),
        ];
    }
}
