<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\GuestCartItem;
use Illuminate\Support\Facades\DB;

class ClearAbandonedCarts extends Command
{
    protected $signature = 'app:clear-abandoned-carts';
    protected $description = 'Delete expired cart items only (no inventory changes)';

    public function handle(): int
    {
        $now = now();
        // Bulk delete expired cart items
        $cartCount = CartItem::query()->whereNotNull('expires_at')->where('expires_at', '<', $now)->delete();
        $guestCount = \App\Models\GuestCartItem::query()->whereNotNull('expires_at')->where('expires_at', '<', $now)->delete();
        $count = $cartCount + $guestCount;

        $this->info("Cleared {$count} expired cart items.");
        return Command::SUCCESS;
    }
}