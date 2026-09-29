<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_guest_sees_both_access_modules_on_the_home_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('¿Sos cliente?')
            ->assertSee('Escanear QR')
            ->assertSee('¿Sos parte del personal?')
            ->assertSee('Iniciar sesión del personal');
    }

    public function test_an_authenticated_administrator_is_sent_to_the_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_authenticated_waiter_is_sent_to_the_waiter_panel(): void
    {
        $mozo = User::factory()->waiter()->create();

        $this->actingAs($mozo)
            ->get(route('home'))
            ->assertRedirect(route('waiter.dashboard'));
    }

    public function test_an_authenticated_kitchen_staff_member_is_sent_to_the_kitchen_panel(): void
    {
        $cocina = User::factory()->kitchen()->create();

        $this->actingAs($cocina)
            ->get(route('home'))
            ->assertRedirect(route('kitchen.dashboard'));
    }
}
