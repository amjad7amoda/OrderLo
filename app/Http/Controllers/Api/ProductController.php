<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    public function index()
    {
        
        $filters = request()->only(
            'search',
            'min_price',
            'max_price'
        );

        $products = Product::filter($filters)->with('images')->get();
        
        return response()->json(['products' => $products
        ->map(function ($product) {
            return array_merge(
                $product->toArray(),
                [
                    'images' => $product->images->map(function ($image) {
                        return asset('storage/'.$image->path);
                    })->toArray()
                ]
            );
        })
    ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'        => 'required|string|max:128',
            'description' => 'required|string|max:255',
            'price'       => 'required|min:1',
            'stock'       => 'required|min:1',
            'store_id'    => 'required|exists:stores,id'
        ]);

        $store = Store::where('id', $request->store_id)->first();
        $product = $store->products()->create($validatedData);

        return response()->json([
            'message' => 'Product has been created Successfully.',
            'product' => $product
        ], 200);
    }

    public function show(int $product)
    {
        $product = Product::where('id', $product)->withImages()->first();
        if (!$product) {
            return response()->json(['error' => 'This product does not exist'], 404);
        }

        $product = array_merge($product->toArray(), [
            'images' => $product->images->pluck('path')->map(function ($path) {
                return asset('storage/'.$path);
            })->toArray()
        ]);

        return response()->json(['product' => $product], 200);
    }

    public function update(Request $request,  int $product)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists'], 404);
        }

        $validatedData = $request->validate([
            'name'        => 'sometimes|string|max:128',
            'description' => 'sometimes|string|max:255',
            'price'       => 'sometimes|min:0',
            'stock'       => 'sometimes|min:0',
        ]);


        $product->update($validatedData);
        $product->save();

        return response()->json([
            'message' => 'Product has been updated successfully.',
            'product' => $product
        ], 200);
    }

    public function destroy( int $product)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists'], 404);
        }

        Storage::disk('public')->deleteDirectory("gallery/products/product-$product->id");
        $product->delete();

        return response()->json(['message' => 'The product has been deleted successfully.'], 200);
    }
}
