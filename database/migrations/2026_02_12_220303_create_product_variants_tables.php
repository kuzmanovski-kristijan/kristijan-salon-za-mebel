<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();

            $table->unsignedInteger('stock_on_hand')->default(0);
            $table->unsignedInteger('stock_reserved')->default(0);

            $table->unsignedInteger('weight_grams')->nullable();
            $table->boolean('is_active')->default(true);

            // "12-44-78" (sorted option_value_ids)
            $table->string('variant_signature')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'is_active'], 'idx_variants_product_active');
            $table->unique(['product_id', 'variant_signature']); // prevents dup combos
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('option_id')
                ->constrained('product_options')
                ->restrictOnDelete();

            $table->foreignId('option_value_id')
                ->constrained('product_option_values')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['variant_id', 'option_id']); // one value per option
            $table->index(['variant_id'], 'idx_pvv_variant');
            $table->index(['option_value_id'], 'idx_pvv_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
    }
};

