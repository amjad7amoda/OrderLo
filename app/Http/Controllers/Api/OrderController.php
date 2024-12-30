<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use Illuminate\Routing\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Notifications\OrderStatusChanged;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:administrator,user');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $orders = $user->orders()
            ->whereIn('status', ['pending', 'delivering'])
            ->with('products')->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders found for the user'], 404);
        }

        $orders->transform(function ($order) {
            $order->products->each(function ($product) {
                return $product->pivot->price = (float)$product->pivot->price;
            });
            $order->products->transform(function ($product) {
                return
                    array_merge(
                        Product::where('id', $product->id)->productImages()->first(),
                        ['pivot' => $product->pivot]
                    );
            });
            return $order;
        });
        return response()->json(['orders' => $orders], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        //Payments Validation
        $paymentIds = $user->payments()->pluck('id')->toArray();
        if (!$paymentIds) {
            return response()->json(['error' => 'Please add a payment method'], 403);
        }
        $request->validate([
            'payment_method' => ['required']
        ]);
        if (! in_array($request->payment_method, $paymentIds))
            return response()->json(['message' => "You don't have this payment method"], 403);
        $paymentMethod = Payment::where('id', $request->payment_method)->first();

        $cart = $user->cart;
        $cartProducts = $cart->products;
        if ($cartProducts->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        foreach ($cartProducts as $cartProduct) {
            if ($cartProduct->stock < $cartProduct->pivot->quantity) {
                return response()->json([
                    'error' => "Insufficient stock for product: {$cartProduct->name}. Available stock: {$cartProduct->stock}.",
                ], 400);
            }
        }

        $totalPrice = $cartProducts->sum(function ($cartProduct) {
            return $cartProduct->pivot->quantity * $cartProduct->price;
        });

        $order = Order::create([
            'user_id' => $user->id,
            'status' => "pending",
            'payment_method' => $paymentMethod->payment_method,
            'total_price' => $totalPrice,
        ]);

        foreach ($cartProducts as $cartProduct) {
            $order->products()->attach($cartProduct->id, [
                'quantity' => $cartProduct->pivot->quantity,
                'price'    => $cartProduct->pivot->price,
            ]);

            $cartProduct->decrement('stock', $cartProduct->pivot->quantity);
        }

        $cart->products()->detach();

        return response()->json([
            'message' => 'Order Created Successfully',
            'order'   => $order
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = $user->orders()->where('id', $id)->first();
        if (!$order) {
            return response()->json(['error' => 'Order not found or unauthorized'], 404);
        }

        $order->products->each(function ($product) {
            return $product->pivot->price = (float)$product->pivot->price;
        });
        $order->products->transform(function ($product) {
            return array_merge(
                Product::where('id', $product->id)->productImages()->first(),
                ['pivot' => $product->pivot]
            );
        });
        return response()->json(['order' => $order], 200);
    }

    public function update(Request $request, $orderId, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $user = $request->user();

        $order = $user->orders()->where('id', $orderId)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or unauthorized'], 404);
        }

        $orderProduct = $order->products()->where('product_id', $productId)->first();

        $newQuantity = $request->quantity;

        if ($orderProduct) {
            $currentQuantity = $orderProduct->pivot->quantity;

            if ($newQuantity > 0) {
                $quantityDifference = $newQuantity - $currentQuantity;

                if ($quantityDifference > 0 && $orderProduct->stock < $quantityDifference) {
                    return response()->json([
                        'error' => "Insufficient stock for product: {$orderProduct->name}. Available stock: {$orderProduct->stock}.",
                    ], 400);
                }

                if ($quantityDifference > 0) {
                    $orderProduct->decrement('stock', $quantityDifference);
                } elseif ($quantityDifference < 0) {
                    $orderProduct->increment('stock', abs($quantityDifference));
                }

                $order->products()->updateExistingPivot($productId, ['quantity' => $newQuantity]);
            } else {
                $orderProduct->increment('stock', $currentQuantity);
                $order->products()->detach($productId);
            }
        } else {
            if ($newQuantity > 0) {
                $product = Product::find($productId);

                if (!$product) {
                    return response()->json(['error' => 'Product not found'], 404);
                }

                if ($product->stock < $newQuantity) {
                    return response()->json([
                        'error' => "Insufficient stock for product: {$product->name}. Available stock: {$product->stock}.",
                    ], 400);
                }

                $order->products()->attach($productId, [
                    'quantity' => $newQuantity,
                    'price'    => $product->price,
                ]);

                $product->decrement('stock', $newQuantity);
            } else {
                return response()->json(['error' => 'Cannot add a product with quantity 0'], 400);
            }
        }

        $totalPrice = $order->products->sum(function ($product) {
            return $product->pivot->quantity * $product->pivot->price;
        });

        if ($order->products->isEmpty()) {
            $order->update(['status' => 'cancelled']);

            return response()->json(['message' => 'All products were removed. Order has been cancelled.'], 200);
        } else {
            $order->update(['total_price' => $totalPrice]);

            return response()->json(['message' => 'Order updated successfully', 'order' => $order], 200);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('id', $id)->where('user_id', $user->id)->first();
        if (!$order) {
            return response()->json(['error' => 'Order not found or unauthorized'], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json(['error' => 'Only pending orders can be cancelled'], 400);
        }

        foreach ($order->products as $product) {
            $quantity = $product->pivot->quantity;
            $product->increment('stock', $quantity);
        }

        $products = $order->products()->detach();

        $order->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Order cancelled successfully, and stock has been restored'], 200);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $history = $user->orders()
            ->whereIn('status', ['arrived', 'cancelled'])
            ->with('products')->get();

        if ($history->isEmpty()) {
            return response()->json(['message' => 'History is empty'], 404);
        }
        $history->transform(function ($order) {
            $order->products->each(function ($product) {
                return $product->pivot->price = (float)$product->pivot->price;
            });
            $order->products->transform(function ($product) {
                return array_merge(
                    Product::where('id', $product->id)->productImages()->first(),
                    ['pivot' => $product->pivot]
                );
            });
            return $order;
        });
        return response()->json(['history' => $history], 200);
    }

    public function updateStatus(Request $request, int $order)
    {
        $user = $request->user();
        $request->validate([
            'status' => 'required|string|in:pending,delivering,arrived,cancelled',
        ]);
        $newStatus = $request->status;
        $order = $user->orders()->find($order);

        if (!$order) {
            return response()->json([
                'error'
                => 'Order not found or does not belong to the user'
            ], 404);
        }
        $order->status = $request->status;
        $order->save();
        $order->user->notify(new OrderStatusChanged($order));
        return response()->json(
            [
                'message' => 'Order status updated successfully',
            ],
            201
        );
    }
}
