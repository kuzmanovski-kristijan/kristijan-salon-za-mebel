<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug'); // unique within parent
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['parent_id', 'slug']);
            $table->index(['parent_id', 'sort'], 'idx_categories_parent_sort');
            $table->index(['is_active', 'sort'], 'idx_categories_active_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

