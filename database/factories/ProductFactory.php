<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word, 
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 5, 100), 
            'stock' => $this->faker->numberBetween(1, 100), 
        ];
    }

    public function configure()
{
    return $this->afterCreating(function (Product $product) {
        $product->images()->createMany([
            ["path" => "gallery/defaultProduct.png"],
            ["path" => "gallery/defaultProduct.png"],
            ["path" => "gallery/defaultProduct.png"]
        ]);
    });
}
}
