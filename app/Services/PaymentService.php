<?php

namespace App\Services;

use App\Models\User;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\Token;
use Stripe\Exception\InvalidRequestException;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * PaymentService - Handles all Stripe payment operations
 * 
 * This service provides a clean interface for managing Stripe customers,
 * payment methods, and setup intents while maintaining PCI compliance.
 */
class PaymentService
{
    // Response status constants
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    // Error message constants for consistency
    const ERROR_CUSTOMER_DELETED = 'Your Stripe customer account has been deleted. Please contact support to restore your payment information.';
    const ERROR_CUSTOMER_NOT_FOUND = 'Your account was previously registered with Stripe, but the customer record is no longer available. Please contact support to resolve this issue.';
    const ERROR_CUSTOMER_ACCESS = 'Unable to access your payment information. Please contact support if this issue persists.';
    const ERROR_PAYMENT_METHOD_OWNERSHIP = 'Payment method does not belong to this user';

    public function __construct()
    {
        $this->initializeStripe();
    }

    /**
     * Initialize Stripe with API key
     */
    private function initializeStripe(): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Update Stripe customer details (name and email)
     */
    public function updateStripeCustomer(User $user): array
    {
        try {
            if (!$user->stripe_customer_id) {
                return $this->successResponse('No Stripe customer to update');
            }

            $customer = Customer::retrieve($user->stripe_customer_id);
            
            if ($this->isCustomerDeleted($customer)) {
                $this->log('warning', 'Cannot update deleted Stripe customer', $user);
                return $this->errorResponse('Stripe customer was deleted');
            }

            $updateData = $this->buildCustomerData($user);
            $customer = Customer::update($user->stripe_customer_id, $updateData);

            $this->log('info', 'Updated Stripe customer details', $user, [
                'stripe_customer_id' => $customer->id,
                'updated_name' => $updateData['name'],
                'updated_email' => $updateData['email']
            ]);

            return $this->successResponse('Stripe customer updated successfully', [
                'customer' => $customer
            ]);

        } catch (InvalidRequestException $e) {
            $this->log('error', 'Stripe customer ID mismatch - customer not found in Stripe during update', $user, [
                'user_email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Stripe customer not found');
        } catch (Exception $e) {
            $this->logError('Failed to update Stripe customer', $user, $e);
            return $this->errorResponse('Failed to update Stripe customer: ' . $e->getMessage());
        }
    }

    /**
     * Delete Stripe customer and clear local stripe_customer_id
     */
    public function deleteStripeCustomer(User $user): array
    {
        if (!$user->stripe_customer_id) {
            return $this->successResponse('No Stripe customer to delete');
        }

        try {
            // Retrieve customer to check status; if not deleted, delete it
            $customer = Customer::retrieve($user->stripe_customer_id);
            if (!$this->isCustomerDeleted($customer)) {
                $customer->delete();
            }

            // Clear local reference regardless
            $user->update(['stripe_customer_id' => null]);

            $this->log('info', 'Deleted Stripe customer for user and cleared local stripe_customer_id', $user);
            return $this->successResponse('Stripe customer deleted');
        } catch (InvalidRequestException $e) {
            // If the customer is not found in Stripe, still clear local reference
            $this->log('warning', 'Stripe customer not found during deletion; clearing local stripe_customer_id', $user, [
                'error' => $e->getMessage(),
            ]);
            $user->update(['stripe_customer_id' => null]);
            return $this->successResponse('Stripe customer already deleted or not found; local record cleared');
        } catch (Exception $e) {
            $this->logError('Failed to delete Stripe customer', $user, $e);
            return $this->errorResponse('Failed to delete Stripe customer: ' . $e->getMessage());
        }
    }

    /**
     * Get or create Stripe customer for user
     */
    public function getOrCreateStripeCustomer(User $user): array
    {
        try {
            // Try to retrieve existing customer first
            if ($user->stripe_customer_id) {
                $existingCustomerResult = $this->handleExistingCustomer($user);
                if ($existingCustomerResult !== null) {
                    return $existingCustomerResult;
                }
            }

            // Create new customer if none exists or existing one failed
            return $this->createNewStripeCustomer($user);

        } catch (Exception $e) {
            $this->logError('Failed to get or create Stripe customer', $user, $e);
            return $this->errorResponse('Failed to create customer: ' . $e->getMessage());
        }
    }

    /**
     * Create setup intent for adding payment method
     */
    public function createSetupIntent(User $user): array
    {
        try {
            $customerResult = $this->getOrCreateStripeCustomer($user);
            
            if ($this->isErrorResponse($customerResult)) {
                return $customerResult;
            }

            $setupIntent = $this->createStripeSetupIntent($customerResult['customer']->id);

            $this->log('info', 'Created setup intent', $user, [
                'setup_intent_id' => $setupIntent->id,
                'client_secret' => $setupIntent->client_secret
            ]);

            return $this->successResponse('Setup intent created successfully', [
                'setup_intent' => $setupIntent,
                'client_secret' => $setupIntent->client_secret
            ]);

        } catch (Exception $e) {
            $this->logError('Failed to create setup intent', $user, $e);
            return $this->errorResponse('Failed to create setup intent: ' . $e->getMessage());
        }
    }

    /**
     * Get user's payment methods from Stripe
     */
    public function getPaymentMethods(User $user): array
    {
        try {
            if (!$user->stripe_customer_id) {
                return $this->successResponse('No payment methods found', [
                    'payment_methods' => []
                ]);
            }

            $paymentMethods = PaymentMethod::all([
                'customer' => $user->stripe_customer_id,
                'type' => 'card',
            ]);

            $formattedMethods = $this->formatPaymentMethods($paymentMethods->data);

            $this->log('info', 'Retrieved payment methods', $user, [
                'methods_count' => count($formattedMethods)
            ]);

            return $this->successResponse('Payment methods retrieved successfully', [
                'payment_methods' => $formattedMethods
            ]);

        } catch (Exception $e) {
            $this->logError('Failed to retrieve payment methods', $user, $e);
            return $this->errorResponse('Failed to retrieve payment methods: ' . $e->getMessage());
        }
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod(User $user, string $paymentMethodId): array
    {
        try {
            $this->log('info', 'Starting payment method deletion', $user, [
                'payment_method_id' => $paymentMethodId
            ]);

            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            
            if (!$this->verifyPaymentMethodOwnership($paymentMethod, $user)) {
                $this->log('warning', 'Payment method ownership verification failed', $user, [
                    'payment_method_id' => $paymentMethodId,
                    'payment_method_customer' => $paymentMethod->customer,
                    'user_stripe_customer_id' => $user->stripe_customer_id
                ]);
                return $this->errorResponse(self::ERROR_PAYMENT_METHOD_OWNERSHIP);
            }

            // Restrict deleting the default (primary) payment method
            $customer = Customer::retrieve($user->stripe_customer_id);
            $isDefaultPaymentMethod = $customer->invoice_settings->default_payment_method === $paymentMethodId;
            if ($isDefaultPaymentMethod) {
                $this->log('warning', 'Attempt to delete default (primary) payment method blocked', $user, [
                    'payment_method_id' => $paymentMethodId,
                ]);
                return $this->errorResponse('You cannot delete your primary card. Please set another card as primary first.');
            }

            // Server-side safeguard: prevent deleting the last remaining payment method
            $existingMethods = PaymentMethod::all([
                'customer' => $user->stripe_customer_id,
                'type' => 'card',
            ]);
            if (count($existingMethods->data) <= 1) {
                $this->log('warning', 'Attempt to delete last remaining payment method blocked', $user, [
                    'payment_method_id' => $paymentMethodId,
                ]);
                return $this->errorResponse('You must keep at least one payment method on file.');
            }

            // If somehow default changed between checks, clear it. Keep original behavior.
            $wasDefault = $this->handleDefaultPaymentMethodDeletion($user, $paymentMethodId);

            // Proceed with deletion for non-default methods
            $paymentMethod->detach();

            $this->log('info', 'Successfully deleted payment method', $user, [
                'payment_method_id' => $paymentMethodId,
                'was_default' => $wasDefault
            ]);

            return $this->successResponse('Payment method deleted successfully');

        } catch (Exception $e) {
            $this->logError('Failed to delete payment method', $user, $e, [
                'payment_method_id' => $paymentMethodId
            ]);
            return $this->errorResponse('Failed to delete payment method: ' . $e->getMessage());
        }
    }

    /**
     * Set payment method as default
     */
    public function setDefaultPaymentMethod(User $user, string $paymentMethodId): array
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            
            if (!$this->verifyPaymentMethodOwnership($paymentMethod, $user)) {
                return $this->errorResponse(self::ERROR_PAYMENT_METHOD_OWNERSHIP);
            }

            $this->updateCustomerDefaultPaymentMethod($user->stripe_customer_id, $paymentMethodId);

            $this->log('info', 'Set default payment method', $user, [
                'payment_method_id' => $paymentMethodId
            ]);

            return $this->successResponse('Default payment method updated successfully');

        } catch (Exception $e) {
            $this->logError('Failed to set default payment method', $user, $e, [
                'payment_method_id' => $paymentMethodId
            ]);
            return $this->errorResponse('Failed to set default payment method: ' . $e->getMessage());
        }
    }

    /**
     * Get customer's default payment method
     */
    public function getDefaultPaymentMethod(User $user): array
    {
        try {
            if (!$user->stripe_customer_id) {
                return $this->successResponse('No default payment method found', [
                    'default_payment_method' => null
                ]);
            }

            $customer = Customer::retrieve($user->stripe_customer_id);
            $defaultPaymentMethodId = $customer->invoice_settings->default_payment_method ?? null;

            if (!$defaultPaymentMethodId) {
                return $this->successResponse('No default payment method set', [
                    'default_payment_method' => null
                ]);
            }

            $paymentMethod = PaymentMethod::retrieve($defaultPaymentMethodId);
            $formattedMethod = $this->formatSinglePaymentMethod($paymentMethod);

            $this->log('info', 'Retrieved default payment method', $user, [
                'default_payment_method_id' => $defaultPaymentMethodId
            ]);

            return $this->successResponse('Default payment method retrieved successfully', [
                'default_payment_method' => $formattedMethod
            ]);

        } catch (Exception $e) {
            $this->logError('Failed to retrieve default payment method', $user, $e);
            return $this->errorResponse('Failed to retrieve default payment method: ' . $e->getMessage());
        }
    }

    /**
     * Create payment method from token or payment method ID
     * Supports both  tokens (tok_*) and  payment method IDs (pm_*)
     */
    public function createPaymentMethodFromToken(User $user, string $token, bool $setAsDefault = false): array
    {
        try {
            $this->log('info', 'Creating payment method from token', $user, [
                'token_prefix' => substr($token, 0, 4),
                'set_as_default' => $setAsDefault
            ]);

            // Get or create Stripe customer
            $customerResult = $this->getOrCreateStripeCustomer($user);
            if ($this->isErrorResponse($customerResult)) {
                return $customerResult;
            }

            $customer = $customerResult['customer'];

            // Determine if it's a token or payment method ID
            if (str_starts_with($token, 'tok_')) {
                // Legacy token approach
                return $this->createPaymentMethodFromLegacyToken($customer, $token, $setAsDefault, $user);
            } elseif (str_starts_with($token, 'pm_')) {
                // Modern payment method ID approach
                return $this->attachExistingPaymentMethod($customer, $token, $setAsDefault, $user);
            } else {
                return $this->errorResponse('Invalid token format. Must start with "tok_" or "pm_"');
            }

        } catch (Exception $e) {
            $this->logError('Failed to create payment method from token', $user, $e, [
                'token_prefix' => substr($token, 0, 4)
            ]);
            return $this->errorResponse('Failed to create payment method: ' . $e->getMessage());
        }
    }

       /**
     * Create and confirm a one-off PaymentIntent using specified or default payment method.
     * Returns [status, message, data(payment_intent)].
     */
    public function chargePaymentMethod(User $user, int|float $amount, string $currency = 'sgd', string $description = '', ?string $paymentMethodId = null, ?string $idempotencyKey = null, array $metadata = []): array
    {
        try {
            if (!$user->stripe_customer_id) {
                $res = $this->getOrCreateStripeCustomer($user);
                if ($this->isErrorResponse($res)) {
                    return $res;
                }
            }

            $customer = Customer::retrieve($user->stripe_customer_id);

            // Use specified payment method or default
            $pmId = $paymentMethodId ?? $customer->invoice_settings->default_payment_method ?? null;
            if (!$pmId) {
                return $this->errorResponse('No payment method specified or set as default.');
            }

            // Verify the payment method belongs to this customer (security check)
            if ($paymentMethodId) {
                $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
                if ($paymentMethod->customer !== $user->stripe_customer_id) {
                    return $this->errorResponse('Payment method does not belong to this user.');
                }
            }

            // Amount is expected in smallest currency unit (Stripe requirement)
            $params = [
                'customer' => $user->stripe_customer_id,
                'amount' => (int) round($amount * 100),
                'currency' => $currency,
                'payment_method' => $pmId,
                'confirm' => true,
                'off_session' => true,
                'description' => $description,
                'metadata' => array_merge([
                    'user_id' => (string) $user->id,
                ], $metadata),
                'receipt_email' => $user->email, // This will help with automatic invoice generation
            ];

            $opts = [];
            if (!empty($idempotencyKey)) {
                $opts['idempotency_key'] = $idempotencyKey; // prevent duplicate charges on retries
            }

            $pi = \Stripe\PaymentIntent::create($params, $opts);

            // For one-off PaymentIntents, invoices are NOT automatically generated
            // Invoices are only created for subscriptions or when explicitly using the Invoice API
            // Check if there's an associated invoice and gather its information
            if ($pi->status === 'succeeded' && 
                isset($pi->charges) && 
                is_object($pi->charges) && 
                !empty($pi->charges->data)
            ) {
                $charge = $pi->charges->data[0];
                if (isset($charge->invoice)) {
                    try {
                        // Retrieve the invoice if it exists
                        $invoice = \Stripe\Invoice::retrieve($charge->invoice);
                        // Store invoice info in response data instead of mutating the PaymentIntent
                        $invoiceInfo = [
                            'id' => $invoice->id,
                            'hosted_url' => $invoice->hosted_invoice_url ?? null,
                            'pdf_url' => $invoice->invoice_pdf ?? null,
                            'number' => $invoice->number ?? null
                        ];
                        // Add invoice info to the success response data
                        return $this->successResponse('Payment successful', [
                            'payment_intent' => $pi,
                            'invoice' => $invoiceInfo
                        ]);
                    } catch (\Exception $e) {
                        $this->logError('Failed to retrieve invoice', $user, $e);
                        // Continue without invoice information if retrieval fails
                    }
                }
            }

            return $this->successResponse('Payment successful', ['payment_intent' => $pi]);
        } catch (\Stripe\Exception\CardException $e) {
            return $this->errorResponse('Card declined: '.$e->getMessage());
        } catch (Exception $e) {
            $this->logError('Payment failed', $user, $e);
            return $this->errorResponse('Payment failed: '.$e->getMessage());
        }
    }

    /**
     * Attach order details to an existing PaymentIntent (after successful charge).
     * Updates description and metadata so Stripe shows the order number.
     */
    public function attachOrderDetailsToPaymentIntent(User $user, string $paymentIntentId, string $orderNumber, int $orderId): array
    {
        try {
            // Fetch the PaymentIntent first
            $pi = null;
            try {
                $pi = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            } catch (Exception $e) {
                $this->logError('Failed to retrieve PaymentIntent for ownership check', $user, $e, [
                    'payment_intent_id' => $paymentIntentId,
                    'order_number' => $orderNumber,
                    'order_id' => $orderId,
                ]);
                return $this->errorResponse('Failed to retrieve payment intent: ' . $e->getMessage());
            }

            // Ownership check: compare metadata user_id or customer
            $belongsToUser = false;
            if (isset($pi->metadata['user_id']) && $pi->metadata['user_id'] == (string)$user->id) {
                $belongsToUser = true;
            } elseif (isset($pi->customer) && $user->stripe_customer_id && $pi->customer == $user->stripe_customer_id) {
                $belongsToUser = true;
            }

            if (!$belongsToUser) {
                $this->log('warning', 'PaymentIntent ownership verification failed during update', $user, [
                    'payment_intent_id' => $paymentIntentId,
                    'intent_customer' => $pi->customer ?? null,
                    'intent_user_id' => $pi->metadata['user_id'] ?? null,
                    'user_stripe_customer_id' => $user->stripe_customer_id,
                    'user_id' => $user->id,
                    'order_number' => $orderNumber,
                    'order_id' => $orderId,
                ]);
                return $this->errorResponse('Payment intent does not belong to this user.');
            }

            // Proceed with update
            $pi = \Stripe\PaymentIntent::update($paymentIntentId, [
                'description' => 'Order ' . $orderNumber,
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'order_id' => (string) $orderId,
                    'order_number' => $orderNumber,
                ],
            ]);

            return $this->successResponse('Payment intent updated with order details', ['payment_intent' => $pi]);
        } catch (Exception $e) {
            $this->logError('Failed to attach order details to PaymentIntent', $user, $e, [
                'payment_intent_id' => $paymentIntentId,
                'order_number' => $orderNumber,
                'order_id' => $orderId,
            ]);
            return $this->errorResponse('Failed to update payment metadata: ' . $e->getMessage());
        }
    }
    
    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Handle existing customer retrieval and validation
     */
    private function handleExistingCustomer(User $user): ?array
    {
        try {
            $customer = Customer::retrieve($user->stripe_customer_id);
            
            if ($this->isCustomerDeleted($customer)) {
                $this->log('warning', 'Cannot update deleted Stripe customer', $user);
                return $this->errorResponse(self::ERROR_CUSTOMER_DELETED);
            }

            $this->log('info', 'Retrieved existing Stripe customer', $user, [
                'stripe_customer_id' => $customer->id,
                'customer_email' => $customer->email
            ]);
            return $this->successResponse('Stripe customer retrieved successfully', [
                'customer' => $customer
            ]);

        } catch (InvalidRequestException $e) {
            $this->log('error', 'Stripe customer ID mismatch - customer not found in Stripe during retrieve', $user, [
                'user_email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(self::ERROR_CUSTOMER_NOT_FOUND);
        } catch (Exception $e) {
            $this->logError('Failed to retrieve Stripe customer', $user, $e);
            return $this->errorResponse(self::ERROR_CUSTOMER_ACCESS);
        }
    }

    /**
     * Create new Stripe customer
     */
    private function createNewStripeCustomer(User $user): array
    {
        $customerData = $this->buildCustomerData($user);
        $customer = Customer::create($customerData);

        // Update user with Stripe customer ID
        $user->update(['stripe_customer_id' => $customer->id]);

        $this->log('info', 'Created new Stripe customer', $user, [
            'stripe_customer_id' => $customer->id,
            'customer_email' => $customer->email
        ]);

        return $this->successResponse('Stripe customer created successfully', [
            'customer' => $customer
        ]);
    }

    /**
     * Create Stripe setup intent
     */
    private function createStripeSetupIntent(string $customerId): SetupIntent
    {
        return SetupIntent::create([
            'customer' => $customerId,
            'usage' => 'off_session',
            'payment_method_types' => ['card'],
        ]);
    }

    /**
     * Handle default payment method deletion
     */
    private function handleDefaultPaymentMethodDeletion(User $user, string $paymentMethodId): bool
    {
        $customer = Customer::retrieve($user->stripe_customer_id);
        $isDefaultPaymentMethod = $customer->invoice_settings->default_payment_method === $paymentMethodId;
        
        if ($isDefaultPaymentMethod) {
            $this->log('info', 'Deleting default payment method, clearing default setting', $user, [
                'payment_method_id' => $paymentMethodId
            ]);
            $this->updateCustomerDefaultPaymentMethod($user->stripe_customer_id, null);
        }

        return $isDefaultPaymentMethod;
    }

    /**
     * Update customer's default payment method
     */
    private function updateCustomerDefaultPaymentMethod(string $customerId, ?string $paymentMethodId): void
    {
        Customer::update($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);
    }

    /**
     * Determine if we should force the customer's only payment method to be default.
     * This ensures that when a customer has exactly one payment method, it is set as default
     * if no default is currently set.
     */
    private function shouldForceDefaultPaymentMethod(string $customerId): bool
    {
        try {
            $paymentMethods = PaymentMethod::all([
                'customer' => $customerId,
                'type' => 'card',
            ]);

            if (count($paymentMethods->data) !== 1) {
                return false;
            }

            $onlyMethod = $paymentMethods->data[0] ?? null;
            if (!$onlyMethod) {
                return false;
            }

            $customer = Customer::retrieve($customerId);
            $currentDefault = $customer->invoice_settings->default_payment_method ?? null;

            return $currentDefault !== $onlyMethod->id;
        } catch (Exception $e) {
            Log::warning('Unable to determine if default payment method should be forced', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if customer is deleted
     */
    private function isCustomerDeleted($customer): bool
    {
        return isset($customer->deleted) && $customer->deleted;
    }

    /**
     * Verify payment method ownership
     */
    private function verifyPaymentMethodOwnership(PaymentMethod $paymentMethod, User $user): bool
    {
        return $paymentMethod->customer === $user->stripe_customer_id;
    }

    /**
     * Build customer data for Stripe
     */
    private function buildCustomerData(User $user): array
    {
        return [
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'email' => $user->email,
            'phone' => $user->country_code . $user->phone,
            'metadata' => [
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ]
        ];
    }

    /**
     * Format payment methods for response
     */
    private function formatPaymentMethods(array $paymentMethods): array
    {
        return array_map([$this, 'formatSinglePaymentMethod'], $paymentMethods);
    }

    /**
     * Format single payment method
     */
    private function formatSinglePaymentMethod(PaymentMethod $method): array
    {
        return [
            'id' => $method->id,
            'brand' => ucfirst($method->card->brand),
            'last4' => $method->card->last4,
            'exp_month' => $method->card->exp_month,
            'exp_year' => $method->card->exp_year,
            'is_default' => false // We'll determine this separately if needed
        ];
    }

    /**
     * Format payment method for API response (matches mobile team's expected format)
     */
    private function formatPaymentMethodForApi(PaymentMethod $method, bool $isDefault = false): array
    {
        return [
            'id' => $method->id,
            'exp_month' => $method->card->exp_month,
            'exp_year' => $method->card->exp_year,
            'last4' => $method->card->last4,
            'funding' => $method->card->funding,
            'brand' => $method->card->brand,
            'default' => $isDefault
        ];
    }

    /**
     * Create payment method from Stripe token
     */
    private function createPaymentMethodFromLegacyToken(Customer $customer, string $tokenId, bool $setAsDefault, User $user): array
    {
        try {
            // Retrieve the token to get card details
            $token = Token::retrieve($tokenId);
            
            if (!$token->card) {
                return $this->errorResponse('Token does not contain card information');
            }

            // Create payment method from token
            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'token' => $tokenId,
                ],
            ]);

            // Attach to customer
            $paymentMethod->attach(['customer' => $customer->id]);

            $shouldSetAsDefault = $setAsDefault;

            if (!$shouldSetAsDefault && $this->shouldForceDefaultPaymentMethod($customer->id)) {
                $shouldSetAsDefault = true;
            }

            if ($shouldSetAsDefault) {
                $this->updateCustomerDefaultPaymentMethod($customer->id, $paymentMethod->id);
            }

            $formattedMethod = $this->formatPaymentMethodForApi($paymentMethod, $shouldSetAsDefault);

            $this->log('info', 'Created payment method from legacy token', $user, [
                'token_id' => $tokenId,
                'payment_method_id' => $paymentMethod->id,
                'set_as_default' => $setAsDefault,
                'forced_default' => $shouldSetAsDefault && !$setAsDefault,
            ]);

            return $this->successResponse('Payment method created successfully', [
                'payment_method' => $formattedMethod,
                'is_primary' => $shouldSetAsDefault,
            ]);

        } catch (Exception $e) {
            $this->logError('Failed to create payment method from legacy token', $user, $e, [
                'token_id' => $tokenId
            ]);
            return $this->errorResponse('Failed to create payment method from token: ' . $e->getMessage());
        }
    }

    /**
     * Attach existing payment method to customer
     */
    private function attachExistingPaymentMethod(Customer $customer, string $paymentMethodId, bool $setAsDefault, User $user): array
    {
        try {
            // Retrieve the payment method
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            
            // Attach to customer
            $paymentMethod->attach(['customer' => $customer->id]);

            $shouldSetAsDefault = $setAsDefault;

            if (!$shouldSetAsDefault && $this->shouldForceDefaultPaymentMethod($customer->id)) {
                $shouldSetAsDefault = true;
            }

            if ($shouldSetAsDefault) {
                $this->updateCustomerDefaultPaymentMethod($customer->id, $paymentMethod->id);
            }

            $formattedMethod = $this->formatPaymentMethodForApi($paymentMethod, $shouldSetAsDefault);

            $this->log('info', 'Attached existing payment method to customer', $user, [
                'payment_method_id' => $paymentMethodId,
                'set_as_default' => $setAsDefault,
                'forced_default' => $shouldSetAsDefault && !$setAsDefault,
            ]);

            return $this->successResponse('Payment method attached successfully', [
                'payment_method' => $formattedMethod,
                'is_primary' => $shouldSetAsDefault,
            ]);

        } catch (Exception $e) {
            $this->logError('Failed to attach payment method', $user, $e, [
                'payment_method_id' => $paymentMethodId
            ]);
            return $this->errorResponse('Failed to attach payment method: ' . $e->getMessage());
        }
    }

    /**
     * Check if response is an error
     */
    private function isErrorResponse(array $response): bool
    {
        return $response['status'] === self::STATUS_ERROR;
    }

    /**
     * Create success response
     */
    private function successResponse(string $message, array $data = []): array
    {
        $response = [
            'status' => self::STATUS_SUCCESS,
            'message' => $message
        ];

        return array_merge($response, $data);
    }

    /**
     * Create error response
     */
    private function errorResponse(string $message): array
    {
        return [
            'status' => self::STATUS_ERROR,
            'message' => $message
        ];
    }

    // ========================================
    // LOGGING METHODS
    // ========================================

    /**
     * Log with user context
     */
    private function log(string $level, string $message, User $user, array $additionalContext = []): void
    {
        $context = array_merge([
            'user_id' => $user->id,
            'stripe_customer_id' => $user->stripe_customer_id ?? 'none',
        ], $additionalContext);

        Log::log($level, $message, $context);
    }

    /**
     * Log error with exception details
     */
    private function logError(string $message, User $user, Exception $e, array $additionalContext = []): void
    {
        $context = array_merge([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], $additionalContext);

        $this->log('error', $message, $user, $context);
    }
}