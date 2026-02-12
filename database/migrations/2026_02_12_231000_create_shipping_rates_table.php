<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Skopje", "Zone A"
            $table->string('city')->nullable();
            $table->string('zone')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('free_over', 12, 2)->nullable();
            $table->boolean('pickup_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort'], 'idx_shipping_rates_active_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};

