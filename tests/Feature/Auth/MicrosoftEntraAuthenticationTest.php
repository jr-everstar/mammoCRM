<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class MicrosoftEntraAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_with_a_matching_microsoft_entra_email(): void
    {
        $user = User::factory()->create([
            'email' => 'sales@example.com',
            'status' => 'active',
            'must_use_microsoft_login' => true,
        ]);

        $this->mockMicrosoftUser('sales@example.com');

        $response = $this->get(route('auth.microsoft.callback'));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_microsoft_redirect_starts_on_the_configured_callback_host(): void
    {
        Config::set('services.microsoft.redirect', 'http://localhost:8000/auth/microsoft/callback');

        $this->get('http://127.0.0.1:8000/auth/microsoft/redirect')
            ->assertRedirect('http://localhost:8000/auth/microsoft/redirect');
    }

    public function test_microsoft_entra_email_matching_is_case_insensitive(): void
    {
        $user = User::factory()->create([
            'email' => 'Sales@Example.com',
            'status' => 'active',
        ]);

        $this->mockMicrosoftUser('sales@example.com');

        $this->get(route('auth.microsoft.callback'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_users_can_not_authenticate_with_microsoft_entra(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'status' => 'inactive',
        ]);

        $this->mockMicrosoftUser('inactive@example.com');

        $this->get(route('auth.microsoft.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unlinked_microsoft_entra_accounts_can_not_authenticate(): void
    {
        $this->mockMicrosoftUser('unknown@example.com');

        $this->get(route('auth.microsoft.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function mockMicrosoftUser(string $email): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andReturn((new SocialiteUser)->setRaw([
                'mail' => $email,
                'userPrincipalName' => $email,
            ])->map([
                'id' => 'entra-user-id',
                'name' => 'Microsoft User',
                'email' => $email,
            ]));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('microsoft')
            ->andReturn($provider);
    }
}
