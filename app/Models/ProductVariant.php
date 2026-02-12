<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'price', 'compare_at_price',
        'stock_on_hand', 'stock_reserved', 'weight_grams',
        'is_active', 'variant_signature',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class, 'variant_id');
    }

    public function getStockAvailableAttribute(): int
    {
        return max(0, (int) $this->stock_on_hand - (int) $this->stock_reserved);
    }

    /**
     * Keep `variant_signature` in sync with the variant's option values.
     *
     * Notes:
     * - Signature stays NULL until the variant has a complete set of values for all
     *   options assigned to the product (prevents transient unique constraint issues).
     * - For products without assigned options, signature is an empty string ("").
     */
    public function syncVariantSignature(): void
    {
        if (! $this->exists) {
            return;
        }

        $signature = $this->calculateVariantSignature();

        if ($this->variant_signature === $signature) {
            return;
        }

        $this->forceFill(['variant_signature' => $signature])->saveQuietly();
    }

    protected function calculateVariantSignature(): ?string
    {
        $expectedOptionIds = DB::table('product_option_assignments')
            ->where('product_id', $this->product_id)
            ->pluck('option_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $values = DB::table('product_variant_values')
            ->where('variant_id', $this->getKey())
            ->get(['option_id', 'option_value_id']);

        // No options assigned to the product => single default variant.
        if (empty($expectedOptionIds)) {
            if ($values->isEmpty()) {
                return '';
            }

            // Fallback: if values exist without assignments, still generate a stable signature.
            $optionValueIds = $values
                ->pluck('option_value_id')
                ->map(fn ($v) => (int) $v)
                ->sort()
                ->values()
                ->all();

            return implode('-', $optionValueIds);
        }

        // Don't compute a signature until the variant has a full set of values.
        if ($values->count() !== count($expectedOptionIds)) {
            return null;
        }

        $currentOptionIds = $values
            ->pluck('option_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $expectedOptionIdsSorted = $expectedOptionIds;
        sort($expectedOptionIdsSorted);

        if ($currentOptionIds !== $expectedOptionIdsSorted) {
            return null;
        }

        $optionValueIds = $values
            ->pluck('option_value_id')
            ->map(fn ($v) => (int) $v)
            ->sort()
            ->values()
            ->all();

        return implode('-', $optionValueIds);
    }

    protected static function booted(): void
    {
        static::creating(function (self $variant): void {
            if ($variant->variant_signature !== null) {
                return;
            }

            $expectedCount = DB::table('product_option_assignments')
                ->where('product_id', $variant->product_id)
                ->count();

            if ($expectedCount === 0) {
                // Make the "no options" signature deterministic.
                $variant->variant_signature = '';
            }
        });
    }
}
