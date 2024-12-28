<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\OrderStatusChanged;
class DriverController extends Controller
{
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

        $order = Order::where('id', $orderId)
            ->where('status', 'pending')
            ->whereNull('driver_id')
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or already taken'], 404);
        }

        $order->driver_id = $user->id;
        $order->status = 'delivering';
        $order->save();
        $order->user->notify(new OrderStatusChanged($order));
        // DON'T USE THIS WAY, IT WON'T WORK, USE save() INSTEAD
        // $order->update([
        //     'driver_id' => $user->id,
        //     'status'    => 'delivering'
        // ]);

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
            return response()->json(['error' => 'Order not found or unauthorized'], 404);
        }

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

        return response()->json([
            'message' => 'Order marked as arrived successfully.',
            'order' => $order
        ], 200);
    }
}
