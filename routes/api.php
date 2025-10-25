<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerProfilePhotoController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PetProfileController;
use App\Http\Controllers\Api\PetSettingController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\GeneralSettingController;
use App\Http\Controllers\Api\ServiceSettingsController;
use App\Http\Controllers\Api\PromotionWalletController;
use App\Http\Controllers\Api\ReferralSignupController;
use App\Http\Controllers\Api\EcommerceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtpUnified'])->middleware('process.session.token'); // New unified endpoint

Route::post('/login', [AuthController::class, 'login'])->middleware('process.session.token'); // sends OTP on each login

Route::post('/admin-login', [AuthController::class, 'adminLogin']); // This endpoint issues JWT for admins

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

// ----------------------
// Public E-commerce APIs (3 core APIs for mobile)
// ----------------------
Route::middleware([
    'api',
    'process.session.token',
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
])->group(function () {
    Route::get('/ecommerce/landing', [EcommerceController::class, 'landing']);
    Route::get('/ecommerce/products', [EcommerceController::class, 'index']);
    Route::get('/ecommerce/filters', [EcommerceController::class, 'filters']);

    // Product detail API for mobile
    Route::get('/ecommerce/products/{slug}', [EcommerceController::class, 'show']);

    // Cart APIs (public for guests, works with session tokens)
    Route::get('/ecommerce/cart', [EcommerceController::class, 'cart']);
    Route::post('/ecommerce/cart/items', [EcommerceController::class, 'addToCart']);
    Route::delete('/ecommerce/cart/items/{id}', [EcommerceController::class, 'removeCartItem']);

    // Public catalog list
    Route::get('/catalogs', [CatalogController::class, 'publicIndex']);
    
    // Invoice download route (inside public middleware group to ensure session access)
    Route::get('/ecommerce/invoice/{paymentId}/download', [EcommerceController::class, 'downloadInvoice']);
    
    // Removed the new order-based endpoint to maintain backward compatibility
    // Route::get('/ecommerce/orders/{orderNumber}/invoice/download', [EcommerceController::class, 'downloadInvoiceByOrder']);
});

// Customer API routes (protected by auth:api)
Route::middleware(['auth:api', 'role:customer'])->group(function () {
    Route::get('/customer', [CustomerController::class, 'me']);
    Route::delete('/customer', [CustomerController::class, 'destroy']);
    
    // Mobile-friendly Profile Management APIs
    Route::put('/customer/profile', [CustomerController::class, 'updateProfile']); // Form 1 & 3 combined
    
    // Address Management APIs (Form 2)
    Route::get('/customer/addresses', [CustomerController::class, 'getAddresses']);
    Route::post('/customer/address', [CustomerController::class, 'createAddress']);
    Route::put('/customer/address/{id}', [CustomerController::class, 'updateAddress']);
    Route::delete('/customer/address/{id}', [CustomerController::class, 'deleteAddress']);
    Route::get('/customer/address-types', [CustomerController::class, 'getAddressTypes']);
    
    // Customer Profile Photo routes
    Route::post('/customer/profile-photo', [CustomerProfilePhotoController::class, 'upload']);
    Route::get('/customer/profile-photo', [CustomerProfilePhotoController::class, 'get']);
    Route::delete('/customer/profile-photo', [CustomerProfilePhotoController::class, 'delete']);
    
    // Payment Method routes
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods/create', [PaymentMethodController::class, 'create']);
    Route::delete('/payment-methods/{paymentMethodId}', [PaymentMethodController::class, 'destroy']);
    Route::post('/payment-methods/{paymentMethodId}/set-default', [PaymentMethodController::class, 'setDefault']);

        // Checkout (authenticated only)
    Route::get('/ecommerce/checkout/summary', [\App\Http\Controllers\Api\CheckoutController::class, 'summary']);
    Route::post('/ecommerce/checkout/apply-coupon', [\App\Http\Controllers\Api\CheckoutController::class, 'applyCoupon']);
    Route::post('/ecommerce/checkout/place-order', [\App\Http\Controllers\Api\CheckoutController::class, 'placeOrder']);

    // Orders (mobile, authenticated only)
    Route::get('/ecommerce/orders', [OrderController::class, 'index']);
    Route::get('/ecommerce/orders/{orderNumber}', [OrderController::class, 'show']);
    Route::get('/thank-you/{orderNumber}', [OrderController::class, 'thankYou']);
    Route::post('/ecommerce/orders/{orderNumber}/generate-invoice', [OrderController::class, 'generateInvoice']);
    
    // Pets routes
    Route::get('/pets', [PetProfileController::class, 'index'])->name('pets');
    // Route::get('/pets/{id}', [PetProfileController::class, 'show']);
    Route::post('/pets', [PetProfileController::class, 'store'])->name('pets.create');
    Route::post('/pets/{id}', [PetProfileController::class, 'update'])->name('pets.update');
    Route::delete('/pets/{id}', [PetProfileController::class, 'destroy'])->name('pets.destroy');

    // vaccination-records
    Route::post('/vaccination-records', [PetProfileController::class, 'saveVaccinationRecord']);
    Route::post('/vaccination-records/{id}', [PetProfileController::class, 'saveVaccinationRecord']);
    Route::delete('/vaccination-records/{id}', [PetProfileController::class, 'deleteVaccinationRecord']);
    Route::post('/download/vaccination-records/{id}', [PetProfileController::class, 'downloadVaccinationRecord']);
    
    // Blood Test Records
    Route::post('/blood-test-records', [PetProfileController::class, 'saveBloodTest']);
    Route::post('/blood-test-records/{id}', [PetProfileController::class, 'saveBloodTest']);
    Route::delete('/blood-test-records/{id}', [PetProfileController::class, 'deleteBloodTest']);
    Route::post('/download/blood-test-records/{id}', [PetProfileController::class, 'downloadBloodTestRecord']);
    
    // deworming-records
    Route::post('/deworming-records', [PetProfileController::class, 'saveDeworming']);
    Route::post('/deworming-records/{id}', [PetProfileController::class, 'saveDeworming']);
    Route::delete('/deworming-records/{id}', [PetProfileController::class, 'deleteDeworming']);
    Route::post('/download/deworming-records/{id}', [PetProfileController::class, 'downloadDeworming']);
    
    // deworming-records
    Route::post('/medical-history-records', [PetProfileController::class, 'saveMedicalHistoryRecord']);
    Route::post('/medical-history-records/{id}', [PetProfileController::class, 'saveMedicalHistoryRecord']);
    Route::post('/download/medical-history-records/{id}', [PetProfileController::class, 'downloadMedicalHistoryRecord']);
    Route::delete('/medical-history-records/{id}', [PetProfileController::class, 'deleteMedicalHistoryRecord']);

    // dietary-preferences
    Route::post('/dietary-preferences-records', [PetProfileController::class, 'saveDietaryPreferences']);
    Route::post('/dietary-preferences-records/{id}', [PetProfileController::class, 'saveDietaryPreferences']);
    Route::delete('/dietary-preferences-records/{id}', [PetProfileController::class, 'deleteDietaryPreferences']);

    // medication-supplements
    Route::post('/medication-supplements-records', [PetProfileController::class, 'saveMedicationSupplement']);
    Route::post('/medication-supplements-records/{id}', [PetProfileController::class, 'saveMedicationSupplement']);
    Route::delete('/medication-supplements-records/{id}', [PetProfileController::class, 'deleteMedicationSupplement']);

    // Pet setting
    Route::get('/pet-settings', [PetSettingController::class, 'index']);
    Route::get('/sizes', [PetSettingController::class, 'size']);

    // General Setting
    Route::get('/general-settings', [GeneralSettingController::class, 'index']);
    Route::get('/service-settings', [ServiceSettingsController::class, 'index']);

    Route::get('/referrals/signups', [ReferralSignupController::class, 'index'])->name('referral_signups');
    Route::get('/referrals/status', [ReferralSignupController::class, 'referralStatus'])->name('referral_status');

    Route::get('/promotion/wallet', [PromotionWalletController::class, 'wallet'])->name('promo_wallet');
    Route::get('/promotion/coupon/{id}', [PromotionWalletController::class, 'couponDetail'])->name('coupon_detail');
    Route::post('/promotion/add-promo', [PromotionWalletController::class, 'addPromoCode'])->name('add_promo_code');
    Route::post('/promotion/validate-voucher-code', [PromotionWalletController::class, 'validateVoucherCode'])->name('validate_voucher_code');
    Route::post('/promotion/voucher/{id}/increment-usage', [PromotionWalletController::class, 'incrementVoucherUsage'])->name('voucher.increment_usage');

    
});

// Universal Profile API routes (works for all platforms)
Route::middleware(['auth:api'])->group(function () {
    // Profile management
    Route::get('/profile/{userId?}', [ProfileController::class, 'show']);
    Route::put('/profile/{userId?}', [ProfileController::class, 'update']);
    Route::post('/profile', [ProfileController::class, 'store']); // Admin only
    
    // OTP verification for profile updates
    Route::post('/profile/verify-otp', [ProfileController::class, 'verifyOtp']);
    Route::post('/profile/resend-otp', [ProfileController::class, 'resendOtp']);
    
    // OTP verification with secure email/phone updates
    Route::post('/profile/verify-and-update-email', [ProfileController::class, 'verifyAndUpdateEmail']);
    Route::post('/profile/verify-and-update-phone', [ProfileController::class, 'verifyAndUpdatePhone']);
});

// Mobile Profile API routes (Single endpoint approach)
Route::middleware(['auth:api'])->group(function () {
    // Single endpoint to save complete profile to database
    Route::post('/profile/save', [ProfileController::class, 'saveProfile']);
    
    // OTP endpoints for mobile (if needed for verification)
    Route::post('/profile/send-email-otp', [ProfileController::class, 'sendEmailOtp']);
    Route::post('/profile/send-phone-otp', [ProfileController::class, 'sendPhoneOtp']);
    Route::post('/profile/verify-otp', [ProfileController::class, 'verifyOtp']);
    
});

// Address lookup routes (public, no auth required)
Route::post('/address/search', [AddressController::class, 'searchByPostalCode']);
Route::get('/address/status', [AddressController::class, 'status']);

// Health routes (public)
Route::get('/health/storage', [HealthController::class, 'storage']);