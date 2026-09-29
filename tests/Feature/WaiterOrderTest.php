<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaiterOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_waiter_sees_the_menu_grouped_by_category_including_unavailable_dishes(): void
    {
        $category = Category::factory()->create(['nombre' => 'Pizzas', 'orden' => 1]);
        Dish::factory()->for($category)->create(['nombre' => 'Pizza napolitana', 'orden' => 1]);
        Dish::factory()->for($category)->agotado()->create(['nombre' => 'Pizza fugazzeta', 'orden' => 2]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.menu'))
            ->assertOk()
            ->assertSeeInOrder(['Pizzas', 'Pizza napolitana', 'Pizza fugazzeta'])
            ->assertSee('Agotado');
    }

    public function test_the_waiter_creates_an_order_that_freezes_the_current_prices(): void
    {
        $mozo = User::factory()->waiter()->create();
        $table = Table::factory()->create(['numero' => '5']);
        $category = Category::factory()->create();
        $dish = Dish::factory()->for($category)->create(['precio' => 10.50]);

        $response = $this->actingAs($mozo)->post(route('waiter.orders.store'), [
            'table_id' => $table->id,
            'items' => [
                (string) $dish->id => ['cantidad' => 2],
            ],
        ]);

        $order = Order::sole();

        $response->assertRedirect(route('waiter.orders.show', $order));
        $this->assertSame(OrderStatus::Pendiente, $order->estado);
        $this->assertSame(TableStatus::Ocupada, $order->table->estado);

        $item = OrderItem::sole();
        $this->assertSame(2, $item->cantidad);
        $this->assertSame('10.50', $item->precio_unitario);

        $dish->update(['precio' => 99.00]);

        $this->assertSame('10.50', $item->fresh()->precio_unitario);
        $this->assertSame(21.00, $order->fresh()->total());
    }

    public function test_the_waiter_marks_the_table_as_occupied_when_taking_an_order(): void
    {
        $table = Table::factory()->create(['estado' => TableStatus::Libre]);
        $dish = Dish::factory()->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->post(route('waiter.orders.store'), [
                'table_id' => $table->id,
                'items' => [(string) $dish->id => ['cantidad' => 1]],
            ]);

        $this->assertSame(TableStatus::Ocupada, $table->fresh()->estado);
    }

    public function test_dishes_left_at_zero_are_ignored(): void
    {
        $table = Table::factory()->create();
        $pedido = Dish::factory()->create();
        $noPedido = Dish::factory()->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->post(route('waiter.orders.store'), [
                'table_id' => $table->id,
                'items' => [
                    (string) $pedido->id => ['cantidad' => 3],
                    (string) $noPedido->id => ['cantidad' => 0],
                ],
            ]);

        $this->assertSame(1, Order::sole()->items()->count());
        $this->assertSame(3, OrderItem::sole()->cantidad);
    }

    public function test_an_order_without_any_dish_is_rejected(): void
    {
        $table = Table::factory()->create();
        $dish = Dish::factory()->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->post(route('waiter.orders.store'), [
                'table_id' => $table->id,
                'items' => [(string) $dish->id => ['cantidad' => 0]],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Order::count());
    }

    public function test_an_unavailable_dish_cannot_be_ordered(): void
    {
        $table = Table::factory()->create();
        $agotado = Dish::factory()->agotado()->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->post(route('waiter.orders.store'), [
                'table_id' => $table->id,
                'items' => [(string) $agotado->id => ['cantidad' => 1]],
            ])
            ->assertSessionHasErrors();

        $this->assertSame(0, Order::count());
    }

    public function test_the_waiter_confirms_a_pending_order(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.status', $order), ['estado' => 'confirmado'])
            ->assertRedirect(route('waiter.orders.show', $order));

        $this->assertSame(OrderStatus::Confirmado, $order->fresh()->estado);
    }

    public function test_the_waiter_cannot_move_an_order_out_of_the_pending_status(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.status', $order), ['estado' => 'rechazado'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::Confirmado, $order->fresh()->estado);
    }

    public function test_the_waiter_cannot_touch_an_order_the_kitchen_already_started(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::EnPreparacion]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.status', $order), ['estado' => 'rechazado'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::EnPreparacion, $order->fresh()->estado);
    }

    public function test_a_handed_back_order_cannot_skip_the_resend_form(): void
    {
        $order = Order::factory()->create([
            'estado' => OrderStatus::SinInsumos,
            'motivo_devolucion' => 'Falta la milanesa',
        ]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.status', $order), ['estado' => 'confirmado'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::SinInsumos, $order->fresh()->estado);
    }

    public function test_the_resend_form_offers_the_dishes_of_the_order_and_the_rest_of_the_menu(): void
    {
        $category = Category::factory()->create(['nombre' => 'Pizzas', 'orden' => 1]);
        $enElPedido = Dish::factory()->for($category)->create(['nombre' => 'Muzza', 'precio' => 10.00]);
        $agregable = Dish::factory()->for($category)->create(['nombre' => 'Napolitana', 'precio' => 12.00]);
        $order = Order::factory()->create(['estado' => OrderStatus::SinInsumos]);
        OrderItem::factory()->for($order)->for($enElPedido)->create(['cantidad' => 2]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.resend', $order))
            ->assertOk()
            ->assertSee('Muzza')
            ->assertSee('Napolitana')
            // The dish already on the order is only rendered once.
            ->assertSee('items['.$enElPedido->id.'][cantidad]', false)
            ->assertSee('value="2"', false);

        $this->assertSame(1, substr_count(
            $this->actingAs(User::factory()->waiter()->create())
                ->get(route('waiter.orders.resend', $order))
                ->getContent(),
            'items['.$enElPedido->id.'][cantidad]'
        ));
    }

    public function test_the_resend_form_keeps_a_sold_out_dish_of_the_order_on_screen(): void
    {
        $agotado = Dish::factory()->agotado()->create(['nombre' => 'Milanesa napolitana']);
        $order = Order::factory()->create(['estado' => OrderStatus::SinInsumos]);
        OrderItem::factory()->for($order)->for($agotado)->create(['cantidad' => 1]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.resend', $order))
            ->assertOk()
            ->assertSee('Milanesa napolitana')
            ->assertSee('Agotado');
    }

    public function test_a_handed_back_order_can_be_sent_back_unchanged(): void
    {
        $dish = Dish::factory()->create(['precio' => 10.00]);
        $order = Order::factory()->create([
            'estado' => OrderStatus::SinInsumos,
            'motivo_devolucion' => 'Falta la milanesa',
        ]);
        OrderItem::factory()->for($order)->for($dish)->create(['cantidad' => 2]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.resend.update', $order), [
                'items' => [$dish->id => ['cantidad' => 2]],
                'preparar_igual' => '1',
            ])
            ->assertRedirect(route('waiter.orders.show', $order))
            ->assertSessionHas('status');

        $order = $order->fresh();

        $this->assertSame(OrderStatus::Confirmado, $order->estado);
        $this->assertNull($order->motivo_devolucion);
        $this->assertSame(Order::OBSERVACION_REENVIO, $order->observaciones);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(2, $order->items()->sole()->cantidad);
    }

    public function test_the_same_order_note_is_added_next_to_the_existing_observation(): void
    {
        $dish = Dish::factory()->create();
        $order = Order::factory()->create([
            'estado' => OrderStatus::SinInsumos,
            'observaciones' => 'Sin sal',
        ]);
        OrderItem::factory()->for($order)->for($dish)->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.resend.update', $order), [
                'items' => [$dish->id => ['cantidad' => 1]],
                'observaciones' => 'Sin sal',
                'preparar_igual' => '1',
            ]);

        $this->assertSame(
            'Sin sal — '.Order::OBSERVACION_REENVIO,
            $order->fresh()->observaciones
        );
    }

    public function test_the_waiter_can_replace_the_dishes_of_a_handed_back_order(): void
    {
        $milanesa = Dish::factory()->create(['precio' => 10.00]);
        $ensalada = Dish::factory()->create(['precio' => 8.00]);
        $order = Order::factory()->create(['estado' => OrderStatus::SinInsumos]);
        OrderItem::factory()->for($order)->for($milanesa)->create(['cantidad' => 2]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.resend.update', $order), [
                'items' => [
                    $milanesa->id => ['cantidad' => 0],
                    $ensalada->id => ['cantidad' => 3],
                ],
            ]);

        $order = $order->fresh();

        $this->assertSame(OrderStatus::Confirmado, $order->estado);
        $this->assertNull($order->motivo_devolucion);
        $this->assertTrue($order->items->sole()->dish->is($ensalada));
        $this->assertSame(3, $order->items->sole()->cantidad);
    }

    public function test_a_dish_that_stays_keeps_the_price_it_was_frozen_at(): void
    {
        $milanesa = Dish::factory()->create(['precio' => 10.00]);
        $ensalada = Dish::factory()->create(['precio' => 8.00]);
        $order = Order::factory()->create(['estado' => OrderStatus::SinInsumos]);
        OrderItem::factory()->for($order)->for($milanesa)->create([
            'cantidad' => 1,
            'precio_unitario' => 10.00,
        ]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.resend.update', $order), [
                'items' => [
                    $milanesa->id => ['cantidad' => 2],
                    $ensalada->id => ['cantidad' => 1],
                ],
            ]);

        $milanesa->update(['precio' => 25.00]);
        $order = $order->fresh();

        $this->assertSame('10.00', $order->items->firstWhere('dish_id', $milanesa->id)->precio_unitario);
        $this->assertSame('8.00', $order->items->firstWhere('dish_id', $ensalada->id)->precio_unitario);
        $this->assertSame(28.00, $order->total());
    }

    public function test_a_handed_back_order_cannot_be_resent_without_dishes(): void
    {
        $dish = Dish::factory()->create();
        $order = Order::factory()->create(['estado' => OrderStatus::SinInsumos]);
        OrderItem::factory()->for($order)->for($dish)->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.resend.update', $order), [
                'items' => [$dish->id => ['cantidad' => 0]],
            ])
            ->assertSessionHasErrors('items');

        $order = $order->fresh();

        $this->assertSame(OrderStatus::SinInsumos, $order->estado);
        $this->assertSame(1, $order->items()->count());
    }

    public function test_a_handed_back_order_keeps_a_sold_out_dish(): void
    {
        $agotado = Dish::factory()->agotado()->create();
        $order = Order::factory()->create(['estado' => OrderStatus::SinInsumos]);
        OrderItem::factory()->for($order)->for($agotado)->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.resend.update', $order), [
                'items' => [$agotado->id => ['cantidad' => 1]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Confirmado, $order->fresh()->estado);
    }

    public function test_an_order_that_is_not_a_handback_cannot_be_resent(): void
    {
        $dish = Dish::factory()->create();
        $order = Order::factory()->create(['estado' => OrderStatus::Confirmado]);
        OrderItem::factory()->for($order)->for($dish)->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.resend', $order))
            ->assertRedirect(route('waiter.orders.show', $order))
            ->assertSessionHas('error');

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.resend.update', $order), [
                'items' => [$dish->id => ['cantidad' => 1]],
            ])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::Confirmado, $order->fresh()->estado);
    }

    public function test_an_order_that_is_not_a_handback_cannot_be_resent_even_with_an_empty_payload(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Entregado]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.resend.update', $order), ['items' => []])
            ->assertSessionHasErrors('estado');

        $this->assertSame(OrderStatus::Entregado, $order->fresh()->estado);
    }

    public function test_a_kitchen_user_cannot_resend_an_order(): void
    {
        $dish = Dish::factory()->create();
        $order = Order::factory()->create(['estado' => OrderStatus::SinInsumos]);
        OrderItem::factory()->for($order)->for($dish)->create();

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('waiter.orders.resend', $order))
            ->assertRedirect(route('kitchen.dashboard'));

        $this->assertSame(OrderStatus::SinInsumos, $order->fresh()->estado);
    }

    public function test_the_waiter_rejects_a_handed_back_order_and_the_table_is_freed(): void
    {
        $table = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        $order = Order::factory()->for($table)->create([
            'estado' => OrderStatus::SinInsumos,
            'motivo_devolucion' => 'Falta la milanesa',
        ]);

        $this->actingAs(User::factory()->waiter()->create())
            ->patch(route('waiter.orders.status', $order), ['estado' => 'rechazado']);

        $this->assertSame(OrderStatus::Rechazado, $order->fresh()->estado);
        $this->assertSame(TableStatus::Libre, $table->fresh()->estado);
    }

    public function test_a_table_with_an_order_being_prepared_is_not_reported_as_free(): void
    {
        $table = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        $order = Order::factory()->for($table)->create(['estado' => OrderStatus::EnPreparacion]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.dashboard'))
            ->assertOk()
            ->assertSee("Pedido #{$order->id}")
            ->assertDontSee('Sin pedidos abiertos');
    }

    public function test_the_dashboard_points_out_an_order_the_kitchen_handed_back(): void
    {
        $table = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        $order = Order::factory()->for($table)->create(['estado' => OrderStatus::SinInsumos]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.dashboard'))
            ->assertOk()
            ->assertSee("Pedido #{$order->id}")
            ->assertSee('Sin insumos');
    }

    public function test_the_dashboard_counts_the_orders_a_table_still_has_in_course(): void
    {
        $table = Table::factory()->create(['estado' => TableStatus::Ocupada]);
        $enPreparacion = Order::factory()->for($table)->create([
            'estado' => OrderStatus::EnPreparacion,
            'created_at' => now()->subMinutes(5),
        ]);
        Order::factory()->for($table)->create([
            'estado' => OrderStatus::Confirmado,
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.dashboard'))
            ->assertOk()
            ->assertSee("Pedido #{$enPreparacion->id}")
            ->assertSee('+1 pedido en curso');
    }

    public function test_the_order_detail_shows_the_progress_of_the_order(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::EnPreparacion]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.show', $order))
            ->assertOk()
            ->assertSeeInOrder(['Pendiente', 'Confirmado', 'En preparación', 'Listo', 'Entregado'])
            ->assertDontSee('Confirmar pedido')
            ->assertDontSee('Rechazar');
    }

    public function test_the_order_detail_offers_the_way_back_to_the_kitchen(): void
    {
        $order = Order::factory()->create([
            'estado' => OrderStatus::SinInsumos,
            'motivo_devolucion' => 'Falta la milanesa',
        ]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.show', $order))
            ->assertOk()
            ->assertSee('Falta la milanesa')
            ->assertSee('La cocina no pudo prepararlo')
            ->assertSee('Reenviar a cocina')
            ->assertSee(route('waiter.orders.resend', $order), false)
            ->assertSee('Rechazar');
    }

    public function test_the_order_detail_offers_the_confirmation_of_a_pending_order(): void
    {
        $order = Order::factory()->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.show', $order))
            ->assertOk()
            ->assertSee('Confirmar pedido')
            ->assertSee('Rechazar');
    }

    public function test_the_board_fragment_is_served_without_the_staff_layout(): void
    {
        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.board'))
            ->assertOk()
            ->assertViewIs('waiter.partials.board')
            ->assertDontSee('<html', false);
    }

    public function test_a_guest_cannot_reach_the_waiter_board_fragment(): void
    {
        $this->get(route('waiter.board'))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_take_an_order(): void
    {
        $dish = Dish::factory()->create();
        $table = Table::factory()->create();

        $this->post(route('waiter.orders.store'), [
            'table_id' => $table->id,
            'items' => [(string) $dish->id => ['cantidad' => 1]],
        ])->assertRedirect(route('login'));

        $this->assertSame(0, Order::count());
    }

    public function test_validation_errors_name_the_fields_the_waiter_sees(): void
    {
        $response = $this->actingAs(User::factory()->waiter()->create())
            ->post(route('waiter.orders.store'), [
                'table_id' => null,
                'items' => [],
            ]);

        $response->assertSessionHasErrors([
            'table_id' => 'El campo mesa es obligatorio.',
            'items' => 'Agregá al menos un plato al pedido.',
        ]);
    }

    public function test_an_administrator_cannot_take_an_order(): void
    {
        $dish = Dish::factory()->create();
        $table = Table::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('waiter.orders.store'), [
                'table_id' => $table->id,
                'items' => [(string) $dish->id => ['cantidad' => 1]],
            ])->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, Order::count());
    }
}
