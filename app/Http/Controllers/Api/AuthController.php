<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name'         => 'required',
            "phone_number" => 'required|unique:users',
            'password'     => 'required',
            'role'         => ['required', Rule::in(User::$roles)],
        ]);
        $validatedData['password'] = bcrypt($validatedData['password']);

        $user = User::create($validatedData);
        $token = $user->createToken('login-token');

        return response()->json([
            'token' => $token
        ]);
    }

    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'phone_number' => 'required',
            'password' => 'required'
        ]);


        $user = User::Where('phone_number', $request->phone_number)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'The credentials are incorrect'
            ]);
        }

        $token = $user->createToken('api-token');

        return response()->json([
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}
