<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\TableStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Model3D;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use App\Support\PanelEstadisticas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard reads the real rows instead of keeping counters, so these tests
 * pin the arithmetic that decides what the administrator sees.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_dashboard_renders_on_an_empty_installation(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Todavía no hay pedidos.')
            ->assertSee('Todavía no hay pedidos entregados.');

        $this->assertSame(0.0, PanelEstadisticas::ingresos());
    }

    public function test_revenue_counts_only_delivered_orders(): void
    {
        $entregado = $this->pedidoEntregado(10.00, 2);
        $this->pedidoEntregado(5.00, 1);

        // A pending order is money that has not been taken yet, and a rejected
        // one never will be; neither belongs in the figure.
        $pendiente = Order::factory()->create(['estado' => OrderStatus::Pendiente]);
        OrderItem::factory()->for($pendiente)->create([
            'cantidad' => 4,
            'precio_unitario' => 100.00,
        ]);

        $rechazado = Order::factory()->create(['estado' => OrderStatus::Rechazado]);
        OrderItem::factory()->for($rechazado)->create([
            'cantidad' => 9,
            'precio_unitario' => 100.00,
        ]);

        $this->assertSame(25.00, PanelEstadisticas::ingresos());
        $this->assertSame(4, PanelEstadisticas::pedidos()['total']);
        $this->assertSame(1, PanelEstadisticas::pedidos()['abiertos']);

        unset($entregado);
    }

    public function test_revenue_uses_the_price_frozen_at_order_time(): void
    {
        $plato = Dish::factory()->create(['precio' => 10.00]);
        $pedido = $this->pedidoEntregado(10.00, 2, $plato);

        $plato->update(['precio' => 99.00]);

        $this->assertSame(20.00, PanelEstadisticas::ingresos());
        $this->assertSame(20.00, $pedido->fresh()->total());
    }

    public function test_the_most_ordered_dishes_are_listed_with_their_quantities(): void
    {
        $pizza = Dish::factory()->create(['nombre' => 'Pizza']);
        $empanadas = Dish::factory()->create(['nombre' => 'Empanadas']);

        $this->pedidoEntregado(10, 3, $pizza);
        $this->pedidoEntregado(10, 5, $empanadas);
        $this->pedidoEntregado(10, 1, $pizza);

        $top = PanelEstadisticas::topPlatos();

        $this->assertSame('Empanadas', $top->first()['dish']->nombre);
        $this->assertSame(5, $top->first()['cantidad']);
        $this->assertSame(4, $top->last()['cantidad']);
    }

    public function test_every_order_status_is_listed_even_when_it_has_no_orders(): void
    {
        $pedidos = PanelEstadisticas::pedidos()['porEstado'];

        $this->assertCount(count(OrderStatus::cases()), $pedidos);
        $this->assertSame(0, $pedidos->firstWhere('estado', OrderStatus::Entregado)['total']);
    }

    public function test_a_table_counts_as_occupied_only_while_it_has_an_open_order(): void
    {
        $conPedido = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        Order::factory()->for($conPedido)->create(['estado' => OrderStatus::Pendiente]);

        // Marked busy but nothing is being served on it any more: the waiter
        // panel would have released it, so the dashboard must not count it.
        $liberada = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        Order::factory()->for($liberada)->create(['estado' => OrderStatus::Entregado]);

        Table::factory()->create(['estado' => TableStatus::Libre]);

        $mesas = PanelEstadisticas::mesas();

        $this->assertSame(3, $mesas['total']);
        $this->assertSame(1, $mesas['ocupadas']);
    }

    public function test_the_menu_counters_flag_the_dishes_that_need_a_file(): void
    {
        $category = Category::factory()->create();
        $conFoto = Dish::factory()->for($category)->create(['imagen' => 'platos/1.jpg']);
        Dish::factory()->for($category)->create(['nombre' => 'Sin foto']);
        Dish::factory()->for($category)->agotado()->create(['nombre' => 'Agotado']);
        Model3D::factory()->for($conFoto)->create();

        Ingredient::factory()->create();

        $menu = PanelEstadisticas::menu();

        $this->assertSame(1, $menu['categorias']);
        $this->assertSame(3, $menu['platos']);
        $this->assertSame(2, $menu['disponibles']);
        $this->assertSame(1, $menu['agotados']);
        $this->assertSame(1, $menu['ingredientes']);
        $this->assertSame(1, $menu['modelos']);
        // Only the two dishes with no path in the column; the one that has a
        // path but no file on disk is counted at render time instead.
        $this->assertSame(2, $menu['sinFoto']);
        $this->assertSame(2, $menu['sinModelo']);
    }

    public function test_the_staff_counters_only_include_active_administrators(): void
    {
        User::factory()->admin()->count(2)->create();
        User::factory()->admin()->deactivated()->create();
        User::factory()->waiter()->count(3)->create();

        $personal = PanelEstadisticas::personal();

        $this->assertSame(6, $personal['total']);
        $this->assertSame(5, $personal['activos']);
        $this->assertSame(2, $personal['administradores']);
    }

    public function test_the_dashboard_lists_every_admin_section(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.categories.index'))
            ->assertSee(route('admin.dishes.index'))
            ->assertSee(route('admin.ingredients.index'))
            ->assertSee(route('admin.tables.index'))
            ->assertSee(route('admin.orders.index'))
            ->assertSee(route('admin.users.index'));
    }

    /**
     * A delivered order holding one line, and returning the order.
     */
    private function pedidoEntregado(float $precio, int $cantidad, ?Dish $plato = null): Order
    {
        $pedido = Order::factory()->create(['estado' => OrderStatus::Entregado]);

        OrderItem::factory()->for($pedido)->for($plato ?? Dish::factory())->create([
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
        ]);

        return $pedido;
    }

    public function test_the_dashboard_does_not_depend_on_a_seeded_menu(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Administrador]))
            ->get(route('admin.dashboard'))
            ->assertOk();
    }
}
