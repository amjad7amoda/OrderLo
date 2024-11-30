<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $payments = $user->payments;
        return response()->json($payments);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        // check if payment methode exists
        $existingPayment = Payment::where('user_id', $request->user()->id)
        ->where('payment_method', $request->payment_method)
        ->first();


        // error if payment methode exists
        if ($existingPayment) {
            return response()->json([
                "message" => "Payment method already exists"],409);
        }

        //validate data
        $validatedData = $request->validate([
            'payment_method' => 'required|string',
            'card_number' => 'required|string|min:10|max:20',
        ]);

        //add payment method
        $user->payments()->create([
            'payment_method' =>$request->payment_method,
            'card_number' =>$request->card_number
        ]);
        return response()->json(["message"=> "Payment method added successfully"]);

    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $payment)
    {
        $payment = Payment::where('id', $payment)->first();
        if(!$payment)
            return response()->json(['error' => 'This payment method is not exist'],404);

        $request->validate([
            'card_number' => 'required|min:10|max:20'
        ]);

        $payment->update(['card_number' => $request->card_number]);

        return response()->json(['payment' => $payment]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $payment)
    {
        $payment = Payment::where('id',$payment)->first();
        if(!$payment)
            return response()->json(['error' => 'This payment method is not exist'],404 );

        $payment->delete();
        return response()->json(["message"=> "The payment method has been deleted."]);
    }
}
