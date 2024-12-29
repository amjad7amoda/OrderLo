<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:administrator,user');
    }

    public function index(Request $request)
    {
        $payments = $request->user()->payments;
        return response()->json(['payments', $payments], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => ['required', Rule::in(Payment::$payment_methods)],
            'card_number'    => 'required|string|min:10|max:20',
        ]);

        $user = $request->user();
        $existingPayment = Payment::where('user_id', $request->user()->id)
            ->where('payment_method', $request->payment_method)
            ->first();

        if ($existingPayment) {
            return response()->json([
                "message" => "Payment method already exists"
            ], 403);
        }

        $user->payments()->create([
            'payment_method' => $request->payment_method,
            'card_number'    => $request->card_number
        ]);

        return response()->json(["message" => "Payment method added successfully"], 200);
    }

    public function update(Request $request, int $payment)
    {
        $payment = Payment::where('id', $payment)->first();
        if (!$payment) {
            return response()->json(['error' => 'This payment method is not exist'], 404);
        }

        $request->validate([
            'card_number' => 'required|min:10|max:20'
        ]);

        $payment->update(['card_number' => $request->card_number]);

        return response()->json(['payment' => $payment], 200);
    }

    public function destroy(Request $request, int $payment)
    {
        $payment = Payment::where('id', $payment)->first();
        if (!$payment) {
            return response()->json(['error' => 'This payment method is not exist'], 404);
        }

        $payment->delete();
        return response()->json(["message" => "The payment method has been deleted."], 200);
    }
}
