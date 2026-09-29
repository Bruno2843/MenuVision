<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_board_only_shows_the_orders_the_kitchen_owns(): void
    {
        $this->orderWithDish('plato confirmado', ['estado' => OrderStatus::Confirmado]);
        $this->orderWithDish('plato en preparacion', ['estado' => OrderStatus::EnPreparacion]);
        $this->orderWithDish('plato listo', ['estado' => OrderStatus::Listo]);
        $this->orderWithDish('plato pendiente', ['estado' => OrderStatus::Pendiente]);
        $this->orderWithDish('plato rechazado', ['estado' => OrderStatus::Rechazado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('plato confirmado')
            ->assertSee('plato en preparacion')
            ->assertSee('plato listo')
            ->assertDontSee('plato pendiente')
            ->assertDontSee('plato rechazado');
    }

    public function test_the_board_shows_the_dishes_and_observations_of_an_order(): void
    {
        $dish = Dish::factory()->create(['nombre' => 'Bife de chorizo']);
        $order = Order::factory()->create([
            'estado' => OrderStatus::Confirmado,
            'observaciones' => 'Sin sal',
        ]);
        OrderItem::factory()->for($order)->for($dish)->create([
            'cantidad' => 3,
            'observaciones' => 'A punto',
        ]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('Bife de chorizo')
            ->assertSee('3')
            ->assertSee('Sin sal')
            ->assertSee('A punto');
    }

    public function test_the_board_queues_the_oldest_order_first(): void
    {
        $reciente = Order::factory()->create([
            'estado' => OrderStatus::Confirmado,
            'created_at' => now()->subMinutes(5),
        ]);
        $antiguo = Order::factory()->create([
            'estado' => OrderStatus::Confirmado,
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(["#{$antiguo->id}", "#{$reciente->id}"]);
    }

    public function test_the_kitchen_starts_preparing_a_confirmed_order(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'en_preparacion'])
            ->assertRedirect(route('kitchen.dashboard'))
            ->assertSessionHas('status');

        $this->assertSame(OrderStatus::EnPreparacion, $order->fresh()->estado);
    }

    public function test_the_kitchen_marks_an_order_as_ready(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::EnPreparacion]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'listo']);

        $this->assertSame(OrderStatus::Listo, $order->fresh()->estado);
    }

    public function test_delivering_the_last_order_of_a_table_frees_it(): void
    {
        $table = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        $order = Order::factory()->for($table)->create(['estado' => OrderStatus::Listo]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'entregado']);

        $this->assertSame(OrderStatus::Entregado, $order->fresh()->estado);
        $this->assertSame(TableStatus::Libre, $table->fresh()->estado);
    }

    public function test_delivering_an_order_keeps_a_table_busy_when_another_order_is_open(): void
    {
        $table = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        $entregado = Order::factory()->for($table)->create(['estado' => OrderStatus::Listo]);
        Order::factory()->for($table)->create(['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $entregado), ['estado' => 'entregado']);

        $this->assertSame(TableStatus::Ocupada, $table->fresh()->estado);
    }

    public function test_the_kitchen_cannot_skip_a_step(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'listo'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::Confirmado, $order->fresh()->estado);
    }

    public function test_the_kitchen_cannot_act_on_a_pending_order(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'en_preparacion'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::Pendiente, $order->fresh()->estado);
    }

    public function test_the_kitchen_cannot_reopen_a_delivered_order(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Entregado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'en_preparacion'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::Entregado, $order->fresh()->estado);
    }

    public function test_the_kitchen_cannot_confirm_or_reject_an_order(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'confirmado'])
            ->assertSessionHasErrors('estado');

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'rechazado'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::Pendiente, $order->fresh()->estado);
    }

    public function test_the_kitchen_hands_back_an_order_when_an_ingredient_is_missing(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), [
                'estado' => 'sin_insumos',
                'motivo_devolucion' => 'Falta la milanesa',
            ])
            ->assertRedirect(route('kitchen.dashboard'))
            ->assertSessionHas('status');

        $order = $order->fresh();

        $this->assertSame(OrderStatus::SinInsumos, $order->estado);
        $this->assertSame('Falta la milanesa', $order->motivo_devolucion);
    }

    public function test_handing_back_an_order_requires_a_reason(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'sin_insumos'])
            ->assertSessionHasErrors('motivo_devolucion');

        $this->assertSame(OrderStatus::Confirmado, $order->fresh()->estado);
    }

    public function test_the_kitchen_can_hand_back_an_order_it_already_started(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::EnPreparacion]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), [
                'estado' => 'sin_insumos',
                'motivo_devolucion' => 'Se nos terminó el queso',
            ]);

        $this->assertSame(OrderStatus::SinInsumos, $order->fresh()->estado);
    }

    public function test_the_kitchen_cannot_hand_back_a_ready_order(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Listo]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), [
                'estado' => 'sin_insumos',
                'motivo_devolucion' => 'Falta la milanesa',
            ])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::Listo, $order->fresh()->estado);
    }

    public function test_advancing_an_order_clears_a_previous_handback_reason(): void
    {
        $order = Order::factory()->create([
            'estado' => OrderStatus::EnPreparacion,
            'motivo_devolucion' => 'Falta la milanesa',
        ]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'listo']);

        $order = $order->fresh();

        $this->assertSame(OrderStatus::Listo, $order->estado);
        $this->assertNull($order->motivo_devolucion);
    }

    public function test_the_board_shows_the_handed_back_orders_with_their_reason(): void
    {
        $this->orderWithDish('napolitana', [
            'estado' => OrderStatus::SinInsumos,
            'motivo_devolucion' => 'Falta la milanesa',
        ]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('Sin insumos')
            ->assertSee('Falta la milanesa');
    }

    public function test_the_board_offers_the_handback_while_the_food_is_being_produced(): void
    {
        $this->orderWithDish('napolitana', ['estado' => OrderStatus::EnPreparacion]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('¿Falta algún insumo?')
            ->assertSee('Devolver al mozo');
    }

    public function test_the_board_does_not_offer_the_handback_once_the_food_is_ready(): void
    {
        $this->orderWithDish('napolitana', ['estado' => OrderStatus::Listo]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertDontSee('Devolver al mozo');
    }

    public function test_the_board_fragment_is_served_without_the_staff_layout(): void
    {
        $this->orderWithDish('napolitana', ['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertViewIs('kitchen.partials.board')
            ->assertSee('napolitana')
            ->assertDontSee('<html', false);
    }

    public function test_a_guest_cannot_reach_the_kitchen_board_fragment(): void
    {
        $this->get(route('kitchen.board'))->assertRedirect(route('login'));
    }

    public function test_a_waiter_cannot_reach_the_kitchen_board_fragment(): void
    {
        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('kitchen.board'))
            ->assertRedirect(route('waiter.dashboard'));
    }

    public function test_the_kitchen_sees_the_order_detail(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::EnPreparacion]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.orders.show', $order))
            ->assertOk()
            ->assertSee("Pedido #{$order->id}")
            ->assertSee('Marcar como listo');
    }

    public function test_rejecting_the_last_order_of_a_table_frees_it(): void
    {
        $table = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        $order = Order::factory()->for($table)->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.status', $order), ['estado' => 'rechazado']);

        $this->assertSame(TableStatus::Libre, $table->fresh()->estado);
    }

    public function test_a_waiter_cannot_reach_the_kitchen_panel(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('kitchen.dashboard'))
            ->assertRedirect(route('waiter.dashboard'));

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('kitchen.orders.status', $order), ['estado' => 'en_preparacion'])
            ->assertRedirect(route('waiter.dashboard'));

        $this->assertSame(OrderStatus::Confirmado, $order->fresh()->estado);
    }

    public function test_a_kitchen_user_cannot_take_orders(): void
    {
        $dish = Dish::factory()->create();
        $table = Table::factory()->create();

        $this->actingAs(User::factory()->kitchen()->create())
            ->post(route('waiter.orders.store'), [
                'table_id' => $table->id,
                'items' => [(string) $dish->id => ['cantidad' => 1]],
            ])
            ->assertRedirect(route('kitchen.dashboard'));

        $this->assertSame(0, Order::count());
    }

    public function test_a_guest_cannot_reach_the_kitchen_panel(): void
    {
        $this->get(route('kitchen.dashboard'))->assertRedirect(route('login'));
    }

    /**
     * Create an order carrying a single identifiable dish.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function orderWithDish(string $dishName, array $attributes = []): Order
    {
        $dish = Dish::factory()->create(['nombre' => $dishName]);

        $order = Order::factory()->create($attributes);
        OrderItem::factory()->for($order)->for($dish)->create();

        return $order;
    }
}
