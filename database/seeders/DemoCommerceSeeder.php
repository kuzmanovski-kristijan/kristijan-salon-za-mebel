<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class DemoCommerceSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::findOrCreate('admin', 'web');
        $staffRole = Role::findOrCreate('staff', 'web');
        $customerRole = Role::findOrCreate('customer', 'web');

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles([$adminRole]);

        $staff = User::updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $staff->syncRoles([$staffRole]);

        $customer = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Customer User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $customer->syncRoles([$customerRole]);

        User::factory()
            ->count(5)
            ->create()
            ->each(function (User $user) use ($customerRole): void {
                $user->syncRoles([$customerRole]);
            });

        $variants = $this->pickVariants(6, 5);

        // Guest cart
        $guestCartId = DB::table('carts')->insertGetId([
            'user_id' => null,
            'guest_token' => (string) Str::uuid(),
            'currency' => 'EUR',
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cart_items')->insert([
            [
                'cart_id' => $guestCartId,
                'variant_id' => $variants[0]['id'],
                'qty' => 1,
                'unit_price_snapshot' => $variants[0]['price'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cart_id' => $guestCartId,
                'variant_id' => $variants[1]['id'],
                'qty' => 2,
                'unit_price_snapshot' => $variants[1]['price'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Customer cart
        $customerCartId = DB::table('carts')->insertGetId([
            'user_id' => $customer->id,
            'guest_token' => null,
            'currency' => 'EUR',
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cart_items')->insert([
            [
                'cart_id' => $customerCartId,
                'variant_id' => $variants[2]['id'],
                'qty' => 1,
                'unit_price_snapshot' => $variants[2]['price'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cart_id' => $customerCartId,
                'variant_id' => $variants[3]['id'],
                'qty' => 1,
                'unit_price_snapshot' => $variants[3]['price'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $inventory = app(InventoryService::class);

        // Orders: 1) new, 2) delivered, 3) canceled
        $this->createOrder(
            userId: $customer->id,
            byUserId: $staff->id,
            items: [
                ['variant_id' => $variants[0]['id'], 'qty' => 1],
                ['variant_id' => $variants[2]['id'], 'qty' => 1],
            ],
            shippingTotal: 15,
            statusFlow: ['new'],
            paymentStatus: 'unpaid',
            shippingStatus: 'unshipped',
            inventory: $inventory,
            placedAt: now()->subHours(2),
        );

        $this->createOrder(
            userId: null,
            byUserId: $staff->id,
            items: [
                ['variant_id' => $variants[1]['id'], 'qty' => 1],
            ],
            shippingTotal: 20,
            statusFlow: ['new', 'confirmed', 'delivered'],
            paymentStatus: 'paid',
            shippingStatus: 'delivered',
            inventory: $inventory,
            placedAt: now()->subDays(2),
        );

        $this->createOrder(
            userId: $customer->id,
            byUserId: $staff->id,
            items: [
                ['variant_id' => $variants[3]['id'], 'qty' => 2],
            ],
            shippingTotal: 15,
            statusFlow: ['new', 'canceled'],
            paymentStatus: 'unpaid',
            shippingStatus: 'canceled',
            inventory: $inventory,
            placedAt: now()->subDays(1),
        );
    }

    /**
     * @return array<int, array{id: int, price: string}>
     */
    private function pickVariants(int $count, int $minOnHand): array
    {
        $variants = DB::table('product_variants')
            ->where('stock_on_hand', '>=', $minOnHand)
            ->orderBy('id')
            ->limit($count)
            ->get(['id', 'price'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'price' => (string) $row->price])
            ->all();

        if (count($variants) < $count) {
            $variants = DB::table('product_variants')
                ->where('stock_on_hand', '>=', 1)
                ->orderBy('id')
                ->limit($count)
                ->get(['id', 'price'])
                ->map(fn ($row) => ['id' => (int) $row->id, 'price' => (string) $row->price])
                ->all();
        }

        if (count($variants) < $count) {
            throw new \RuntimeException('Not enough variants with stock to seed demo commerce.');
        }

        return $variants;
    }

    /**
     * @param array<int, array{variant_id: int, qty: int}> $items
     * @param array<int, string> $statusFlow
     */
    private function createOrder(
        ?int $userId,
        ?int $byUserId,
        array $items,
        int $shippingTotal,
        array $statusFlow,
        string $paymentStatus,
        string $shippingStatus,
        InventoryService $inventory,
        \DateTimeInterface $placedAt,
    ): int {
        $now = now();

        return (int) DB::transaction(function () use (
            $userId,
            $byUserId,
            $items,
            $shippingTotal,
            $statusFlow,
            $paymentStatus,
            $shippingStatus,
            $inventory,
            $placedAt,
            $now
        ): int {
            $number = $this->nextOrderNumber();

            $orderItems = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $variantId = $item['variant_id'];
                $qty = $item['qty'];

                $variant = DB::table('product_variants')
                    ->join('products', 'products.id', '=', 'product_variants.product_id')
                    ->where('product_variants.id', $variantId)
                    ->first(['product_variants.sku', 'product_variants.price', 'products.name as product_name']);

                if (! $variant) {
                    throw new \RuntimeException("Variant {$variantId} not found.");
                }

                $unitPrice = (float) $variant->price;
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'variant_id' => $variantId,
                    'sku' => (string) $variant->sku,
                    'name' => (string) $variant->product_name,
                    'variant_description' => $this->describeVariant($variantId),
                    'qty' => $qty,
                    'unit_price' => number_format($unitPrice, 2, '.', ''),
                    'discount' => number_format(0, 2, '.', ''),
                    'line_total' => number_format($lineTotal, 2, '.', ''),
                ];
            }

            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $grandTotal = $subtotal - $discountTotal + $shippingTotal + $taxTotal;

            $finalStatus = end($statusFlow) ?: 'new';
            reset($statusFlow);

            $orderId = (int) DB::table('orders')->insertGetId([
                'number' => $number,
                'user_id' => $userId,
                'status' => $finalStatus,
                'payment_status' => $paymentStatus,
                'shipping_status' => $shippingStatus,
                'currency' => 'EUR',
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'discount_total' => number_format($discountTotal, 2, '.', ''),
                'shipping_total' => number_format($shippingTotal, 2, '.', ''),
                'tax_total' => number_format($taxTotal, 2, '.', ''),
                'grand_total' => number_format($grandTotal, 2, '.', ''),
                'payment_method' => 'cod',
                'shipping_method' => 'standard',
                'notes' => null,
                'placed_at' => $placedAt,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            foreach ($orderItems as $oi) {
                // Reserve before inserting items so failures roll back the order.
                $inventory->reserve((int) $oi['variant_id'], (int) $oi['qty']);

                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'variant_id' => $oi['variant_id'],
                    'sku' => $oi['sku'],
                    'name' => $oi['name'],
                    'variant_description' => $oi['variant_description'],
                    'qty' => $oi['qty'],
                    'unit_price' => $oi['unit_price'],
                    'discount' => $oi['discount'],
                    'line_total' => $oi['line_total'],
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]);
            }

            // Shipping + billing snapshot
            DB::table('order_addresses')->insert([
                [
                    'order_id' => $orderId,
                    'type' => 'shipping',
                    'full_name' => $userId ? 'Customer User' : 'Guest Customer',
                    'email' => $userId ? 'customer@example.com' : 'guest@example.com',
                    'phone' => '+38970000000',
                    'country' => 'MK',
                    'city' => 'Skopje',
                    'zip' => '1000',
                    'line1' => 'Partizanski Odredi 1',
                    'line2' => null,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ],
                [
                    'order_id' => $orderId,
                    'type' => 'billing',
                    'full_name' => $userId ? 'Customer User' : 'Guest Customer',
                    'email' => $userId ? 'customer@example.com' : 'guest@example.com',
                    'phone' => '+38970000000',
                    'country' => 'MK',
                    'city' => 'Skopje',
                    'zip' => '1000',
                    'line1' => 'Partizanski Odredi 1',
                    'line2' => null,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ],
            ]);

            // Status history
            $previous = null;
            foreach ($statusFlow as $status) {
                DB::table('order_status_histories')->insert([
                    'order_id' => $orderId,
                    'from_status' => $previous,
                    'to_status' => $status,
                    'by_user_id' => $byUserId,
                    'changed_at' => $placedAt,
                    'reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $previous = $status;
            }

            // Finalize stock for special terminal states.
            if ($finalStatus === 'delivered') {
                foreach ($orderItems as $oi) {
                    $inventory->commitSale((int) $oi['variant_id'], (int) $oi['qty']);
                }
            }

            if ($finalStatus === 'canceled') {
                foreach ($orderItems as $oi) {
                    $inventory->release((int) $oi['variant_id'], (int) $oi['qty']);
                }
            }

            return $orderId;
        });
    }

    private function nextOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = DB::table('orders')
            ->where('number', 'like', 'ORD-'.$date.'-%')
            ->count();

        return sprintf('ORD-%s-%04d', $date, $count + 1);
    }

    private function describeVariant(int $variantId): ?string
    {
        $rows = DB::table('product_variant_values as pvv')
            ->join('product_options as po', 'po.id', '=', 'pvv.option_id')
            ->join('product_option_values as pov', 'pov.id', '=', 'pvv.option_value_id')
            ->where('pvv.variant_id', $variantId)
            ->orderBy('po.sort')
            ->get(['po.name as option_name', 'pov.value as option_value']);

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows
            ->map(fn ($r) => (string) $r->option_name.': '.(string) $r->option_value)
            ->implode(', ');
    }
}
