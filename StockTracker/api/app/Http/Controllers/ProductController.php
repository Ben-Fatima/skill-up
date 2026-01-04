<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Allowed fields for product operations.
     */
    private const FIELDS = ['sku', 'name', 'unit_cost_cents', 'min_stock'];

    /**
     * Display a listing of products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $products = Product::searchAndPaginate(request()->query());

        return response()->json([
            'message' => 'List of products',
            'data'    => $products,
        ], 200);
    }


    /**
     * Creates and stores new product.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $data = request()->only(self::FIELDS);
        $validated = $this->validateProduct($data);
        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Get a product by ID.
     * @return \Illuminate\Http\JsonResponse
     */

    public function show()
    {
        $id = (int)request()->route('id');
        $product = Product::findOrFail($id);

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => $product
        ], 200);
    }

    /**
     * Updates a product by ID.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update()
    {
        $id = (int)request()->route('id');
        $data = request()->only(self::FIELDS);

        if(!request()->hasAny(self::FIELDS)){
            return response()->json([
                'message' => 'No data provided for update'
            ], 400);
        }


        $product = Product::findOrFail($id);
        $validated = $this->validateProduct($data, $id, true);
        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ], 200);
    }

    /**
     * Deletes a product by ID.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        $id = (int)request()->route('id');
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }

    /**
     * Validates product data.
     * @param array $data
     * @return array
     */
    private function validateProduct(array $data, ?int $id = null, bool $partial = false)
    {
        $presenceRule = $partial ? ['sometimes', 'required'] : ['required'];
        $skuUniqueRule = $id === null
            ? 'unique:products,sku'
            : Rule::unique('products', 'sku')->ignore($id);

        return validator($data, [
            'sku' => array_merge($presenceRule, [$skuUniqueRule],['string']),
            'name' => array_merge($presenceRule, ['string']),
            'unit_cost_cents' => array_merge($presenceRule, ['integer', 'min:0']),
            'min_stock' => array_merge($presenceRule, ['integer', 'min:0']),
        ])->validate();
    }
}
