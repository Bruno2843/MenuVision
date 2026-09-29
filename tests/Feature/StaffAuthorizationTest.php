<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_guest_cannot_reach_the_admin_panel(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_reach_the_waiter_panel(): void
    {
        $this->get(route('waiter.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_reach_the_kitchen_panel(): void
    {
        $this->get(route('kitchen.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_an_administrator_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard');
    }

    public function test_a_waiter_can_access_the_waiter_panel(): void
    {
        $mozo = User::factory()->waiter()->create();

        $this->actingAs($mozo)
            ->get(route('waiter.dashboard'))
            ->assertOk()
            ->assertViewIs('waiter.dashboard');
    }

    public function test_a_kitchen_staff_member_can_access_the_kitchen_panel(): void
    {
        $cocina = User::factory()->kitchen()->create();

        $this->actingAs($cocina)
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertViewIs('kitchen.dashboard');
    }

    public function test_a_waiter_cannot_reach_the_admin_panel(): void
    {
        $mozo = User::factory()->waiter()->create();

        $this->actingAs($mozo)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('waiter.dashboard'));
    }

    public function test_a_kitchen_staff_member_cannot_reach_the_admin_panel(): void
    {
        $cocina = User::factory()->kitchen()->create();

        $this->actingAs($cocina)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('kitchen.dashboard'));
    }

    public function test_a_waiter_cannot_reach_the_kitchen_panel(): void
    {
        $mozo = User::factory()->waiter()->create();

        $this->actingAs($mozo)
            ->get(route('kitchen.dashboard'))
            ->assertRedirect(route('waiter.dashboard'));
    }

    public function test_a_kitchen_staff_member_cannot_reach_the_waiter_panel(): void
    {
        $cocina = User::factory()->kitchen()->create();

        $this->actingAs($cocina)
            ->get(route('waiter.dashboard'))
            ->assertRedirect(route('kitchen.dashboard'));
    }

    public function test_an_administrator_cannot_reach_the_waiter_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('waiter.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_administrator_cannot_reach_the_kitchen_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('kitchen.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
