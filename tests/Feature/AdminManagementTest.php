<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_management(): void
    {
        Admin::factory()->create();

        $this->get(route('admin.admins.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_admin_can_view_admins_index(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.admins.index'))
            ->assertOk()
            ->assertSee('Admins')
            ->assertSee($admin->email);
    }

    public function test_authenticated_admin_can_create_another_admin(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.admins.store'), [
                'name' => 'Second Admin',
                'email' => 'second@example.com',
                'password' => 'Password1',
                'password_confirmation' => 'Password1',
            ]);

        $response->assertRedirect(route('admin.admins.index'));

        $this->assertDatabaseHas('admins', [
            'name' => 'Second Admin',
            'email' => 'second@example.com',
        ]);
    }

    public function test_create_admin_requires_valid_input(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.admins.create'))
            ->post(route('admin.admins.store'), [])
            ->assertRedirect(route('admin.admins.create'))
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_authenticated_admin_can_update_another_admin(): void
    {
        $admin = Admin::factory()->create();
        $otherAdmin = Admin::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.admins.update', $otherAdmin), [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);

        $response->assertRedirect(route('admin.admins.index'));

        $this->assertDatabaseHas('admins', [
            'id' => $otherAdmin->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_authenticated_admin_can_delete_another_admin(): void
    {
        $admin = Admin::factory()->create();
        $otherAdmin = Admin::factory()->create([
            'email' => 'delete-me@example.com',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.admins.destroy', $otherAdmin));

        $response->assertRedirect(route('admin.admins.index'));

        $this->assertDatabaseMissing('admins', [
            'email' => 'delete-me@example.com',
        ]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = Admin::factory()->create();
        Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.admins.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
        ]);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.admins.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseCount('admins', 1);
    }
}
