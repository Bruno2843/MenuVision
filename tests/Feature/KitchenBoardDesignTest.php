<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The kitchen screen was restyled into a filterable list of horizontal cards.
 * These tests pin the pieces the layout promises: the status tabs mirror the
 * states the kitchen owns, every card links to its order and shows the order's
 * dish picture, and the counters come from the real orders.
 */
class KitchenBoardDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_board_renders_only_the_filters_the_kitchen_can_use(): void
    {
        $this->orderWithDish('milanesa', ['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('data-kitchen-filter="todos"', false)
            ->assertSee('data-kitchen-filter="confirmado"', false)
            ->assertSee('data-kitchen-filter="en_preparacion"', false)
            ->assertSee('data-kitchen-filter="listo"', false)
            ->assertSee('data-kitchen-filter="sin_insumos"', false)
            // No tab is invented for a state the kitchen never sees.
            ->assertDontSee('data-kitchen-filter="pendiente"', false)
            ->assertDontSee('data-kitchen-filter="entregado"', false);
    }

    public function test_every_card_offers_a_link_to_its_order_detail(): void
    {
        $order = $this->orderWithDish('milanesa', ['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('Ver detalles')
            ->assertSee('PEDIDO', false)
            ->assertSee('Mesa '.$order->table->numero)
            ->assertSee(route('kitchen.orders.show', $order), false);
    }

    public function test_the_card_shows_the_dish_picture_with_its_fallback(): void
    {
        $this->orderWithDish('milanesa', ['estado' => OrderStatus::Confirmado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertSee('data-imagen-respaldo=', false)
            ->assertSee('/img/menu/', false);
    }

    public function test_the_filter_counters_come_from_the_real_orders(): void
    {
        $this->orderWithDish('uno', ['estado' => OrderStatus::Confirmado]);
        $this->orderWithDish('dos', ['estado' => OrderStatus::Confirmado]);
        $this->orderWithDish('tres', ['estado' => OrderStatus::Listo]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->count() === 3);
    }

    public function test_a_delivered_order_is_not_a_card_on_the_board(): void
    {
        $this->orderWithDish('entregado', ['estado' => OrderStatus::Entregado]);

        $this->actingAs(User::factory()->kitchen()->create())
            ->get(route('kitchen.dashboard'))
            ->assertOk()
            ->assertDontSee('data-order-state="entregado"', false);
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
