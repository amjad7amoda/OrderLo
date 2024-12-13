<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = $user->orders()->with('products')->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders found for the user'], 404);
        }

        return response()->json(['orders' => $orders], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $paymentMethod = $user->payments()->first();

        $request->validate([
            'payment_method' => 'required',
        ]);

        $cart = Cart::where('user_id', $user->id)->first();
        if (!$cart) {
            return response()->json(['error' => 'Cart not found for user'], 404);
        }

        $cartProducts = $cart->products;
        if ($cartProducts->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        // Total Price
        $totalPrice = $cartProducts->sum(function ($cartProduct) {
            return $cartProduct->pivot->quantity * $cartProduct->price;
        });

        // Create Order
        $order = Order::create([
            'user_id' => $user->id,
            'status' => "pending",
            'payment_method' => $paymentMethod->payment_method,
            'total_price' => $totalPrice,
        ]);

        // Add products to order using pivot
        foreach ($cartProducts as $cartProduct) {
            $order->products()->attach($cartProduct->id, [
                'quantity' => $cartProduct->pivot->quantity,
                'price' => $cartProduct->pivot->price,
            ]);
        }

        $cart->products()->detach();

        return response()->json(
            [
                'message' => 'Order Created Successfully',
                'order' => $order
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or unauthorized'], 404);
        }

        $order->products()->detach();
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully'], 200);
    }
}
