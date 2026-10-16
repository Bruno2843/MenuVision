<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every admin screen is behind authentication and the administrator role, and a
 * deactivated account cannot use any of them.
 */
class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * A read-only screen from each section of the panel.
     *
     * @return array<string, array{0: string}>
     */
    public static function paginas(): array
    {
        return [
            'panel' => ['admin.dashboard'],
            'categorías' => ['admin.categories.index'],
            'platos' => ['admin.dishes.index'],
            'ingredientes' => ['admin.ingredients.index'],
            'mesas' => ['admin.tables.index'],
            'pedidos' => ['admin.orders.index'],
            'usuarios' => ['admin.users.index'],
        ];
    }

    #[DataProvider('paginas')]
    public function test_a_guest_is_sent_to_the_login_page(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    #[DataProvider('paginas')]
    public function test_a_waiter_is_sent_to_their_own_dashboard(string $route): void
    {
        $this->actingAs(User::factory()->waiter()->create())
            ->get(route($route))
            ->assertRedirect(route('waiter.dashboard'));
    }

    #[DataProvider('paginas')]
    public function test_the_kitchen_is_sent_to_their_own_dashboard(string $route): void
    {
        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route($route))
            ->assertRedirect(route('kitchen.dashboard'));
    }

    #[DataProvider('paginas')]
    public function test_an_administrator_reaches_every_screen(string $route): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route($route))
            ->assertOk();
    }

    public function test_a_deactivated_administrator_is_signed_out_instead_of_being_redirected(): void
    {
        $admin = User::factory()->admin()->deactivated()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_deactivated_user_cannot_sign_in(): void
    {
        $user = User::factory()->waiter()->deactivated()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_wrong_password_is_reported_the_same_way_for_a_deactivated_account(): void
    {
        $user = User::factory()->waiter()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_waiter_cannot_write_through_any_admin_endpoint(): void
    {
        $mozo = User::factory()->waiter()->create();
        $categoria = Category::factory()->create();
        $plato = Dish::factory()->for($categoria)->create();
        $ingrediente = Ingredient::factory()->create();
        $mesa = Table::factory()->create();
        // The order reuses the table above: OrderFactory would create one of its
        // own otherwise, and the table count would stop proving anything.
        $pedido = Order::factory()->for($mesa)->create();
        $usuario = User::factory()->waiter()->create();

        $this->actingAs($mozo)->post(route('admin.categories.store'), [
            'nombre' => 'Truco', 'descripcion' => null, 'orden' => 1,
        ])->assertRedirect(route('waiter.dashboard'));

        $this->actingAs($mozo)->post(route('admin.ingredients.store'), [
            'nombre' => 'Truco',
        ])->assertRedirect(route('waiter.dashboard'));

        $this->actingAs($mozo)->put(route('admin.dishes.update', $plato), [
            'category_id' => $categoria->id, 'nombre' => 'Truco', 'descripcion' => null,
            'precio' => 1, 'disponible' => 1, 'orden' => 1,
        ])->assertRedirect(route('waiter.dashboard'));

        $this->actingAs($mozo)->post(route('admin.tables.store'), [
            'numero' => '99',
        ])->assertRedirect(route('waiter.dashboard'));

        $this->actingAs($mozo)->post(route('admin.tables.qr.regenerate', $mesa))
            ->assertRedirect(route('waiter.dashboard'));

        $this->actingAs($mozo)->put(route('admin.users.update', $usuario), [
            'name' => $usuario->name, 'email' => $usuario->email,
            'role' => 'administrador', 'activo' => 1,
        ])->assertRedirect(route('waiter.dashboard'));

        $this->actingAs($mozo)->delete(route('admin.dishes.model.destroy', $plato))
            ->assertRedirect(route('waiter.dashboard'));

        $this->assertSame(1, Category::query()->count());
        $this->assertSame(1, Ingredient::query()->count());
        $this->assertSame(1, Table::query()->count());
        $this->assertModelExists($pedido);
        $this->assertSame('mozo', $usuario->fresh()->role->value);
    }
}
