<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_shown_when_an_admin_exists(): void
    {
        Admin::factory()->create();

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Admin login');
    }

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        Admin::factory()->create();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_authenticate(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_cannot_authenticate_with_invalid_password(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_authenticated_admin_can_view_dashboard(): void
    {
        $admin = Admin::factory()->create([
            'name' => 'Panel Admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Welcome, Panel Admin');
    }

    public function test_authenticated_admin_can_logout(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }

    public function test_authenticated_admin_is_redirected_away_from_login(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
