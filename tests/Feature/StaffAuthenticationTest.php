<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_an_unauthenticated_user_sees_the_login_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    public function test_an_administrator_can_log_in(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_waiter_can_log_in(): void
    {
        $mozo = User::factory()->waiter()->create();

        $response = $this->post(route('login.store'), [
            'email' => $mozo->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('waiter.dashboard'));
        $this->assertAuthenticatedAs($mozo);
    }

    public function test_a_kitchen_staff_member_can_log_in(): void
    {
        $cocina = User::factory()->kitchen()->create();

        $response = $this->post(route('login.store'), [
            'email' => $cocina->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('kitchen.dashboard'));
        $this->assertAuthenticatedAs($cocina);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->post(route('login.store'), [
            'email' => 'admin@menuvision.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_login_failure_is_reported_in_spanish(): void
    {
        $this->post(route('login.store'), [
            'email' => 'admin@menuvision.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'Estas credenciales no coinciden con nuestros registros.',
        ]);
    }

    public function test_an_authenticated_user_is_redirected_away_from_the_login_form(): void
    {
        $mozo = User::factory()->waiter()->create();

        $this->actingAs($mozo)
            ->get(route('login'))
            ->assertRedirect(route('waiter.dashboard'));
    }

    public function test_logout_ends_the_session(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
