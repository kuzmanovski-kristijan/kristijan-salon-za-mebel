<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderStatusService
{
    public function transition(Order $order, string $toStatus, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $toStatus, $reason): void {
            $from = $order->status;

            if ($from === $toStatus) {
                return;
            }

            // MVP rules (can be tightened later)
            $allowed = [
                'new' => ['confirmed', 'canceled'],
                'confirmed' => ['packed', 'canceled'],
                'packed' => ['shipped', 'canceled'],
                'shipped' => ['delivered'],
                'delivered' => [],
                'canceled' => [],
            ];

            if (! in_array($toStatus, $allowed[$from] ?? [], true)) {
                throw new RuntimeException("Invalid transition {$from} -> {$toStatus}");
            }

            $order->status = $toStatus;

            // Minimal shipping_status sync
            if ($toStatus === 'shipped') {
                $order->shipping_status = 'shipped';
            }
            if ($toStatus === 'delivered') {
                $order->shipping_status = 'delivered';
            }
            if ($toStatus === 'canceled') {
                $order->shipping_status = 'canceled';
            }

            $order->save();

            $order->history()->create([
                'from_status' => $from,
                'to_status' => $toStatus,
                'by_user_id' => auth()->id(),
                'changed_at' => now(),
                'reason' => $reason,
            ]);
        });
    }
}

