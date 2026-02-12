<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * Reserve stock atomically: increase stock_reserved if enough available.
     *
     * Recommended usage: call inside DB::transaction() while creating an order.
     */
    public function reserve(int $variantId, int $qty): void
    {
        if ($qty <= 0) {
            throw new RuntimeException('Qty must be > 0');
        }

        $updated = DB::table('product_variants')
            ->where('id', $variantId)
            ->whereRaw('(stock_on_hand - stock_reserved) >= ?', [$qty])
            ->update([
                'stock_reserved' => DB::raw('stock_reserved + '.(int) $qty),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Not enough stock to reserve.');
        }
    }

    /**
     * Release reserved stock (e.g. cancel).
     */
    public function release(int $variantId, int $qty): void
    {
        if ($qty <= 0) {
            throw new RuntimeException('Qty must be > 0');
        }

        DB::table('product_variants')
            ->where('id', $variantId)
            ->update([
                'stock_reserved' => DB::raw(
                    'CASE WHEN stock_reserved >= '.(int) $qty.' THEN stock_reserved - '.(int) $qty.' ELSE 0 END'
                ),
                'updated_at' => now(),
            ]);
    }

    /**
     * Convert reserved -> sold (delivered): decrease both on_hand and reserved.
     */
    public function commitSale(int $variantId, int $qty): void
    {
        if ($qty <= 0) {
            throw new RuntimeException('Qty must be > 0');
        }

        $updated = DB::table('product_variants')
            ->where('id', $variantId)
            ->whereRaw('stock_reserved >= ?', [$qty])
            ->whereRaw('stock_on_hand >= ?', [$qty])
            ->update([
                'stock_on_hand' => DB::raw('stock_on_hand - '.(int) $qty),
                'stock_reserved' => DB::raw('stock_reserved - '.(int) $qty),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Cannot commit sale; inconsistent stock state.');
        }
    }
}

