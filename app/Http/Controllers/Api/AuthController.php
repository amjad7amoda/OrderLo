<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'first_name'   => 'required|string|max:15',
            'last_name'    => "required|string|max:15",
            "phone_number" => 'required|unique:users|min:10|max:10',
            'password'     => 'required',
            'role'         => ['required', Rule::in(User::$roles)],
        ]);
        $validatedData['password'] = bcrypt($validatedData['password']);

        $user = User::create([...$validatedData, 'avatar' => 'gallery/defaultAvatar.png']);
        $user->cart()->create();
        $token = $user->createToken('login-token');

        return response()->json([
            'role' => $user->role,
            'token' => $token
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'password'     => 'required'
        ]);

        $user = User::Where('phone_number', $request->phone_number)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'The credentials are incorrect'
            ], 404);
        }

        $token = $user->createToken('api-token');

        return response()->json([
            'role' => $user->role,
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logged out successfully.'
        ], 200);
    }
}
