<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The administrator sees what happened without touching it. The waiter and the
 * kitchen own the state machine, and a third panel able to move an order along
 * would be a third opinion nobody asked for.
 */
class AdminOrderQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_list_shows_the_orders_newest_first(): void
    {
        $mesa = Table::factory()->create();
        $primero = Order::factory()->for($mesa)->create(['estado' => OrderStatus::Pendiente]);
        $segundo = Order::factory()->for($mesa)->create(['estado' => OrderStatus::Entregado]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSeeInOrder([$segundo->numero, $primero->numero]);
    }

    public function test_the_orders_can_be_filtered_by_state(): void
    {
        $mesa = Table::factory()->create();
        $pendiente = Order::factory()->for($mesa)->create(['estado' => OrderStatus::Pendiente]);
        $entregado = Order::factory()->for($mesa)->create(['estado' => OrderStatus::Entregado]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['estado' => OrderStatus::Entregado->value]))
            ->assertOk()
            ->assertSee($entregado->numero)
            ->assertDontSee($pendiente->numero);
    }

    public function test_the_orders_can_be_filtered_by_table(): void
    {
        $buscada = Table::factory()->create(['numero' => '7']);
        $otra = Table::factory()->create(['numero' => '8']);
        $enLaSiete = Order::factory()->for($buscada)->create();
        $enLaOcho = Order::factory()->for($otra)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['mesa' => $buscada->id]))
            ->assertOk()
            ->assertSee($enLaSiete->numero)
            ->assertDontSee($enLaOcho->numero);
    }

    public function test_the_orders_can_be_filtered_from_a_date(): void
    {
        $mesa = Table::factory()->create();
        $viejo = Order::factory()->for($mesa)->create(['created_at' => now()->subDays(10)]);
        $reciente = Order::factory()->for($mesa)->create(['created_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['desde' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee($reciente->numero)
            ->assertDontSee($viejo->numero);
    }

    public function test_a_malformed_date_filters_nothing_out_of_the_screen(): void
    {
        Order::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['desde' => 'no-es-una-fecha']))
            ->assertOk();
    }

    public function test_the_filter_form_offers_every_state_and_table(): void
    {
        $mesa = Table::factory()->create(['numero' => '7']);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('7')
            ->assertSee(OrderStatus::Entregado->label());
    }

    public function test_an_order_is_shown_with_its_table_and_its_lines(): void
    {
        $mesa = Table::factory()->create(['numero' => '7']);
        $plato = Dish::factory()->create(['nombre' => 'Napolitana', 'precio' => 100.00]);
        $pedido = Order::factory()->for($mesa)->create([
            'estado' => OrderStatus::Pendiente,
            'observaciones' => 'Sin sal',
        ]);
        OrderItem::factory()->for($pedido)->for($plato)->create([
            'cantidad' => 2,
            'precio_unitario' => 100.00,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $pedido))
            ->assertOk()
            ->assertSee('Napolitana')
            ->assertSee('Sin sal')
            ->assertSee('7')
            // The price the customer was charged, not today's one.
            ->assertSee('100.00');
    }

    public function test_the_order_screen_offers_no_way_to_change_its_state(): void
    {
        $pedido = Order::factory()->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $pedido))
            ->assertOk()
            ->assertDontSee('name="_method" value="PATCH"', false)
            ->assertDontSee(route('waiter.orders.status', $pedido), false)
            ->assertDontSee(route('kitchen.orders.status', $pedido), false);

        $this->assertSame(OrderStatus::Pendiente, $pedido->fresh()->estado);
    }

    public function test_the_administrator_cannot_reach_the_state_machine_of_the_other_panels(): void
    {
        $pedido = Order::factory()->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs($this->admin())
            ->patch(route('waiter.orders.status', $pedido), ['estado' => OrderStatus::Confirmado->value])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->admin())
            ->patch(route('kitchen.orders.status', $pedido), ['estado' => OrderStatus::Listo->value])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(OrderStatus::Pendiente, $pedido->fresh()->estado);
    }

    public function test_the_list_survives_an_order_with_no_lines(): void
    {
        Order::factory()->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index'))
            ->assertOk();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
