<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    public function show(Request $request)
    {
        $user = $request->user();
        $user->avatar = url('storage/'.$user->avatar);
        return response()->json(['user' => $user], 200);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $valedatedData = $request->validate([
            "name"         => "sometimes|string|max:26",
            "password"     => "sometimes|min:8|max:20",
            "phone_number" => "sometimes|unique",
            "location"     => "sometimes|string",
            "avatar"       => "sometimes|image|mimes:png,jpg,jpeg"
        ]);

        $user->update($valedatedData);
        if ($request->hasFile('avatar')) {
            $avatarName = "user-{$user->id}.png";
            if (Storage::disk('public')->exists('gallery/users/'.$avatarName)) {
                Storage::disk('public')->delete('gallery/users/'.$avatarName);
            }
            $avatarPath = $request->file('avatar')->storeAS("gallery/users", $avatarName, 'public');
            $user->update(['avatar' => $avatarPath]);
        }

        return response()->json(['user' => $user], 200);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if($user->avatar != "gallery/defaultAvatar.png")
            Storage::disk('public')->delete($user->avatar);
        $user->delete();
        return response()->json(["message"=> "deleted"], 200);
    }
}
