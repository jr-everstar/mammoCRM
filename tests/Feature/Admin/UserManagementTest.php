<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passkeys\Passkey;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_use_user_management_pages(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['name' => 'Practical User']);
        $user->assignRole(Role::firstOrCreate(['name' => 'sales']));

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Practical User');
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk()->assertSee('新增用戶');
        $this->actingAs($admin)->get(route('admin.users.show', $user))->assertOk()->assertSee('帳戶資料');
        $this->actingAs($admin)->get(route('admin.users.edit', $user))->assertOk()->assertSee('編輯用戶');
    }

    public function test_admin_can_create_microsoft_only_user_without_a_password(): void
    {
        Notification::fake();

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Entra User',
                'email' => 'entra@example.com',
                'role' => 'sales',
                'must_use_microsoft_login' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('invitation_link');

        $this->assertDatabaseHas('users', [
            'email' => 'entra@example.com',
            'status' => 'active',
            'must_use_microsoft_login' => true,
        ]);

        $user = User::where('email', 'entra@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('sales'));
        Notification::assertSentTo($user, UserInvitation::class);
    }

    public function test_admin_can_create_standard_user_with_one_role_and_invitation_without_password(): void
    {
        Notification::fake();

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Invited Staff',
                'email' => 'invited@example.com',
                'role' => 'staff',
            ])
            ->assertRedirect()
            ->assertSessionHas('invitation_link');

        $user = User::where('email', 'invited@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('staff'));
        $this->assertSame('active', $user->status);
        $this->assertFalse($user->hasRole('sales'));
        $this->assertStringContainsString('/reset-password/', $response->baseResponse->getSession()->get('invitation_link'));
        Notification::assertSentTo($user, UserInvitation::class);
    }

    public function test_enforcing_microsoft_login_removes_existing_passkeys(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'sales']));

        $user->passkeys()->create([
            'name' => 'Laptop',
            'credential_id' => 'credential-id',
            'credential' => ['id' => 'credential-id'],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'sales',
                'status' => 'active',
                'must_use_microsoft_login' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($user->refresh()->mustUseMicrosoftLogin());
        $this->assertSame(0, Passkey::where('user_id', $user->id)->count());
    }

    public function test_admin_can_resend_invitation_and_copy_link(): void
    {
        Notification::fake();

        $admin = $this->adminUser();
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'sales']));

        $this->actingAs($admin)
            ->post(route('admin.users.invitation', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('invitation_link');

        Notification::assertSentTo($user, UserInvitation::class);
    }

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'staff']);

        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin']));

        return $admin;
    }
}
