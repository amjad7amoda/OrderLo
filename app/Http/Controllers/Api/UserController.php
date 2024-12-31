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
        $user->avatar = asset('storage/'.$user->avatar);
        return response()->json(['user' => $user], 200);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $valedatedData = $request->validate([
            "first_name"   => "sometimes|string|max:15",
            "last_name"    => "sometimes|string|max:15",
            "password"     => "sometimes|min:8|max:20",
            "phone_number" => "sometimes|unique:users|max:10|min:10",
            "location"     => "sometimes|string",
            "avatar"       => "sometimes|image|mimes:png,jpg,jpeg"
        ]);

        $user->update($valedatedData);
        if ($request->hasFile('avatar')) {
            try{

            $avatarName = "user-{$user->id}.png";
            if (Storage::disk('public')->exists('gallery/users/'.$avatarName)) {
                Storage::disk('public')->delete('gallery/users/'.$avatarName);
            }
            $avatarPath = $request->file('avatar')->storeAS("gallery/users", $avatarName, 'public');
            
            }catch(\Exception $e){
                return response()->json([
                    "message" => "An error occurred while updating the user",
                    "error" => $e->getMessage()
                ], 500);
            }
            $user->update(['avatar' => $avatarPath]);
        }

        return response()->json(['user' => $user], 200);
    }

    public function destroy(Request $request)
{
    $user = $request->user();

    try {
        //make sure if the photo deleted from folder
        if ($user->avatar != "gallery/defaultAvatar.png") {
            $fileDeleted = Storage::disk('public')->delete($user->avatar);
            if (!$fileDeleted) {
                throw new \Exception("Failed to delete the avatar file");
            }
        }
        //Delete the path from database
        $user->delete();
        return response()->json(["message" => "User has been deleted successfully"], 200);

    } catch (\Exception $e) {
        return response()->json([
            "message" => "An error occurred while deleting the user",
            "error" => $e->getMessage()
        ], 500);
    }
}

}
