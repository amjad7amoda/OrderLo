<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    public function index(int $store)
    {
        // Check if the store exists
        $store = Store::with('products.images')->find($store);
        if (!$store) {
            return response()->json(['error' => 'This store does not exist'], 404);
        }

        $products = $store->products->map(function ($product) {
            return array_merge(
                $product->toArray(),
                [
                    'images' => $product->images->map(function ($image) {
                        return asset($image->path);
                    })->toArray()
                ]
            );
        });
        return response()->json(['products' => $products]);
    }

    public function store(Request $request, int $store)
    {
        $store = Store::where('id', $store)->first();
        if (!$store) {
            return response()->json(['error' => 'This store is not exists']);
        }

        $validatedData = $request->validate([
            'name'        => 'required|string|max:128',
            'description' => 'required|string|max:255',
            'price'       => 'required|min:0',
            'stock'       => 'required|min:0',
        ]);

        $product = $store->products()->create($validatedData);

        return response()->json([
            'message' => 'Product has been created Successfully.',
            'product' => $product
        ]);
    }

    public function show(int $store, int $product)
    {
        $store = Store::where('id', $store)->first();
        if (!$store) {
            return response()->json(['error' => 'This store is not exists']);
        }

        $product = Product::where('id', $product)->withImages()->first();
        if (!$product) {
            return response()->json(['error' => 'This product does not exist']);
        }

        $product = array_merge($product->toArray(), [
            'images' => $product->images->pluck('path')->map(function ($path) {
                return asset('storage/'.$path);
            })->toArray()
        ]);

        return response()->json(['product' => $product]);
    }

    public function update(Request $request, int $store, int $product)
    {
        $store = Store::where('id', $store)->first();
        if (!$store) {
            return response()->json(['error' => 'This store is not exists']);
        }

        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists']);
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
        ]);
    }

    public function destroy(int $store, int $product)
    {
        $store = Store::where('id', $store)->first();
        if (!$store) {
            return response()->json(['error' => 'This store is not exists']);
        }

        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists']);
        }

        Storage::disk('public')->deleteDirectory("gallery/products/product-$product->id");
        $product->delete();

        return response()->json(['message' => 'The product has been deleted successfully.']);
    }
}
