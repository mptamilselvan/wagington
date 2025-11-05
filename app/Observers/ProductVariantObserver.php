<?php

namespace App\Observers;

use App\Models\ProductVariant;
use App\Services\BackorderResolutionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "updated" event.
     * Triggered when stock_quantity is updated - auto-process backorders
     */
    public function updated(ProductVariant $productVariant): void
    {
        // Check if stock_quantity was changed
        if ($productVariant->wasChanged('stock_quantity')) {
            $oldStock = $productVariant->getOriginal('stock_quantity');
            $newStock = $productVariant->stock_quantity;

            Log::info('ProductVariantObserver: Stock quantity changed', [
                'variant_id' => $productVariant->id,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change' => $newStock - $oldStock
            ]);

            // Only trigger backorder resolution if stock increased
            if ($newStock > $oldStock) {
                Log::info('ProductVariantObserver: Stock increased, triggering backorder resolution', [
                    'variant_id' => $productVariant->id,
                    'stock_increase' => $newStock - $oldStock
                ]);

                // Defer backorder processing until after the current transaction commits
                // This ensures the stock update is persisted before we try to process backorders
                DB::afterCommit(function () use ($productVariant) {
                    try {
                        $backorderService = app(BackorderResolutionService::class);
                        $result = $backorderService->autoProcessBackordersForVariant($productVariant->id);

                        Log::info('ProductVariantObserver: Backorder auto-processing completed', [
                            'variant_id' => $productVariant->id,
                            'result' => $result
                        ]);
                    } catch (\Exception $e) {
                        Log::error('ProductVariantObserver: Failed to auto-process backorders', [
                            'variant_id' => $productVariant->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                });
            }
        }
    }
}
