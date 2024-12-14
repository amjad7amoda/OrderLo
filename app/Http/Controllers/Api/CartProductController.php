<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CartProductController extends Controller
{

    public function index()
    {
        $cart = auth()->user()->cart;
        return $cart->load('products');
    }

    public function store(Request $request, int $product)
    {
        $user = $request->user();

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $user->cart;

        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product does not exist.'], 404);
        }

        $existingProduct = $cart->products()->where('product_id', $product->id)->first();
        $newQuantity = $existingProduct
            ? $existingProduct->pivot->quantity + $request->quantity
            : $request->quantity;

        // Validate stock availability
        if ($newQuantity > $product->stock) {
            return response()->json([
                'error' => 'Insufficient stock available. Maximum available quantity: ' . $product->stock,
            ], 400);
        }

        if ($existingProduct) {
            $cart->products()->updateExistingPivot($product->id, [
                'quantity' => $newQuantity,
                'price'    => $newQuantity * $product->price, // Recalculate total price for this product
            ]);
        } else {
            $cart->products()->attach(
                $product->id,
                ['quantity' => $request->quantity, 'price' => $product->price * $request->quantity]
            );
        }

        return response()->json(['message' => 'The product has been added successfully.'], 200);
    }


    public function update(Request $request, int $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Fetch the product
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product does not exist.'], 404);
        }

        $cart = $request->user()->cart;
        $existingProduct = $cart->products()->where('product_id', $product->id)->first();

        if (!$existingProduct) {
            return response()->json(['error' => 'This product is not in your cart.'], 404);
        }

        // Validate stock availability
        if ($request->quantity > $product->stock) {
            return response()->json([
                'error' => 'Insufficient stock available. Maximum available quantity: ' . $product->stock,
            ], 400);
        }

        // Update the quantity and price in the pivot table
        $cart->products()->updateExistingPivot($product->id, [
            'quantity' => $request->quantity,
            'price'    => $request->quantity * $product->price,
        ]);

        return response()->json(['message' => 'The product has been updated successfully'], 200);
    }


    public function destroy(Request $request, int $product)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists.'], 404);
        }

        $cart = $request->user()->cart;

        $cart->products()->detach($product->id);

        return response()->json(['message' => 'The product has been removed from the cart'], 200);
    }

    public function clear()
    {
        $cart = auth()->user()->cart;
        $cart->products()->detach();
        return response()->json(['message' => 'The cart has been cleared.'], 200);
    }

}
