<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('guest_token')->nullable()->unique();

            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('active'); // active|converted|abandoned
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_carts_user_status');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();

            $table->unsignedInteger('qty');
            $table->decimal('unit_price_snapshot', 12, 2);

            $table->timestamps();

            $table->unique(['cart_id', 'variant_id']);
            $table->index(['cart_id'], 'idx_cart_items_cart');
            $table->index(['variant_id'], 'idx_cart_items_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};

