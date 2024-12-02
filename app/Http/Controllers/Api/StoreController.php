<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Traits\CanLoadRelationships;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreController extends Controller
{

    public function index()
    {
        $stores = Store::with('products')->get()->map(function ($store) {
            $store->banner = asset('storage/'.$store->banner);
            return $store;
        });
        return response()->json(['stores' => $stores], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|unique:stores|string|max:155',
            'banner' => 'required|image|mimes:jpeg,png,jpg'
        ]);

        $store = Store::create([
            'name'   => $request->name,
            'banner' => '' //Create an empty banner for set it later using store-{$store->id}
        ]);

        $filename = "store-{$store->id}.png";
        $bannerPath = $request->file('banner')->storeAs('gallery/stores', $filename, 'public');
        $store->update(['banner' => $bannerPath]);
        $store->save();

        return response()->json([
            'message' => 'Store has been created Successfully.',
            'store'   => $store
        ], 200);
    }

    public function show(int $store)
    {
        $store = Store::where('id', $store)->first();
        if (!$store) {
            return response()->json(['error' => 'This store is not exists'], 404);
        }

        $store->banner = asset('storage/'.$store->banner);
        return response()->json([
            'store' => $store->load('products')
        ], 200);
    }


    public function update(Request $request, int $store)
    {
        $store = Store::where('id', $store)->first();
        if (!$store) {
            return response()->json(['error' => 'This store is not exists'], 404);
        }

        $request->validate([
            'name'   => 'sometimes|unique:stores|string|max:155',
            'banner' => 'sometimes|image|mimes:jpeg,png,jpg'
        ]);


        if ($request->hasFile('banner')) {
            Storage::disk('gallery')->delete($store->banner);
            $filename = "store-{$store->id}.png";
            $bannerPath = $request->file('banner')->storeAs('gallery/stores', $filename, 'public');
            $store->banner = $bannerPath;
        }

        if ($request->name) {
            $store->name = $request->name;
        }

        $store->save();

        return response()->json(['store' => $store], 200);
    }

    public function destroy(int $store)
    {
        $store = Store::where('id', $store)->first();
        if (!$store) {
            return response()->json(['error' => 'This store is not exists'], 404);
        }

        Storage::disk('public')->delete($store->banner);
        $store->delete();

        return response()->json(['message' => 'The store has been deleted successfully'], 200);
    }
}
