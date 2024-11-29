<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Routing\Controller;
use App\Models\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(int $product)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists']);
        }

        $images = $product->images->map(function ($image) {
            $image->path = asset($image->path);
            return $image; // إنشاء الرابط الكامل
        });

        return response()->json(['images' => $images]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, int $product)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists']);
        }

        $validatedData = $request->validate([
            'path'   => 'required|array',
            'path.*' => 'file|mimes:jpg,jpeg,png'
        ]);

        $images = $request->file('path.*');
        $imagesPath = [];
        $folderName = "gallery/products/product-$product->id";
        $imagesNumber = $product->images()->count();
        foreach ($images as  $image) {
            $imagesNumber++;
            $imageName = "image-$product->id-". $imagesNumber . '.png';
            $imagePath = $image->storeAs($folderName, $imageName, 'public');
            $imagesPath[] = $imagePath;
        }

        foreach($imagesPath as $path){
            $product->images()->create([
                'path' => $path
            ]);
        }

        return response()->json(['message' => 'Images has been added successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $product, int $image)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists']);
        }
        $image = Image::where('id', $image)->first();
        if (!$image) {
            return response()->json(['error' => 'This image is not exists']);
        }

        $image->path = asset('storage/'.$image->path);
        return response()->json(['image' => $image]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Image $image)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Image $image)
    {
        //
    }
}
