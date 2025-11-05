<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductVariant;
use App\Models\OrderItem;

class FixReservedStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:fix-reserved-stock {--variant_id= : Fix specific variant ID} {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix reserved_stock values on variants to match only pending orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $variantId = $this->option('variant_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            $variants = $variantId 
                ? ProductVariant::where('id', $variantId)->get()
                : ProductVariant::where('track_inventory', true)->get();

            if ($variants->isEmpty()) {
                $this->error('No variants found.');
                return 1;
            }

            $this->info("Checking " . $variants->count() . " variant(s)...\n");

            // Preload all pending reserved quantities in one query
            $reservedQuantities = OrderItem::select('variant_id')
                ->selectRaw('SUM(reserved_quantity) as total_reserved')
                ->whereHas('order', function($q) {
                    $q->where('status', 'pending');
                })
                ->whereIn('variant_id', $variants->pluck('id'))
                ->groupBy('variant_id')
                ->pluck('total_reserved', 'variant_id')
                ->toArray();

            $fixed = 0;
            $correct = 0;
            $variantsToUpdate = collect();

            // First pass: identify variants that need updates (no DB writes)
            foreach ($variants as $variant) {
                try {
                    $correctReservedStock = (int)($reservedQuantities[$variant->id] ?? 0);
                    $currentReservedStock = (int)$variant->reserved_stock;

                    if ($currentReservedStock !== $correctReservedStock) {
                        $this->warn("Variant #{$variant->id} ({$variant->sku}):");
                        $this->line("  Current reserved_stock: {$currentReservedStock}");
                        $this->line("  Should be: {$correctReservedStock}");
                        $this->line("  Difference: " . ($correctReservedStock - $currentReservedStock));

                        if (!$dryRun) {
                            $variantsToUpdate->push([
                                'variant' => $variant,
                                'new_stock' => $correctReservedStock
                            ]);
                        } else {
                            $this->comment("  [Would fix in real run]");
                        }

                        $fixed++;
                    } else {
                        $correct++;
                    }
                } catch (\Throwable $e) {
                    $this->error("Error checking variant #{$variant->id}: " . $e->getMessage());
                }
            }

            // Second pass: perform all updates in a single transaction
            if (!$dryRun && $variantsToUpdate->isNotEmpty()) {
                try {
                    \DB::transaction(function() use ($variantsToUpdate) {
                        foreach ($variantsToUpdate as $update) {
                            $update['variant']->reserved_stock = $update['new_stock'];
                            $update['variant']->save();
                            $this->info("  ✓ Fixed variant #{$update['variant']->id}!");
                        }
                    });
                } catch (\Throwable $e) {
                    $this->error("Transaction failed - no variants were updated: " . $e->getMessage());
                    $fixed = 0; // Reset fixed count since no changes were persisted
                    throw $e; // Re-throw to trigger the outer catch block
                }
            }

            $this->info("\n" . str_repeat('=', 50));
            $this->info("Summary:");
            $this->info("  Correct: {$correct}");
            $this->info("  Fixed: {$fixed}");

            if ($dryRun && $fixed > 0) {
                $this->warn("\nThis was a DRY RUN. Run without --dry-run to apply changes.");
            }

            return ($fixed >= 0) ? 0 : 1;

        } catch (\Throwable $e) {
            $this->error("Database error: " . $e->getMessage());
            return 1;
        }
    }
}
