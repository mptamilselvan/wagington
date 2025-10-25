<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use Illuminate\Http\Request;

/**
 * Catalog management APIs
 *
 * @OA\Tag(
 *   name="Catalog",
 *   description="Catalog management APIs"
 * )
 */
class CatalogController extends Controller
{
    // /**
    //  * @OA\Get(
    //  *   path="/api/admin/catalogs",
    //  *   summary="List all catalogs",
    //  *   tags={"Catalog"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Response(response=200, description="OK")
    //  * )
    //  */
    public function index()
    {
        $catalogs = Catalog::with(['products' => function($query) {
            $query->select('id', 'catalog_id', 'name', 'status');
        }])->get();

        return response()->json([
            'status' => 'success',
            'catalogs' => $catalogs->map(function($catalog) {
                return [
                    'id' => $catalog->id,
                    'name' => $catalog->name,
                    'description' => $catalog->description,
                    'products_count' => $catalog->getProductsCount(),
                    'published_products_count' => $catalog->getPublishedProductsCount(),
                    'created_at' => $catalog->created_at,
                    'updated_at' => $catalog->updated_at,
                ];
            })
        ]);
    }

    // /**
    //  * @OA\Post(
    //  *   path="/api/admin/catalogs",
    //  *   summary="Create a new catalog",
    //  *   tags={"Catalog"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\RequestBody(
    //  *     required=true,
    //  *     @OA\JsonContent(
    //  *       required={"name"},
    //  *       @OA\Property(property="name", type="string", maxLength=255),
    //  *       @OA\Property(property="description", type="string", maxLength=500)
    //  *     )
    //  *   ),
    //  *   @OA\Response(response=201, description="Created"),
    //  *   @OA\Response(response=422, description="Validation error")
    //  * )
    //  */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:catalogs,name',
            'description' => 'nullable|string|max:500',
        ]);

        $catalog = Catalog::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Catalog created successfully.',
            'catalog' => $catalog
        ], 201);
    }

    // /**
    //  * @OA\Get(
    //  *   path="/api/admin/catalogs/{id}",
    //  *   summary="Get catalog details",
    //  *   tags={"Catalog"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *   @OA\Response(response=200, description="OK"),
    //  *   @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    public function show(Catalog $catalog)
    {
        $catalog->load(['products' => function($query) {
            $query->select('id', 'catalog_id', 'name', 'slug', 'status', 'product_type', 'featured', 'shippable')
                  ->with('variants:id,product_id,selling_price,stock_quantity')
                  ->orderBy('created_at', 'desc');
        }]);

        return response()->json([
            'status' => 'success',
            'catalog' => [
                'id' => $catalog->id,
                'name' => $catalog->name,
                'description' => $catalog->description,
                'products_count' => $catalog->getProductsCount(),
                'published_products_count' => $catalog->getPublishedProductsCount(),
                'products' => $catalog->products->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'status' => $product->status,
                        'product_type' => $product->product_type,
                        'featured' => $product->featured,
                        'shippable' => $product->shippable,
                        'min_price' => $product->variants->min('selling_price'),
                        'max_price' => $product->variants->max('selling_price'),
                        'total_stock' => $product->variants->sum('stock_quantity'),
                    ];
                }),
                'created_at' => $catalog->created_at,
                'updated_at' => $catalog->updated_at,
            ]
        ]);
    }

    // /**
    //  * @OA\Put(
    //  *   path="/api/admin/catalogs/{id}",
    //  *   summary="Update catalog",
    //  *   tags={"Catalog"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *   @OA\RequestBody(
    //  *     required=true,
    //  *     @OA\JsonContent(
    //  *       @OA\Property(property="name", type="string", maxLength=255),
    //  *       @OA\Property(property="description", type="string", maxLength=500)
    //  *     )
    //  *   ),
    //  *   @OA\Response(response=200, description="OK"),
    //  *   @OA\Response(response=422, description="Validation error"),
    //  *   @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    public function update(Request $request, Catalog $catalog)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:catalogs,name,' . $catalog->id,
            'description' => 'nullable|string|max:500',
        ]);

        $catalog->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Catalog updated successfully.',
            'catalog' => $catalog
        ]);
    }

    // /**
    //  * @OA\Delete(
    //  *   path="/api/admin/catalogs/{id}",
    //  *   summary="Delete catalog",
    //  *   tags={"Catalog"},
    //  *   security={{"bearerAuth": {}}},
    //  *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *   @OA\Response(response=200, description="OK"),
    //  *   @OA\Response(response=404, description="Not Found"),
    //  *   @OA\Response(response=422, description="Cannot delete - has products")
    //  * )
    //  */
    public function destroy(Catalog $catalog)
    {
        // Check if catalog has products
        if ($catalog->products()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete catalog that contains products. Please move or delete products first.'
            ], 422);
        }

        $catalog->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Catalog deleted successfully.'
        ]);
    }

    // /**
    //  * @OA\Get(
    //  *   path="/api/catalogs",
    //  *   summary="Get public catalog list (for frontend)",
    //  *   tags={"E-commerce"},
    //  *   @OA\Response(
    //  *     response=200,
    //  *     description="Catalogs retrieved successfully",
    //  *     @OA\JsonContent(
    //  *       @OA\Property(property="status", type="string", example="success"),
    //  *       @OA\Property(property="message", type="string", example="Catalogs retrieved successfully."),
    //  *       @OA\Property(property="catalogs", type="array", @OA\Items(type="object"))
    //  *     )
    //  *   )
    //  * )
    //  */
    public function publicIndex()
    {
        $catalogs = Catalog::whereHas('products', function($query) {
            $query->where('status', 'published');
        })->get(['id', 'name', 'description']);

        return response()->json([
            'status' => 'success',
            'message' => 'Catalogs retrieved successfully.',
            'catalogs' => $catalogs
        ]);
    }
}