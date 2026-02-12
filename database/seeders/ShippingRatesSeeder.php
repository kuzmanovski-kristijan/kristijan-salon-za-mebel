<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingRatesSeeder extends Seeder
{
    public function run(): void
    {
        // Keep this seeder idempotent; safe to run multiple times.

        ShippingRate::updateOrCreate(
            ['name' => 'Pickup', 'city' => null, 'zone' => null],
            [
                'price' => 0,
                'free_over' => null,
                'pickup_only' => true,
                'is_active' => true,
                'sort' => 10,
            ],
        );

        ShippingRate::updateOrCreate(
            ['name' => 'Skopje', 'city' => 'Skopje', 'zone' => 'Zone A'],
            [
                'price' => 10,
                'free_over' => 300,
                'pickup_only' => false,
                'is_active' => true,
                'sort' => 20,
            ],
        );

        ShippingRate::updateOrCreate(
            ['name' => 'Other cities', 'city' => null, 'zone' => 'Zone A'],
            [
                'price' => 15,
                'free_over' => 400,
                'pickup_only' => false,
                'is_active' => true,
                'sort' => 30,
            ],
        );

        ShippingRate::updateOrCreate(
            ['name' => 'Remote areas', 'city' => null, 'zone' => 'Zone B'],
            [
                'price' => 25,
                'free_over' => 600,
                'pickup_only' => false,
                'is_active' => true,
                'sort' => 40,
            ],
        );
    }
}

