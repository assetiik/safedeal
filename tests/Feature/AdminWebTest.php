<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_admin_credentials(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@safedeal.test',
            'password' => 'admin',
        ]);

        $this->post(route('admin.login.submit'), [
            'login' => 'admin',
            'password' => 'admin',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'Password123',
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Админ-панель');
    }

    public function test_customer_cannot_open_admin(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_sections_render(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        foreach ([
            '/admin/deals',
            '/admin/users',
            '/admin/disputes',
            '/admin/payments',
            '/admin/documents',
            '/admin/audit',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_admin_can_block_and_unblock_user_from_list(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->from('/admin/users')
            ->post(route('admin.users.block', $customer))
            ->assertRedirect('/admin/users');

        $this->assertTrue($customer->fresh()->status->value === 'blocked');

        $this->actingAs($admin)
            ->from('/admin/users')
            ->post(route('admin.users.unblock', $customer))
            ->assertRedirect('/admin/users');

        $this->assertTrue($customer->fresh()->status->value === 'active');
    }
}
