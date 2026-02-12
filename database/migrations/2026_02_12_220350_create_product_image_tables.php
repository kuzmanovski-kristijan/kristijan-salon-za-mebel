<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'sort'], 'idx_product_images_sort');
        });

        Schema::create('variant_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->index(['variant_id', 'sort'], 'idx_variant_images_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_images');
        Schema::dropIfExists('product_images');
    }
};

