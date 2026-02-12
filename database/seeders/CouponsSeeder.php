<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $welcome10Id = DB::table('coupons')->insertGetId([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => number_format(10, 2, '.', ''),
            'starts_at' => $now->copy()->subDay(),
            'ends_at' => $now->copy()->addDays(30),
            'max_uses' => 1000,
            'per_user_limit' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('coupons')->insert([
            [
                'code' => 'SAVE20',
                'type' => 'fixed',
                'value' => number_format(20, 2, '.', ''),
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDays(14),
                'max_uses' => 200,
                'per_user_limit' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'FREESHIP',
                'type' => 'free_shipping',
                'value' => number_format(0, 2, '.', ''),
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDays(60),
                'max_uses' => null,
                'per_user_limit' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Demo redemption: apply WELCOME10 to the newest customer order (if any).
        $order = DB::table('orders')
            ->whereNotNull('user_id')
            ->orderByDesc('id')
            ->first(['id', 'user_id', 'subtotal', 'shipping_total', 'tax_total']);

        if (! $order) {
            return;
        }

        $subtotal = (float) $order->subtotal;
        $discount = round($subtotal * 0.10, 2);
        $shippingTotal = (float) $order->shipping_total;
        $taxTotal = (float) $order->tax_total;
        $grandTotal = $subtotal - $discount + $shippingTotal + $taxTotal;

        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'discount_total' => number_format($discount, 2, '.', ''),
                'grand_total' => number_format($grandTotal, 2, '.', ''),
                'updated_at' => $now,
            ]);

        DB::table('coupon_redemptions')->insert([
            'coupon_id' => $welcome10Id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'redeemed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

