<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create customer role
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    public function test_guest_can_access_login_page()
    {
        $response = $this->get('/customer/login');
        $response->assertStatus(200);
    }

    public function test_guest_can_access_register_page()
    {
        $response = $this->get('/customer/register');
        $response->assertStatus(200);
    }

    public function test_guest_can_access_home_page()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_authenticated_customer_cannot_access_login_page()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/customer/login');
        $response->assertRedirect('/customer/dashboard');
    }

    public function test_authenticated_customer_cannot_access_register_page()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/customer/register');
        $response->assertRedirect('/customer/dashboard');
    }

    public function test_authenticated_customer_redirected_from_home_page()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/customer/dashboard');
    }

    public function test_guest_cannot_access_customer_dashboard()
    {
        $response = $this->get('/customer/dashboard');
        $response->assertRedirect('/customer/login');
    }

    public function test_authenticated_customer_can_access_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/customer/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_user_cannot_access_customer_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/customer/dashboard');
        $response->assertRedirect('/customer/login');
    }

    public function test_authenticated_customer_redirected_from_general_pages()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/terms-of-service');
        $response->assertRedirect('/customer/dashboard');

        $response = $this->actingAs($user)->get('/about-us');
        $response->assertRedirect('/customer/dashboard');
    }
}