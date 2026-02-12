<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionAssignment;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $living = Category::create([
            'name' => 'Living Room',
            'slug' => 'living-room',
            'sort' => 1,
            'is_active' => true,
        ]);
        $sofas = Category::create([
            'name' => 'Sofas',
            'slug' => 'sofas',
            'parent_id' => $living->id,
            'sort' => 1,
            'is_active' => true,
        ]);
        $tables = Category::create([
            'name' => 'Tables',
            'slug' => 'tables',
            'parent_id' => $living->id,
            'sort' => 2,
            'is_active' => true,
        ]);

        // Options
        $color = ProductOption::create([
            'name' => 'Color',
            'code' => 'color',
            'sort' => 1,
            'is_active' => true,
        ]);
        $material = ProductOption::create([
            'name' => 'Material',
            'code' => 'material',
            'sort' => 2,
            'is_active' => true,
        ]);

        // Values
        $blue = ProductOptionValue::create([
            'option_id' => $color->id,
            'value' => 'Blue',
            'slug' => 'blue',
            'sort' => 1,
            'is_active' => true,
        ]);
        $gray = ProductOptionValue::create([
            'option_id' => $color->id,
            'value' => 'Gray',
            'slug' => 'gray',
            'sort' => 2,
            'is_active' => true,
        ]);

        $oak = ProductOptionValue::create([
            'option_id' => $material->id,
            'value' => 'Oak',
            'slug' => 'oak',
            'sort' => 1,
            'is_active' => true,
        ]);
        $walnut = ProductOptionValue::create([
            'option_id' => $material->id,
            'value' => 'Walnut',
            'slug' => 'walnut',
            'sort' => 2,
            'is_active' => true,
        ]);

        // Products
        $sofa = Product::create([
            'category_id' => $sofas->id,
            'name' => 'Nordic Sofa',
            'slug' => 'nordic-sofa',
            'description' => 'Comfortable 3-seater sofa.',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
            'brand' => 'MebeliCo',
            'collection' => 'Nordic',
        ]);

        $table = Product::create([
            'category_id' => $tables->id,
            'name' => 'Coffee Table',
            'slug' => 'coffee-table',
            'description' => 'Minimalist coffee table.',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
            'brand' => 'MebeliCo',
            'collection' => 'Nordic',
        ]);

        // Assign options to products
        foreach ([$sofa, $table] as $p) {
            ProductOptionAssignment::create([
                'product_id' => $p->id,
                'option_id' => $color->id,
                'sort' => 1,
            ]);
            ProductOptionAssignment::create([
                'product_id' => $p->id,
                'option_id' => $material->id,
                'sort' => 2,
            ]);
        }

        // Variants: create combinations (2 colors x 2 materials)
        $this->createVariant($sofa, 'SOFA', 599.00, 10, [
            $color->id => [$blue, $gray],
            $material->id => [$oak, $walnut],
        ]);

        $this->createVariant($table, 'TABLE', 199.00, 5, [
            $color->id => [$blue, $gray],
            $material->id => [$oak, $walnut],
        ]);
    }

    private function createVariant(Product $product, string $skuPrefix, float $basePrice, int $stockEach, array $options): void
    {
        // $options = [ option_id => [values...] ]
        $optionIds = array_keys($options);
        $valueLists = array_values($options);

        foreach ($valueLists[0] as $v1) {
            foreach ($valueLists[1] as $v2) {
                $sku = $skuPrefix.'-'.strtoupper(Str::random(6));

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'price' => $basePrice,
                    'compare_at_price' => null,
                    'stock_on_hand' => $stockEach,
                    'stock_reserved' => 0,
                    'weight_grams' => null,
                    'is_active' => true,
                    'variant_signature' => null, // set by observer via values changes
                ]);

                ProductVariantValue::create([
                    'variant_id' => $variant->id,
                    'option_id' => $optionIds[0],
                    'option_value_id' => $v1->id,
                ]);

                ProductVariantValue::create([
                    'variant_id' => $variant->id,
                    'option_id' => $optionIds[1],
                    'option_value_id' => $v2->id,
                ]);
            }
        }
    }
}

