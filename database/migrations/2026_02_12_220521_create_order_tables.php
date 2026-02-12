<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status')->default('new');
            $table->string('payment_status')->default('unpaid');
            $table->string('shipping_status')->default('unshipped');

            $table->string('currency', 3)->default('EUR');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            $table->string('payment_method')->default('cod'); // MVP
            $table->string('shipping_method')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('placed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_orders_user_created');
            $table->index(['status', 'payment_status'], 'idx_orders_status_payment');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // snapshot + optional reference
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->string('sku');
            $table->string('name');
            $table->string('variant_description')->nullable();

            $table->unsignedInteger('qty');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);

            $table->timestamps();

            $table->index(['order_id'], 'idx_order_items_order');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');

            $table->foreignId('by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('changed_at')->useCurrent();
            $table->string('reason')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'changed_at'], 'idx_order_history');
        });

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // shipping|billing

            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();

            $table->timestamps();

            $table->unique(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

