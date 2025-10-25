<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Catalog;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Product management APIs
 *
 * @OA\Tag(
 *   name="Product Management",
 *   description="Product management APIs for admin"
 * )
 */
class ProductController extends Controller
{
    // /**
    //  * @OA\Get(
    //  *   path="/api/admin/products",
    //  *   summary="List products for admin",
    //  *   tags={"Product Management"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
    //  *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
    //  *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
    //  *   @OA\Parameter(name="catalog_id", in="query", @OA\Schema(type="integer")),
    //  *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
    //  *   @OA\Response(response=200, description="OK")
    //  * )
    //  */
    public function index(Request $request)
    {
        $query = Product::with(['catalog:id,name', 'variants' => function($query) {
            $query->select('id', 'product_id', 'selling_price', 'stock_quantity');
        }]);

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Catalog filter
        if ($catalogId = $request->get('catalog_id')) {
            $query->where('catalog_id', $catalogId);
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->get('per_page', 15);
        $products = $query->orderByDesc('created_at')->paginate($perPage);

        // Transform the data
        $products->getCollection()->transform(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'catalog' => $product->catalog ? [
                    'id' => $product->catalog->id,
                    'name' => $product->catalog->name,
                ] : null,
                'product_type' => $product->product_type,
                'status' => $product->status,
                'featured' => $product->featured,
                'shippable' => $product->shippable,
                'variants_count' => $product->variants->count(),
                'min_price' => $product->variants->min('selling_price'),
                'max_price' => $product->variants->max('selling_price'),
                'total_stock' => $product->variants->sum('stock_quantity'),
                'created_at' => $product->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'products' => $products
        ]);
    }

    // /**
    //  * @OA\Post(
    //  *   path="/api/admin/products",
    //  *   summary="Create new product",
    //  *   tags={"Product Management"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\RequestBody(
    //  *     required=true,
    //  *     @OA\JsonContent(
    //  *       required={"name", "catalog_id", "category_id"},
    //  *       @OA\Property(property="name", type="string", maxLength=255),
    //  *       @OA\Property(property="short_description", type="string", maxLength=160),
    //  *       @OA\Property(property="description", type="string"),
    //  *       @OA\Property(property="catalog_id", type="integer"),
    //  *       @OA\Property(property="category_id", type="integer"),
    //  *       @OA\Property(property="product_type", type="string", enum={"regular", "variant", "addon"}),
    //  *       @OA\Property(property="featured", type="boolean"),
    //  *       @OA\Property(property="shippable", type="boolean"),
    //  *       @OA\Property(property="status", type="string", enum={"draft", "published", "archived"})
    //  *     )
    //  *   ),
    //  *   @OA\Response(response=201, description="Created"),
    //  *   @OA\Response(response=422, description="Validation error")
    //  * )
    //  */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:160',
            'description' => 'nullable|string',
            'catalog_id' => 'nullable|integer|exists:catalogs,id',
            'category_id' => 'required|integer|exists:categories,id',
            'product_type' => 'required|in:regular,variant,addon',
            'featured' => 'boolean',
            'shippable' => 'boolean',
            'status' => 'in:draft,published,archived',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'sort_order' => 'integer',
        ]);

        // If catalog_id is not provided, get it from E-commerce catalog
        if (!isset($validated['catalog_id'])) {
            $ecommerceCatalog = Catalog::where('name', 'E-commerce')->first();
            $validated['catalog_id'] = $ecommerceCatalog ? $ecommerceCatalog->id : 1; // Default to 1 if not found
        }

        $product = Product::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully.',
            'product' => $product->load('catalog:id,name')
        ], 201);
    }

    // /**
    //  * @OA\Get(
    //  *   path="/api/admin/products/{id}",
    //  *   summary="Get product details for admin",
    //  *   tags={"Product Management"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *   @OA\Response(response=200, description="OK"),
    //  *   @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    public function show(Product $product)
    {
        $product->load([
            'catalog:id,name',
            'variants.mediaAssets',
            'generalImages',
            'categories',
            'tags'
        ]);

        return response()->json([
            'status' => 'success',
            'product' => $product
        ]);
    }

    // /**
    //  * @OA\Put(
    //  *   path="/api/admin/products/{id}",
    //  *   summary="Update product",
    //  *   tags={"Product Management"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *   @OA\RequestBody(
    //  *     required=true,
    //  *     @OA\JsonContent(
    //  *       @OA\Property(property="name", type="string", maxLength=255),
    //  *       @OA\Property(property="short_description", type="string", maxLength=160),
    //  *       @OA\Property(property="description", type="string"),
    //  *       @OA\Property(property="catalog_id", type="integer"),
    //  *       @OA\Property(property="category_id", type="integer"),
    //  *       @OA\Property(property="product_type", type="string", enum={"regular", "variant", "addon"}),
    //  *       @OA\Property(property="featured", type="boolean"),
    //  *       @OA\Property(property="shippable", type="boolean"),
    //  *       @OA\Property(property="status", type="string", enum={"draft", "published", "archived"})
    //  *     )
    //  *   ),
    //  *   @OA\Response(response=200, description="OK"),
    //  *   @OA\Response(response=422, description="Validation error"),
    //  *   @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'short_description' => 'nullable|string|max:160',
            'description' => 'nullable|string',
            'catalog_id' => 'nullable|integer|exists:catalogs,id',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'product_type' => 'sometimes|required|in:regular,variant,addon',
            'featured' => 'boolean',
            'shippable' => 'boolean',
            'status' => 'in:draft,published,archived',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'sort_order' => 'integer',
        ]);

        // If catalog_id is provided as null or not provided, maintain E-commerce catalog
        if (isset($validated['catalog_id']) && $validated['catalog_id'] === null) {
            $ecommerceCatalog = Catalog::where('name', 'E-commerce')->first();
            $validated['catalog_id'] = $ecommerceCatalog ? $ecommerceCatalog->id : 1; // Default to 1 if not found
        }

        $product->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully.',
            'product' => $product->load('catalog:id,name')
        ]);
    }

    // /**
    //  * @OA\Delete(
    //  *   path="/api/admin/products/{id}",
    //  *   summary="Delete product (soft delete)",
    //  *   tags={"Product Management"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *   @OA\Response(response=200, description="OK"),
    //  *   @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully.'
        ]);
    }

    // /**
    //  * @OA\Get(
    //  *   path="/api/admin/products/create-data",
    //  *   summary="Get data needed for product creation",
    //  *   tags={"Product Management"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Response(response=200, description="OK")
    //  * )
    //  */
    public function createData()
    {
        $catalogs = Catalog::select('id', 'name')->orderBy('name')->get();
        $categories = Category::select('id', 'name', 'parent_id')->active()->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'catalogs' => $catalogs,
                'categories' => $categories,
                'product_types' => [
                    ['key' => 'regular', 'label' => 'Regular Product'],
                    ['key' => 'variant', 'label' => 'Product with Variants'],
                    ['key' => 'addon', 'label' => 'Add-on Product'],
                ],
                'status_options' => [
                    ['key' => 'draft', 'label' => 'Draft'],
                    ['key' => 'published', 'label' => 'Published'],
                    ['key' => 'archived', 'label' => 'Archived'],
                ],
            ]
        ]);
    }
}