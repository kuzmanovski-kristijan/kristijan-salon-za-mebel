<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // draft|published|archived (application-level)
            $table->string('status')->default('draft');

            // MVP: free-text is OK; if you need dedicated filter pages later,
            // normalize into brand/collection tables.
            $table->string('brand')->nullable();
            $table->string('collection')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['category_id', 'is_active'], 'idx_products_category_active');
            $table->index(['status', 'is_active'], 'idx_products_status_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

