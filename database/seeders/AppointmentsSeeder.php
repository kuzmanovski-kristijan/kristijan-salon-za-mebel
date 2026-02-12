<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $workingHours = json_encode([
            'mon' => ['09:00', '18:00'],
            'tue' => ['09:00', '18:00'],
            'wed' => ['09:00', '18:00'],
            'thu' => ['09:00', '18:00'],
            'fri' => ['09:00', '18:00'],
            'sat' => ['10:00', '14:00'],
            'sun' => null,
        ]);

        $store1Id = DB::table('stores')->insertGetId([
            'name' => 'Skopje - Centar',
            'address' => 'Centar, Skopje',
            'timezone' => 'Europe/Skopje',
            'working_hours' => $workingHours,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $store2Id = DB::table('stores')->insertGetId([
            'name' => 'Skopje - Aerodrom',
            'address' => 'Aerodrom, Skopje',
            'timezone' => 'Europe/Skopje',
            'working_hours' => $workingHours,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $appointments = [];
        $baseDay = now()->startOfDay()->addDay();

        foreach ([$store1Id, $store2Id] as $storeId) {
            for ($i = 0; $i < 5; $i++) {
                $startsAt = $baseDay->copy()->addDays($i)->setTime(11, 0);
                $appointments[] = [
                    'store_id' => $storeId,
                    'customer_name' => 'Demo Customer',
                    'customer_phone' => '+38970000000',
                    'customer_email' => 'customer@example.com',
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHour(),
                    'status' => $i % 2 === 0 ? 'confirmed' : 'new',
                    'note' => $i % 2 === 0 ? 'Showroom consultation' : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('appointments')->insert($appointments);
    }
}

