<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_page_is_shown_when_no_admin_exists(): void
    {
        $this->get(route('admin.setup'))
            ->assertOk()
            ->assertSee('Create the first admin');
    }

    public function test_dashboard_redirects_to_setup_when_no_admin_exists(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.setup'));
    }

    public function test_login_redirects_to_setup_when_no_admin_exists(): void
    {
        $this->get(route('admin.login'))
            ->assertRedirect(route('admin.setup'));
    }

    public function test_first_admin_can_be_created_during_setup(): void
    {
        $response = $this->post(route('admin.setup.store'), [
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('admins', [
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
        ]);

        $this->assertAuthenticatedAs(Admin::query()->first(), 'admin');
    }

    public function test_setup_requires_valid_input(): void
    {
        $this->from(route('admin.setup'))
            ->post(route('admin.setup.store'), [])
            ->assertRedirect(route('admin.setup'))
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_setup_is_blocked_once_an_admin_exists(): void
    {
        Admin::factory()->create();

        $this->get(route('admin.setup'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.setup.store'), [
            'name' => 'Another Admin',
            'email' => 'another@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('admins', 1);
        $this->assertDatabaseMissing('admins', [
            'email' => 'another@example.com',
        ]);
    }
}
