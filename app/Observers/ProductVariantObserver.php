<?php

namespace App\Observers;

use App\Models\ProductVariant;

class ProductVariantObserver
{
    public function saved(ProductVariant $variant): void
    {
        // Ensure signatures are updated after any variant save.
        // Actual recompute happens when values change (via ProductVariantValue hooks),
        // but this keeps things consistent if something else saves the variant.
        $variant->syncVariantSignature();
    }
}

