<?php

namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(25)
        ->create()
        ->each(function($user){
            $paymentMethods = [
                ['payment_method' => 'MTN-Cash', 'card_number' => fake()->numerify(str_repeat('#', 10))],
                ['payment_method' => 'Syriatel-Cash', 'card_number' => fake()->numerify(str_repeat('#', 10))],
                ['payment_method' => 'BBSF', 'card_number' => fake()->numerify(str_repeat('#', 16))],
            ];
            foreach ($paymentMethods as $paymentData) {
                $user->payments()->create($paymentData); 
            }
            $user->cart()->create();
        });
    }
}
