<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\BasePromotion;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Auth;

class VoucherService
{
    /**
     * Create a voucher for the customer with the given promotion.
     */
    public function createVoucher(BasePromotion $promotion, User $user, string $voucherType, ?User $referee = null): Voucher
    {
        // Handle Invalid promo code
        if (!$promotion) {
            throw new Exception("Invalid promo code.");
        }

        // If referee reward, allow only one
        if ($voucherType === Voucher::TYPE_REFEREE_REWARD && 
            Voucher::where('customer_id', $user->id)
                ->where('voucher_type', Voucher::TYPE_REFEREE_REWARD)
                ->exists()
        ) {
            throw new Exception("Only one referee reward allowed.");
        }
        
        if ($promotion->marketingCampaign) 
        {
            // Handle customer type new restrictions
            if ($promotion->marketingCampaign->customer_type === 'new') {
                // Calculate how many days since signup
                $daysSinceSignup = $user->created_at
                    ? $user->created_at->diffInDays(Carbon::now())
                    : PHP_INT_MAX;

                if ($daysSinceSignup > $promotion->marketingCampaign->new_customer_days) {
                    throw new Exception("This promo code is only for new customers (within {$promotion->marketingCampaign->new_customer_days} days).");
                }
            }

            // Handle customer type selected restrictions
            if ($promotion->marketingCampaign->customer_type === 'selected' && !empty($promotion->marketingCampaign->selected_customer_ids)) {
                $allowedIds = json_decode($promotion->marketingCampaign->selected_customer_ids);
                // dd($allowedIds);
                if (!in_array($user->id, $allowedIds)) {
                    throw new Exception("You are not eligible for this promo code.");
                }
            }
        }

        // // Handle active promo code
        if (!$promotion->isActive()) {
            throw new Exception("This promo code is no longer active.");
        }

        // check if already redeemed
        $voucher = Voucher::where('customer_id', Auth::id())
            ->where('voucher_code', $promotion->promo_code)
            ->first();

        if ($voucher) {
            throw new Exception("This promo code has already been added into your wallet.");
        }

        // Set voucher status
        $status = $voucherType === Voucher::TYPE_REFERRER_REWARD 
            ? Voucher::STATUS_PENDING 
            : Voucher::STATUS_AVAILABLE;

        // Handle referral promotions differently
        if ($promotion->promotion === BasePromotion::TYPE_REFERRAL) {
            $discountValue = $voucherType === Voucher::TYPE_REFERRER_REWARD
                ? $promotion->referralPromotion->referrer_reward
                : $promotion->referralPromotion->referee_reward;

            // Generate unique random code
            do {
                $voucherCode = strtoupper(Str::random(7));
            } while (Voucher::where('voucher_code', $voucherCode)->exists());

            $discountType = $promotion->referralPromotion->discount_type;

        } else {
            $discountValue = $promotion->marketingCampaign->discount_value;
            $discountType = $promotion->marketingCampaign->discount_type;
            $voucherCode = $promotion->promo_code;
        }

        // create voucher
        return Voucher::create([
            'promotion_id'   => $promotion->id,
            'voucher_code'   => $voucherCode,
            'customer_id'    => $user->id,
            'discount_type'  => $discountType,
            'voucher_type'   => $voucherType,
            'discount_value' => $discountValue,
            'max_usage'      => $promotion->promotion === BasePromotion::TYPE_REFERRAL ? 1 : $promotion->marketingCampaign->customer_usage_limit,
            'status'         => $status,
            'valid_till'     => Carbon::now()->addDays($promotion->coupon_validity),
            'referee_id'     => $referee?->id,
        ]);
    }

     /**
     * Validate a voucher for the customer.
     */
    public function validateVoucher(array $data, $user_id)
    {

        // Validate input inside service
        $validator = Validator::make($data, [
            'voucher_code' => 'required|string|max:32',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $voucher_code = $data['voucher_code'];

        $voucher = Voucher::with('promotion')
            ->where('voucher_code', $voucher_code)
            ->where('customer_id', $user_id)
            ->first();
        
        // Handle Invalid promo code
        if (!$voucher) {
            throw new Exception("Invalid promo code.");
        }

        // Handle active promo code
        if ($voucher->status != "available") {
            throw new Exception("This promo code is no longer active.");
        }
        
        // Check validity period
        $today = Carbon::now();
        if ($voucher->valid_till && $today->gt($voucher->valid_till)) {
            throw new Exception('This voucher has expired.',);
        }

        // Check usage count
        if ($voucher->max_usage <= $voucher->usage_count) {
            throw new Exception('You have already used this voucher the maximum allowed times.');
        }

        return [
            'status' => "success",
            'data' => [
                'voucher_id'   => $voucher->id,
                'voucher_code'     => $voucher->voucher_code,
                'discount_type'  => $voucher->discount_type ?? null,
                'discount_value' => $voucher->discount_value ?? null,
                'valid_till'     => $voucher->valid_till,
                'stackable'     => $voucher->promotion->stackable,
            ],
        ]; 
    }

    public function incrementVoucherUsage(int $voucherId): array
    {
        $user = Auth::user();

        $voucher = Voucher::where('id', $voucherId)
            ->where('customer_id', $user->id)
            ->first();

        if (!$voucher) {
            throw new Exception('Voucher not found.');
        }

        if ($voucher->usage_count >= $voucher->max_usage) {
            throw new Exception('Maximum usage reached.');
        }

        $voucher->increment('usage_count');

        return [
            'status' => 'success',
            'data' => [
                'id'          => $voucher->id,
                'promo_code'  => $voucher->voucher_code,
                'usage_count' => $voucher->usage_count,
            ]
        ];
    }

    /**
     * Validate multiple vouchers for the customer
     */
    public function validateMultipleVouchers(array $codes, int $userId): array
    {
        $valid = [];
        $errors = [];

        foreach ($codes as $code) {
            try {
                $validationResult = $this->validateVoucher(['voucher_code' => strtoupper(trim($code))], $userId);
                $valid[] = $validationResult['data'];
            } catch (\Exception $e) {
                $errors[strtoupper(trim($code))] = $e->getMessage();
            }
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
        ];
    }

    /**
     * Check stackability rules and resolve conflicts
     */
    public function checkStackability(array $validVouchers, float $subtotal): array
    {
        // Separate vouchers by stackability
        $stackable = [];
        $nonStackable = [];

        foreach ($validVouchers as $voucher) {
            if ($voucher['stackable']) {
                $stackable[] = $voucher;
            } else {
                $nonStackable[] = $voucher;
            }
        }

        // If all are stackable, no conflicts
        if (empty($nonStackable)) {
            return [
                'valid' => $validVouchers,
                'conflicts' => [],
                'message' => null,
            ];
        }

        // Find the non-stackable voucher with highest savings
        $bestNonStackable = null;
        $maxSavings = 0;

        foreach ($nonStackable as $voucher) {
            $savings = $this->calculateSavings($voucher, $subtotal);
            if ($savings > $maxSavings) {
                $maxSavings = $savings;
                $bestNonStackable = $voucher;
            }
        }

        // Conflicts are all other vouchers (stackable + other non-stackables)
        $conflicts = array_filter($validVouchers, function ($v) use ($bestNonStackable) {
            return $v['voucher_id'] !== $bestNonStackable['voucher_id'];
        });

        $message = $this->buildStackabilityMessage($bestNonStackable, $conflicts, $subtotal);

        return [
            'valid' => [$bestNonStackable],
            'conflicts' => $conflicts,
            'message' => $message,
        ];
    }

    /**
     * Calculate potential savings for a voucher
     */
    private function calculateSavings(array $voucher, float $subtotal): float
    {
        if ($voucher['discount_type'] === 'percentage') {
            return ($subtotal * $voucher['discount_value']) / 100;
        } else {
            return min($voucher['discount_value'], $subtotal);
        }
    }

    private function buildStackabilityMessage(array $selectedVoucher, array $conflicts, float $subtotal): ?string
    {
        if (empty($conflicts)) {
            return null;
        }

        $parts = [];
        $selectedCode = $selectedVoucher['voucher_code'] ?? 'UNKNOWN';
        $selectedStackable = (bool) ($selectedVoucher['stackable'] ?? false);
        $selectedSavings = $this->calculateSavings($selectedVoucher, $subtotal);

        $parts[] = sprintf(
            '%s is %s and gives a %s discount (%.2f savings) so it remains applied.',
            $selectedCode,
            $selectedStackable ? 'stackable' : 'non-stackable',
            $selectedVoucher['discount_type'] === 'percentage'
                ? sprintf('%s%%', number_format((float) ($selectedVoucher['discount_value'] ?? 0), 2))
                : sprintf('S$%.2f', (float) ($selectedVoucher['discount_value'] ?? 0)),
            $selectedSavings
        );

        foreach ($conflicts as $conflict) {
            $conflictCode = $conflict['voucher_code'] ?? 'UNKNOWN';
            $conflictStackable = (bool) ($conflict['stackable'] ?? false);
            $conflictSavings = $this->calculateSavings($conflict, $subtotal);

            if ($conflictStackable) {
                $reason = sprintf('it cannot be combined with the non-stackable voucher %s', $selectedCode);
            } else {
                $reason = 'it conflicts with another non-stackable coupon';
            }

            if ($conflictSavings > $selectedSavings) {
                $reason .= sprintf(', even though %s would save more (%.2f), it cannot be used with non-stackable vouchers so %s was kept', $conflictCode, $conflictSavings, $selectedCode);
            } elseif ($conflictSavings < $selectedSavings) {
                $reason .= sprintf(', %s was removed because %s saves more (%.2f vs %.2f)', $conflictCode, $selectedCode, $selectedSavings, $conflictSavings);
            } else {
                $reason .= sprintf(', %s was removed because both save %.2f and %s was selected based on priority', $conflictCode, $conflictSavings, $selectedCode);
            }

            $parts[] = sprintf(
                '%s is %s; %s',
                $conflictCode,
                $conflictStackable ? 'stackable' : 'non-stackable',
                $reason
            );
        }

        return implode("\n", $parts);
    }

    /**
     * Calculate stacked discount with sequential application
     */
    public function calculateStackedDiscount(array $sortedVouchers, float $subtotal): array
    {
        $runningTotal = $subtotal;
        $breakdown = [];
        $stackOrder = 1;
        $totalDiscount = 0;

        foreach ($sortedVouchers as $voucher) {
            // Calculate stack priority in code
            $stackPriority = $this->calculateStackPriority($voucher);

            // Calculate discount on current running total
            $discountAmount = 0;
            if ($voucher['discount_type'] === 'percentage') {
                $discountAmount = ($runningTotal * $voucher['discount_value']) / 100;
            } else {
                $discountAmount = min($voucher['discount_value'], $runningTotal);
            }

            $discountAmount = round($discountAmount, 2);
            $runningTotal = max(0, $runningTotal - $discountAmount);
            $totalDiscount += $discountAmount;

            $breakdown[] = [
                'voucher_id' => $voucher['voucher_id'],
                'voucher_code' => $voucher['voucher_code'],
                'discount_type' => $voucher['discount_type'],
                'discount_value' => $voucher['discount_value'],
                'calculated_discount' => $discountAmount,
                'stack_order' => $stackOrder,
                'stack_priority' => $stackPriority,
                'running_total_after' => round($runningTotal, 2),
            ];

            $stackOrder++;
        }

        return [
            'total_discount' => round($totalDiscount, 2),
            'final_amount' => round($runningTotal, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate stack priority for a voucher
     */
    private function calculateStackPriority(array $voucher): int
    {
        if ($voucher['discount_type'] === 'percentage') {
            $base = 80; // Middle of 70-100 range
            return $base + (int)$voucher['discount_value'];
        } else {
            $base = 40; // Middle of 10-60 range
            return $base + (int)$voucher['discount_value'];
        }
    }
}
