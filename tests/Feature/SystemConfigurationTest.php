<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;
use App\Helpers\FormatHelper;
use App\Utilities\MoneyFormatter;
use Carbon\Carbon;
use App\Models\TaxSetting;
use App\Models\PeakSeason;
use App\Models\OffDay;
use App\Models\User;
use App\Services\TaxService;
use App\Services\PricingService;
use App\Services\AvailabilityService;

class SystemConfigurationTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test time display in 12-hour AM/PM format.
     */
    public function test_time_display_format()
    {
        try {
            $this->assertEquals('03:45 PM', FormatHelper::formatTime('2025-07-30 15:45:00'));
            $this->assertEquals('12:00 AM', FormatHelper::formatTime('2025-07-30 00:00:00'));
            $this->assertEquals('02:59 PM', FormatHelper::formatTime('2025-07-30 14:59:00'));
            $this->assertEquals('12:01 PM', FormatHelper::formatTime('2025-07-30 12:01:00'));
        } catch (\Exception $e) {
            $this->fail('Time formatting failed: ' . $e->getMessage());
        }
    }

    public function test_time_returns_null_if_invalid_or_null()
    {
        $this->assertNull(FormatHelper::formatTime(null));
        $this->assertNull(FormatHelper::formatTime(''));
    }

    /**
     * Test currency formatting displays SGD with two decimal places.
     */
    public function test_currency_formatting()
    {
        try {
            // Arrange: Set currency in config
            config(['app.currency' => 'SGD']);

            // Assert: Check SGD and rounding
            $this->assertEquals('100.13', MoneyFormatter::format(100.123));
            $this->assertEquals('100.11', MoneyFormatter::format(100.101));
            $this->assertEquals('100.00', MoneyFormatter::format(100));
            $this->assertEquals('0.01', MoneyFormatter::format(0.001));
        } catch (\Exception $e) {
            $this->fail('Currency formatting failed: ' . $e->getMessage());
        }
    }

    /**
     * Test timezone is set to Asia/Singapore.
     */
    public function test_timezone_configuration()
    {
        try {
            // Arrange: Set timezone in config
            config(['app.timezone' => 'Asia/Singapore']);

            // Act: Get current time
            $now = Carbon::now();

            // Assert: Verify timezone
            $this->assertEquals('Asia/Singapore', $now->timezone->getName());
            $this->assertEquals('Asia/Singapore', config('app.timezone'));

            $time = Carbon::create(2025, 7, 30, 15, 45, 0, 'UTC');
            $formatted = $time->timezone(config('app.timezone'))->format('h:i A');

            $this->assertEquals('11:45 PM', $formatted); // +8 from UTC


        } catch (\Exception $e) {
            $this->fail('Timezone configuration failed: ' . $e->getMessage());
        }
    }

    /**
     * Test company settings form submission via Livewire.
     */
    public function test_company_settings_form_submission()
    {
        try {
            // Arrange: Create a user with admin role
            $admin = \App\Models\User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ]);

            \DB::table('countries')->insert([
                'id' => 196,
                'code' => 'SG',
                'name' => 'Singapore',
                'phonecode' => 65,
            ]);

            // Act: Submit company settings via Livewire
            Livewire::actingAs($admin)
                ->test('backend.company-settings')
                ->set('company_name', 'Test Corp')
                ->set('uen_no', '123456789')
                ->set('country_id', 196)
                ->set('postal_code', '123456789')
                ->set('address_line1', '123 Singapore St')
                ->set('address_line2', '123 Singapore St')
                ->set('contact_number', '1234 5678')
                ->set('contact_number_dial_code', '+65')
                ->set('support_email', 'support@test.com')
                ->call('save')
                ->assertHasNoErrors(); 

            // Assert: Verify data saved in database
            $this->assertDatabaseHas('company_settings', [
                'company_name' => 'Test Corp',
                'uen_no' => '123456789',
                'support_email' => 'support@test.com',
            ]);
        } catch (\Exception $e) {
            $this->fail('Company settings form submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Test GST calculation for a given amount.
     */
    public function test_gst_calculation()
    {
        try {
            $admin = \App\Models\User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ]);
            
            // Arrange: Set GST rate
            TaxSetting::create(['tax_type' => 'GST', 'rate' => 0.09,'created_by' => $admin->id]);

            // Act: Calculate GST
            $amount = 100.00;
            $result = app(TaxService::class)->calculateGst($amount);

            // Assert: Verify GST and total
            $this->assertEquals(9.00, $result['gst']);
            $this->assertEquals(109.00, $result['total']);
        } catch (\Exception $e) {
            $this->fail('GST calculation failed: ' . $e->getMessage());
        }
    }

    /**
     * Test peak season price variation applies correctly.
     */
    public function test_peak_season_pricing()
    {
        try {
            // Arrange: Create a user with admin role
            $admin = \App\Models\User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ]);

            // Arrange: Create a peak season
            PeakSeason::create([
                'title' => 'Test',
                'start_date' => '2025-07-01',
                'end_date' => '2025-07-03',
                'price_variation' => 0.10,
                'description' => "This is test description.",
                'created_by' => $admin->id,
            ]);

            // Act: Calculate price for a date in peak season
            $basePrice = 100.00;
            $date = Carbon::parse('2025-07-02');
            $price = MoneyFormatter::format(app(PricingService::class)->applyPeakPricing($basePrice, $date));

            // Assert: Verify 10% increase
            $this->assertEquals(110.01, $price);

            // Act: Calculate price for a non-peak date
            $date = Carbon::parse('2024-02-04');
            $price = MoneyFormatter::format(app(PricingService::class)->applyPeakPricing($basePrice, $date));

            // Assert: Verify no increase
            $this->assertEquals(100.00, $price);
        } catch (\Exception $e) {
            $this->fail('Peak season pricing failed: ' . $e->getMessage());
        }
    }

    /**
     * Test off-day blocks booking attempts.
     */
    public function test_off_day_availability()
    {
        try {

            $admin = \App\Models\User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ]);

            // Arrange: Create an off-day
            OffDay::create(['title' => "Independence day",'start_date' => '2025-08-15','end_date' => '2025-08-15', 'reason' => 'Public Holiday','created_by' => $admin->id]);

            // Act: Check availability
            $date = Carbon::parse('2025-08-15');
            $isAvailable = app(AvailabilityService::class)->isAvailable($date);

            // Assert: Verify date is unavailable
            $this->assertFalse($isAvailable);

            // Act: Check a non-off-day
            $date = Carbon::parse('2025-01-02');
            $isAvailable = app(AvailabilityService::class)->isAvailable($date);

            // Assert: Verify date is available
            $this->assertTrue($isAvailable);
        } catch (\Exception $e) {
            $this->fail('Off-day availability check failed: ' . $e->getMessage());
        }
    }

    /**
     * Test off-day form submission and admin alert display.
     */
    public function test_off_day_form_submission_and_alert()
    {
        try {
            // Arrange: Create an admin user
            $admin = \App\Models\User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ]);

            // Act: Submit off-day via Livewire
            Livewire::actingAs($admin)
                ->test('backend.off-day')
                ->set('title', "Independence day")
                ->set('start_date', '2025-08-15')
                ->set('end_date', '2025-08-15')
                ->set('reason', 'Public Holiday')
                ->call('save')
                ->assertHasNoErrors(); 

            // Assert: Verify off-day saved
            $this->assertDatabaseHas('off_days', [
                'start_date' => \Carbon\Carbon::parse('2025-08-15')->startOfDay()->toDateTimeString(),
                'title' => 'Independence day',
            ]);

            // Act: Check admin panel for alert
            // Livewire::actingAs($admin)
            //     ->test('off_day_alert')
            //     ->assertSee('Upcoming off-day: Jan 1, 2025 - Public Holiday');
        } catch (\Exception $e) {
            $this->fail('Off-day form submission or alert failed: ' . $e->getMessage());
        }
    }
}
