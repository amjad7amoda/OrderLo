<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    //TODO.. update avatar
    public function update(Request $request)
    {
        $user = $request->user();
        $valedatedData = $request->validate([
            "name"=> "sometimes",
            "password"=> "sometimes",
            "phone_number"=> "sometimes",
            "location"=> "sometimes",
        ]);
        $user->update($valedatedData);
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->user()->delete();
        return response()->json(["message"=> "deleted"]);
    }
}
