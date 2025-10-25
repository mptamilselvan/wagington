<?php

namespace App\Traits;

use App\Models\User;
use App\Services\PaymentService;
use Exception;

/**
 * Trait SyncsWithStripe
 * 
 * Provides automatic Stripe customer synchronization functionality for services that update user profile information. When user details like name, email, or phone are updated, this trait automatically syncs those changes with Stripe.
 * 
 * Usage:
 * 1. Add 'use SyncsWithStripe;' to your service class
 * 2. Call $this->syncWithStripeIfNeeded($user, $updatedData, $context) after updating user
 * 
 * Features:
 * - Only syncs when Stripe-relevant fields are updated
 * - Only syncs for users who have a Stripe customer ID
 * - Works for admin updates, customer self-updates, and API updates
 */
trait SyncsWithStripe
{
    /**
     * Sync user changes with Stripe if relevant fields were updated
     *
     * @param User $user The updated user model
     * @param array $updatedData The data that was updated
     * @param string $context Context information for logging (e.g., 'admin', 'customer_service', 'api')
     * @return void
     */
    protected function syncWithStripeIfNeeded(User $user, array $updatedData, string $context = 'unknown'): void
    {
        // Only sync if user has a Stripe customer ID (regardless of who is updating)
        if (!$user->stripe_customer_id) {
            return;
        }

        // Check if any Stripe-relevant fields were updated
        $stripeRelevantFields = ['first_name', 'last_name', 'email', 'phone', 'country_code'];
        $hasStripeRelevantChanges = !empty(array_intersect(array_keys($updatedData), $stripeRelevantFields));
        
        if (!$hasStripeRelevantChanges) {
            return;
        }

        try {
            $paymentService = app(PaymentService::class);
            $stripeResult = $paymentService->updateStripeCustomer($user);
            
            if ($stripeResult['status'] === 'error') {
                \Log::warning('Failed to sync customer details with Stripe', [
                    'user_id' => $user->id,
                    'context' => $context,
                    'updated_fields' => array_keys($updatedData),
                    'stripe_error' => $stripeResult['message']
                ]);
            } else {
                \Log::info('Successfully synced customer details with Stripe', [
                    'user_id' => $user->id,
                    'context' => $context,
                    'updated_fields' => array_keys($updatedData)
                ]);
            }
        } catch (Exception $e) {
            \Log::error('Exception during Stripe customer sync', [
                'user_id' => $user->id,
                'context' => $context,
                'updated_fields' => array_keys($updatedData),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the list of fields that are relevant for Stripe synchronization
     *
     * @return array
     */
    protected function getStripeRelevantFields(): array
    {
        return ['first_name', 'last_name', 'email', 'phone', 'country_code'];
    }

    /**
     * Check if the updated data contains any Stripe-relevant fields
     *
     * @param array $updatedData
     * @return bool
     */
    protected function hasStripeRelevantChanges(array $updatedData): bool
    {
        return !empty(array_intersect(array_keys($updatedData), $this->getStripeRelevantFields()));
    }
}