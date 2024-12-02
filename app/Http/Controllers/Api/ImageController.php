<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Routing\Controller;
use App\Models\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{

    public function index(int $product)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists'], 404);
        }

        $images = $product->images->map(function ($image) {
            $image->path = asset($image->path);
            return $image;
        });

        return response()->json(['images' => $images], 200);
    }

    public function store(Request $request, int $product)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists'], 404);
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

        return response()->json(['message' => 'Images has been added successfully'], 200);
    }

    public function show(int $product, int $image)
    {
        $product = Product::where('id', $product)->first();
        if (!$product) {
            return response()->json(['error' => 'This product is not exists'], 404);
        }
        $image = Image::where('id', $image)->first();
        if (!$image) {
            return response()->json(['error' => 'This image is not exists'], 404);
        }

        $image->path = asset('storage/'.$image->path);
        return response()->json(['image' => $image], 200);
    }
}
