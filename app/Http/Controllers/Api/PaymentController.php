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
        $payments = Payment::where("user_id", $user->id)->get();
        return response()->json($payments);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
            'card_number' => 'required|string',
        ]);

        //add payment method
        $payment = Payment::create([
            'user_id'=>$request->user()->id,
            'payment_method' =>$request->payment_method,
            'card_number' =>$request->card_number
        ]);
        return response()->json(["message"=> "Payment maethod added successful"],201);
        
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

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Payment $payment)
    {
        $payment->delete();
        return response()->json(["message"=> "deleted"]);
    }
}
