<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['images', 'stock', 'categories'])
            ->where('active', true);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                // Include all descendant category IDs
                $categoryIds = $this->getCategoryIds($category);
                $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
            }
        }

        $products = $query->paginate(20);

        return ProductListResource::collection($products);
    }

    public function show(string $slug): ProductResource
    {
        $product = Product::with(['images', 'stock', 'categories'])
            ->where('slug', $slug)
            ->where('active', true)
            ->firstOrFail();

        return new ProductResource($product);
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
