<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaiterOrderFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_order_form_offers_the_waiter_a_button_to_add_dishes(): void
    {
        $category = Category::factory()->create(['nombre' => 'Entradas']);
        Dish::factory()->for($category)->create(['nombre' => 'Empanadas']);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.create'))
            ->assertOk()
            ->assertSee('Agregar plato')
            ->assertSee('Empanadas');
    }

    public function test_the_order_form_does_not_offer_sold_out_dishes_to_add(): void
    {
        $category = Category::factory()->create(['nombre' => 'Entradas']);
        Dish::factory()->for($category)->agotado()->create(['nombre' => 'Milanesa agotada']);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.create'))
            ->assertOk()
            ->assertDontSee('Milanesa agotada');
    }
}
