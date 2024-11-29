<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        $validatedData = $request->validate([
            'name'        => 'require|string|max:128',
            'description' => 'required|string|max:255',
            'price'       => 'required|min:0',
            'stock'       => 'required|min:0',
        ]);

        $store = Store::where('id', $store)->first();
        $product = $store->products()->create($validatedData);

        return response()->json([
           'message' => 'Product has been created Successfully.',
           'product'=> $product
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
