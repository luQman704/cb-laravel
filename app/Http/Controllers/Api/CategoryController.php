<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductListResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        // Load only root categories (no parent), then load nested children recursively
        $categories = Category::with($this->nestedChildrenRelation())
            ->whereNull('parent_id')
            ->where('active', true)
            ->orderBy('position')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function products(Request $request, string $slug): AnonymousResourceCollection
    {
        $category = Category::where('slug', $slug)->where('active', true)->firstOrFail();

        $categoryIds = $this->getCategoryIds($category);

        $products = \App\Models\Product::with(['images', 'stock', 'categories'])
            ->where('active', true)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->paginate(20);

        return ProductListResource::collection($products);
    }

    /**
     * Build a recursive eager-load string for nested children (up to 5 levels deep).
     */
    private function nestedChildrenRelation(): array
    {
        return ['children.children.children.children.children'];
    }

    private function getCategoryIds(Category $category): array
    {
        $ids = [$category->id];

        $children = Category::where('parent_id', $category->id)->where('active', true)->get();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getCategoryIds($child));
        }

        return $ids;
    }
}
