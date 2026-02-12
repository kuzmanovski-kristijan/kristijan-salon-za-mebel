<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,
            DemoCommerceSeeder::class,
            AppointmentsSeeder::class,
            CouponsSeeder::class,
            ShippingRatesSeeder::class,
        ]);
    }
}
