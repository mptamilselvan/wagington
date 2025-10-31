<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class CouponService
{
    /**
     * Validate and apply a coupon to the given cart
     *
     * @param string $couponCode
     * @param float $subtotal
     * @param int|null $userId
     * @return array ['valid' => bool, 'discount' => float, 'message' => string]
     */
    public function applyCoupon(string $couponCode, float $subtotal, ?int $userId = null): array
    {
        try {
            $couponCode = strtoupper(trim($couponCode));

            if (empty($couponCode)) {
                return [
                    'valid' => false,
                    'discount' => 0,
                    'message' => 'Please enter a coupon code'
                ];
            }

            // Use API to validate voucher code
            return $this->validateVoucherViaAPI($couponCode, $subtotal);
        } catch (\Exception $e) {
            Log::error('CouponService: Failed to apply coupon', [
                'error' => $e->getMessage(),
                'coupon_code' => $couponCode,
                'subtotal' => $subtotal,
                'user_id' => $userId
            ]);

            return [
                'valid' => false,
                'discount' => 0,
                'message' => 'An error occurred while applying the coupon'
            ];
        }
    }

    /**
     * Validate voucher via API
     */
    private function validateVoucherViaAPI(string $couponCode, float $subtotal): array
    {
        try {
            // Base URL and timeout from config
            $baseUrl = rtrim(config('services.promotion_api.url', 'http://localhost:8000'), '/');
            $timeout = (int) config('services.promotion_api.timeout', 5);

            $token = $this->getAuthToken();
            if ($token === null) {
                Log::warning('CouponService: No auth token available for validating voucher', ['coupon_code' => $couponCode]);
                return [
                    'valid' => false,
                    'discount' => 0,
                    'message' => 'Unauthorized'
                ];
            }

            $response = Http::withToken($token)
                ->timeout($timeout)
                ->post("{$baseUrl}/api/promotion/validate-voucher-code", [
                    'voucher_code' => $couponCode
                ]);

            if ($response->failed()) {
                return [
                    'valid' => false,
                    'discount' => 0,
                    'message' => 'Invalid or expired coupon code'
                ];
            }

            $result = $response->json();

            if (!isset($result['status']) || $result['status'] !== 'success') {
                return [
                    'valid' => false,
                    'discount' => 0,
                    'message' => $result['message'] ?? 'Invalid coupon code'
                ];
            }

            // Extract voucher data
            $voucherData = $result['data'] ?? [];
            $voucherId = $voucherData['voucher_id'] ?? null;
            $discountType = $voucherData['discount_type'] ?? 'fixed';
            $discountValue = $voucherData['discount_value'] ?? 0;

            // Ensure we have a valid voucher id — if missing, treat as invalid response
            if (empty($voucherId)) {
                Log::warning('CouponService: validateVoucherViaAPI returned no voucher_id', [
                    'coupon_code' => $couponCode,
                    'response' => $result,
                ]);

                return [
                    'valid' => false,
                    'discount' => 0,
                    'message' => $result['message'] ?? 'Invalid coupon data received from promotion service'
                ];
            }

            // Calculate discount
            $discount = $this->calculateDiscount($discountType, $discountValue, $subtotal);

            return [
                'valid' => true,
                'discount' => $discount,
                'message' => 'Coupon applied successfully',
                'voucher_id' => $voucherId
            ];

        } catch (\Exception $e) {
            Log::error('CouponService: Failed to validate voucher via API', [
                'error' => $e->getMessage(),
                'coupon_code' => $couponCode
            ]);

            return [
                'valid' => false,
                'discount' => 0,
                'message' => 'An error occurred while validating the coupon'
            ];
        }
    }

    /**
     * Calculate discount amount based on type and value
     */
    private function calculateDiscount(string $type, float $value, float $subtotal): float
    {
        $discount = 0;

        if ($type === 'percentage') {
            // Ensure percentage is within valid range
            $value = max(0, min(100, $value));
            $discount = ($subtotal * $value) / 100;
        } elseif ($type === 'fixed') {
            $discount = min($value, $subtotal); // Don't allow discount to exceed subtotal
        }

        // Ensure discount is not negative
        $discount = max(0, $discount);

        return $discount;
    }

    /**
     * Increment voucher usage via API
     */
    public function incrementVoucherUsage(int $voucherId, string $orderId): bool
    {
        try {
            $token = $this->getAuthToken();
            if ($token === null) {
                Log::warning('CouponService: No auth token available for incrementing voucher usage', ['voucher_id' => $voucherId]);
                return false;
            }

            $baseUrl = rtrim(config('services.promotion_api.url', 'http://localhost:8000'), '/');
            $timeout = (int) config('services.promotion_api.timeout', 5);

            $response = Http::withToken($token)
                ->timeout($timeout)
                ->post("{$baseUrl}/api/promotion/voucher/{$voucherId}/increment-usage", [
                    'idempotency_key' => $orderId // Use order ID as idempotency key
                ]);

            if ($response->failed()) {
                Log::error('CouponService: Failed to increment voucher usage via API', [
                    'voucher_id' => $voucherId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

            $result = $response->json();

            if (!isset($result['status']) || $result['status'] !== 'success') {
                Log::error('CouponService: Failed to increment voucher usage', [
                    'voucher_id' => $voucherId,
                    'message' => $result['message'] ?? 'Unknown error'
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('CouponService: Exception while incrementing voucher usage', [
                'error' => $e->getMessage(),
                'voucher_id' => $voucherId
            ]);
            return false;
        }
    }

    /**
     * Get auth token for API requests
     *
     * Returns JWT token string when available, or null when not authenticated or on error.
     */
    private function getAuthToken(): ?string
    {
        // Get the JWT token for the authenticated user
        try {
            if (Auth::check()) {
                return JWTAuth::fromUser(Auth::user());
            }
        } catch (\Exception $e) {
            Log::error('CouponService: Failed to get JWT token', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Record coupon usage (placeholder for compatibility)
     */
    public function recordUsage(int $couponId, int $orderId, ?int $userId = null, float $discountAmount = 0): void
    {
        // This method is kept for compatibility but the actual usage tracking
        // is now handled by the incrementVoucherUsage method
        try {
            // In a real implementation, we might want to log this locally as well
            Log::info('Coupon usage recorded', [
                'coupon_id' => $couponId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'discount_amount' => $discountAmount
            ]);
        } catch (\Exception $e) {
            Log::error('CouponService: Failed to record coupon usage', [
                'error' => $e->getMessage(),
                'coupon_id' => $couponId,
                'order_id' => $orderId,
                'user_id' => $userId
            ]);
        }
    }
}