<?php

use Illuminate\Support\Facades\Route;

use Livewire\Volt\Volt;
use App\Livewire\Frontend\CustomerController;
use App\Livewire\Frontend\CustomerRegisterController;
use App\Livewire\Frontend\CustomerProfileController;
use App\Services\OneMapService;
use Illuminate\Support\Facades\Auth;

use App\Livewire\Frontend\ReferralDashboard;
use App\Livewire\Frontend\PromoWallet;
use App\Livewire\Frontend\CustomerPaymentMethodController;
use App\Livewire\Frontend\Pet as PetCustomer;
use App\Livewire\Backend\Pet as PetAdmin;
use App\Livewire\Backend\VaccinationRecord;
use App\Livewire\Backend\BloodTestRecord;
use App\Livewire\Backend\DewormingRecord;
use App\Livewire\Backend\MedicalHistoryRecord;
use App\Livewire\Backend\DietaryPreferences;
use App\Livewire\Backend\MedicationSupplement;
use App\Livewire\Backend\Species;
use App\Livewire\Backend\Breed;
use App\Livewire\Backend\Vaccination;
use App\Livewire\Backend\BloodTest;
use App\Livewire\Backend\Size;
use App\Livewire\Backend\PetTag;
use App\Livewire\Backend\VaccineExemption;
use App\Livewire\Backend\RevaluationWorkflow;
use App\Livewire\Frontend\AddPaymentMethod;
use App\Livewire\Frontend\Ecommerce\Landing as ShopLanding;
use App\Livewire\Frontend\Ecommerce\Listing as ShopListing;
use App\Livewire\Frontend\Ecommerce\ProductShow as ShopProductShow;
use App\Livewire\Backend\Room\CancelSetting;
use App\Livewire\Backend\SpeciesSizeSetting;
use App\Livewire\Backend\Room\PetSizeLimitSetting;
use App\Livewire\Frontend\Room\Landing as RoomLanding;
use App\Livewire\Frontend\Room\Details as RoomDetails;
use App\Livewire\Backend\Room\PeakSeason;
use App\Livewire\Backend\Room\OffDay;
use App\Livewire\Backend\Room\RoomPriceOption;
use App\Livewire\Backend\Room\RoomType;
use App\Livewire\Backend\Room\Room;
use App\Livewire\Backend\Room\RoomWeekend;
use App\Livewire\Backend\Room\RoomBooking;
// The welcome page
Route::get('/', function () {
    // If user is authenticated and has customer role, redirect to dashboard
    if (Auth::check() && Auth::user()->hasRole('customer')) {
        return redirect()->route('customer.dashboard');
    }
    return view('welcome');
})->name('home');

// Public shop routes
Route::prefix('shop')->group(function(){
    Route::get('/', function () {
        return view('frontend.ecommerce.landing');
    })->name('shop.home');
    Route::get('/products', function () {
        return view('frontend.ecommerce.listing');
    })->name('shop.list');
    Route::get('/product/{slug}', function ($slug) {
        return view('frontend.ecommerce.product-show', ['slug' => $slug]);
    })->name('shop.product');
    Route::get('/cart', function () {
        return view('frontend.ecommerce.cart');
    })->name('shop.cart');
    Route::get('/checkout', function () {
        return view('frontend.ecommerce.checkout');
    })->middleware('auth.customer')->name('shop.checkout');
    Route::get('/thank-you/{orderNumber}', function ($orderNumber) {
        return view('frontend.ecommerce.thank-you', ['orderNumber' => $orderNumber]);
    })->middleware('auth.customer')->name('shop.thank-you');
});

// Public room routes
Route::prefix('rooms')->group(function(){
    Route::get('/', function () {
        return view('frontend.room.landing');
    })->name('room.home');
    Route::get('/{slug}', function ($slug) {
        return view('frontend.room.details', ['slug' => $slug]);
    })->name('room.details');
});


/*Route::prefix('rooms')->group(function(){
    Route::get('/', RoomLanding::class)->name('room.home');
    Route::get('/{slug}', RoomDetails::class)->name('room.details');
});
*/

/* frontend routes */
// Non Authenticated Customer routes
Route::prefix('customer')->namespace('App\Livewire\Frontend')->group(function () {
    // Guest-only routes (redirect authenticated customers to dashboard)
        Route::middleware(['guest.only'])->group(function () {
            Route::get('/login', function() {
                return view('welcome')->with('openModal', 'login');
            })->name('customer.login');
        Route::post('/send-otp', [CustomerController::class, 'sendOtp'])->name('customer.sendOtp');
        Route::get('/verify-otp', [CustomerController::class, 'showOtpForm'])->name('customer.showOtpForm');
        Route::post('/verify-otp', [CustomerController::class, 'verifyOtp'])->name('customer.verifyOtp');
        Route::post('/cancel-otp', [CustomerController::class, 'cancelOtp'])->name('customer.cancelOtp');
        Route::get('/success', [CustomerController::class, 'showSuccess'])->name('customer.success'); // Login success, might redirect to dashboard later

        // Customer Registration routes
        Route::get('/register', function() {
            return view('welcome')->with('openModal', 'register');
        })->name('customer.register.form');
        Route::post('/register', 'CustomerRegisterController@register')->name('customer.register');

        Route::get('/register/verify-otp', [CustomerRegisterController::class, 'showOtpForm'])->name('customer.register.showOtpForm');
        Route::post('/register/verify-otp', [CustomerRegisterController::class, 'verifyOtp'])->name('customer.register.verifyOtp');
        Route::post('/register/resend-phone-otp', [CustomerRegisterController::class, 'resendPhoneOtp'])->name('customer.register.resendPhoneOtp');
        Route::get('/register/success', [CustomerRegisterController::class, 'showPhoneSuccess'])->name('customer.register.phone.success');

        // Routes for attaching/verifying email during registration (Session-based, no auth required)
        Route::get('/register/attach-email', [CustomerRegisterController::class, 'showAttachEmailForm'])->name('customer.register.attachEmailForm');
        Route::post('/register/attach-email', [CustomerRegisterController::class, 'attachEmail'])->name('customer.register.attachEmail');
        Route::get('/register/verify-email-otp', [CustomerRegisterController::class, 'showEmailOtpForm'])->name('customer.register.showEmailOtpForm');
        Route::post('/register/verify-email-otp', [CustomerRegisterController::class, 'verifyEmailOtp'])->name('customer.register.verifyEmailOtp');
        Route::post('/register/resend-email-otp', [CustomerRegisterController::class, 'resendEmailOtp'])->name('customer.register.resendEmailOtp');
        Route::get('/register/email-success', [CustomerRegisterController::class, 'showEmailSuccess'])->name('customer.register.email.success');
    });


    // Authenticated customer routes
    Route::middleware(['auth.customer'])->group(function () {
        Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('customer.dashboard');
        Route::get('/profile', [CustomerProfileController::class, 'showProfile'])->name('customer.profile');
        Route::post('/profile', [CustomerProfileController::class, 'updateProfile'])->name('customer.profile.update');
        
        // Multi-step profile routes 
        Route::get('/profile/multi-step', function() { return redirect()->route('customer.profile.step', 1); })->name('customer.profile.multistep');
        Route::get('/profile/step/{step}', [CustomerProfileController::class, 'showStep'])->name('customer.profile.step')->where('step', '[1-3]');
        Route::post('/profile/step/{step}/save', [CustomerProfileController::class, 'saveStep'])->name('customer.profile.step.save')->where('step', '[1-3]');
        
        // Address management routes
        Route::get('/profile/address/{address}/edit', [CustomerProfileController::class, 'getAddress'])->name('customer.profile.address.edit');
        Route::delete('/profile/address/{address}', [CustomerProfileController::class, 'deleteAddress'])->name('customer.profile.address.delete');
        
        Route::post('/logout', [CustomerController::class, 'logout'])->name('customer.logout');
        // Account deletion route
        Route::delete('/delete-account', [CustomerController::class, 'deleteAccount'])->name('customer.delete-account');
        
        Route::get('/payment-methods', [CustomerPaymentMethodController::class, 'showPaymentMethods'])->name('customer.payment-methods');
        Route::get('/payment-methods/add', function () {
            return view('frontend.customer.add-payment-method');
        })->name('customer.payment-methods.add');
        Route::post('/payment-methods/create', [CustomerController::class, 'createPaymentMethod'])->name('customer.payment-methods.create');
      
        // Order History routes
        Route::get('/order-history', function () {
            return view('frontend.ecommerce.order-history');
        })->name('customer.order-history');
        Route::get('/order/{orderNumber}', function ($orderNumber) {
            return view('frontend.ecommerce.order-detail', ['orderNumber' => $orderNumber]);
        })->name('customer.order-detail');
        
        // Invoice download route for web requests
        Route::get('/order/{orderNumber}/invoice/{paymentId}/download', [App\Http\Controllers\Web\InvoiceController::class, 'downloadInvoice'])
            ->name('customer.invoice.download');

       // Pets
        Route::get('/pets', [PetCustomer::class, 'index'])->name('customer.pets');
        Route::get('/vaccination-records/{id}/{customer_id}', [VaccinationRecord::class,'index'])->name('customer.vaccination-records');
        Route::get('/blood-test-records/{id}/{customer_id}', [BloodTestRecord::class,'index'])->name('customer.blood-test-records');
        Route::get('/deworming-records/{id}/{customer_id}', [DewormingRecord::class,'index'])->name('customer.deworming-records');
        Route::get('/medical-history-records/{id}/{customer_id}', [MedicalHistoryRecord::class,'index'])->name('customer.medical-history-records');
        Route::get('/dietary-preferences/{id}/{customer_id}', [DietaryPreferences::class,'index'])->name('customer.dietary-preferences');
        Route::get('medication-supplements/{id}/{customer_id}', [MedicationSupplement::class,'index'])->name('customer.medication-supplements');

        Route::get('my-referrals', [ReferralDashboard::class,'index'])->name('my-referrals');
        Route::get('promo-wallet', [PromoWallet::class,'index'])->name('promo-wallet');
    });
});
/* Backend routes */

// Admin dashboard and routes - only accessible to admin users
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function() {
        // Redirect customers to their dashboard
        if (Auth::user()->hasRole('customer')) {
            return redirect()->route('customer.dashboard');
        }
        // Only allow admin users to access admin dashboard
        if (!Auth::user()->hasRole('admin')) {
            return redirect()->route('home')->with('error', 'Access denied.');
        }
        return view('dashboard');
    })->name('dashboard');
});

Route::namespace('App\Livewire\Backend')->prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Route::get('/customers', 'Customer@index')->name('customers');

    Route::get('/product-management', \App\Livewire\Backend\ProductManagement::class)->name('product-management');
        
    // E-commerce: Order Management
    Route::get('/order-management', \App\Livewire\Backend\OrderManagement::class)->name('order-management');
    // Use order_number for detail URLs
    Route::get('/order-management/{order:order_number}', \App\Livewire\Backend\OrderDetails::class)->name('order-details');
   
    // E-commerce: Shipping Management
    Route::get('/shipping-management', \App\Livewire\Backend\ShippingManagement::class)->name('shipping-management');
    
    // E-commerce: Inventory Restock
    Route::get('/inventory-restock', \App\Livewire\Backend\InventoryRestock::class)->name('inventory-restock');
    
    Route::get('/ecommerce-settings', \App\Livewire\Backend\EcommerceSettings::class)->name('ecommerce-settings');

    Route::get('/role-settings', 'RoleSetting@index')->name('role-settings');
    Route::get('/campaigns', 'Campaign@index')->name('campaigns');
   
    Route::get('/rooms', Room::class)->name('rooms');
    Route::get('/room-types', RoomType::class)->name('room-types');
    Route::get('/room-bookings', RoomBooking::class)->name('room-bookings');
    Route::get('/pet-size-limit-settings', [PetSizeLimitSetting::class, 'index'])->name('pet-size-limit-settings');
    Route::get('/room-peak-seasons', [PeakSeason::class, 'index'])->name('room-peak-seasons');
    Route::get('/room-off-days', [OffDay::class, 'index'])->name('room-off-days');
    Route::get('/room-price-options', [RoomPriceOption::class, 'index'])->name('room-price-options');
    Route::get('/room-cancel-setting', [CancelSetting::class, 'index'])->name('room-cancel-setting');
    Route::get('/room-weekend', [RoomWeekend::class, 'index'])->name('room-weekend');
    Route::get('/system-settings', 'SystemSetting@index')->name('system-settings');
    Route::get('/company-settings', 'CompanySettings@index')->name('company-settings');
    Route::get('/operational-hours', 'OperationalHours@index')->name('operational-hours');
    Route::get('/tax-settings', 'TaxSetting@index')->name('tax-settings');
    
  
    Route::get('species', [Species::class,'index'])->name('admin.species');
    Route::get('breeds', [Breed::class,'index'])->name('admin.breeds');
    Route::get('/vaccination', [Vaccination::class,'index'])->name('admin.vaccination');
    Route::get('/blood-tests', [BloodTest::class,'index'])->name('admin.blood-tests');
    Route::get('/sizes', [Size::class,'index'])->name('admin.sizes');
    Route::get('/pet-tags', [PetTag::class,'index'])->name('admin.pet-tags');
    Route::get('/vaccine-exemptions', [VaccineExemption::class,'index'])->name('admin.vaccine-exemptions');
    Route::get('/revaluation-workflow', [RevaluationWorkflow::class,'index'])->name('admin.revaluation-workflow');

    // Route::get('/pets', 'PetAdmin@index')->name('pets-admin');
    Route::get('/pets', [PetAdmin::class, 'index'])->name('admin.pets');
    Route::get('/vaccination-records/{id}/{customer_id}', [VaccinationRecord::class,'index'])->name('admin.vaccination-records');
    Route::get('/blood-test-records/{id}/{customer_id}', [BloodTestRecord::class,'index'])->name('admin.blood-test-records');
    Route::get('/deworming-records/{id}/{customer_id}', [DewormingRecord::class,'index'])->name('admin.deworming-records');
    Route::get('/medical-history-records/{id}/{customer_id}', [MedicalHistoryRecord::class,'index'])->name('admin.medical-history-records');
    Route::get('/dietary-preferences/{id}/{customer_id}', [DietaryPreferences::class,'index'])->name('admin.dietary-preferences');
    Route::get('medication-supplements/{id}/{customer_id}', [MedicationSupplement::class,'index'])->name('admin.medication-supplements');
    
    Route::get('size-managements/{id}/{customer_id}', 'SizeManagement@index')->name('admin.size-managements');
    Route::get('temperament-health-evaluations/{id}/{customer_id}', 'TemperamentHealthEvaluation@index')->name('admin.temperament-health-evaluations');

    Route::get('promotion/marketingcampaign', 'MarketingCampaign@index')->name('admin.marketingcampaign');
    Route::get('promotion/referralpromotion', 'ReferralPromotion@index')->name('admin.referralpromotion');
    Route::get('promotion/voucher', 'Voucher@index')->name('admin.voucher');

    // Service Setting
    Route::get('service-settings/category', 'ServiceCategory@index')->name('admin.service-category');
    Route::get('service-settings/subcategory', 'ServiceSubcategory@index')->name('admin.service-subcategory');
    Route::get('service-settings/pool-settings', 'PoolSetting@index')->name('admin.pool-settings');
    Route::get('service-settings/advance-duration', 'AdvanceDuration@index')->name('admin.advance-duration');
    Route::get('service-settings/cancellation-refund', 'CancellationRefund@index')->name('admin.cancellation-refund');
    Route::get('service-settings/booking-slots', 'BookingSlots@index')->name('admin.booking-slots');
    Route::get('service-settings/service-pricing-attributes', 'ServicePricingAttribute@index')->name('admin.service-pricing-attributes');

    Route::get('service-settings/peak-seasons', 'PeakSeason@index')->name('peak-season');
    Route::get('service-settings/off-days', 'OffDay@index')->name('off-days');
    Route::get('service-settings/booking-status-settings', 'BookingStatusSetting@index')->name('admin.booking-status-settings');
    Route::get('service-settings/service-types', 'ServiceType@index')->name('admin.service-types');

    Route::get('services', 'Service@index')->name('admin.services');

});

require __DIR__.'/auth.php';
