<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\OrderStatusChanged;
class DriverController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:administrator,driver');
    }

    public function getAllDrivers()
    {
        $drivers = User::where('role', 'driver')->get();

        return response()->json(['drivers' => $drivers], 200);
    }


    public function getAllOrders()
    {
        $orders = Order::where('status', 'pending')->whereNull('driver_id')->get();

        return response()->json(['avialable_orders' => $orders], 200);
    }

    public function acceptOrder(Request $request, $orderId)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json(['error' => 'Only drivers can accept orders'], 403);
        }

        $order = Order::where('id', $orderId)->first();
        if(!$order)
            return response()->json(['error' => 'This order is not exists.'], 404);
        if($order->driver_id == $user->id)
            return response()->json(['error' => 'You already take this order'], 403);
        if ($order->status == 'delivering') 
            return response()->json(['error' => 'The order has been already taken'], 404);
        

        $order->driver_id = $user->id;
        $order->status = 'delivering';
        $order->save();
        $order->user->notify(new OrderStatusChanged($order));

        return response()->json(['message' => 'Order accepted successfully', 'order' => $order], 200);
    }

    public function assignedDeliveries(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json(['error' => 'Only drivers can access their deliveries'], 403);
        }

        $assignedOrders = $user->deliveryOrders()
            ->where('status', 'delivering')
            ->with('products')
            ->get();

        if ($assignedOrders->isEmpty()) {
            return response()->json(['message' => 'No active deliveries assigned to you'], 200);
        }
        
        $assignedOrders->each(function($order){
            $order->products->each(function ($product) {
                return $product->pivot->price = (float)$product->pivot->price;
            });
        });
        return response()->json(['assigned_orders' => $assignedOrders], 200);
    }

    public function showOrder(Request $request, $orderId)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json(['error' => 'Only drivers can access this order'], 403);
        }

        $order = $user->deliveryOrders()
            ->where('id', $orderId)
            // ->where('status', 'delivering')
            ->with('products')
            ->first();

        if (!$order) {
            return response()->json(['error' => 'The order is not on your list'], 404);
        }
        $order->products->each(function ($product) {
            return $product->pivot->price = (float)$product->pivot->price;
        });
        return response()->json(['order' => $order], 200);
    }
    public function cancelDelivery(Request $request, $orderId)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json(['error' => 'Only drivers can cancel deliveries'], 403);
        }

        $order = $user->deliveryOrders()
            ->where('id', $orderId)
            ->where('driver_id', $user->id)
            ->where('status', 'delivering')
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or unauthorized'], 404);
        }

        $order->driver_id = null;
        $order->status = 'pending';
        $order->save();
        $order->user->notify(new OrderStatusChanged($order));
        return response()->json(['message' => 'Delivery operation has been canceled, order is now available'], 200);
    }



    public function markAsArrived(Request $request, $orderId)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json(['error' => 'Unauthorized. Only drivers can update orders.'], 403);
        }

        $order = Order::where('id', $orderId)
            ->where('status', 'delivering')
            ->where('driver_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or not eligible for this action.'], 404);
        }

        $order->update(['status' => 'arrived']);
        $order->user->notify(new OrderStatusChanged($order));
        return response()->json([
            'message' => 'Order marked as arrived successfully.',
            'order' => $order
        ], 200);
    }
}
