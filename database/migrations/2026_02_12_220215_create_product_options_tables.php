<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // Color
            $table->string('code')->unique();  // color
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort'], 'idx_options_active_sort');
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('option_id')
                ->constrained('product_options')
                ->cascadeOnDelete();

            $table->string('value'); // Oak
            $table->string('slug');  // oak
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['option_id', 'slug']);
            $table->index(['option_id', 'sort'], 'idx_pov_option_sort');
            $table->index(['is_active'], 'idx_pov_active');
        });

        Schema::create('product_option_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('option_id')
                ->constrained('product_options')
                ->restrictOnDelete();

            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'option_id']);
            $table->index(['product_id', 'sort'], 'idx_poa_product_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_assignments');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
    }
};

