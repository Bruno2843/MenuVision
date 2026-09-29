<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\QRCode;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuCategoryOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_menu_shows_categories_in_their_configured_order(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory()->create(['numero' => '3']))->create();
        $postres = Category::factory()->create(['nombre' => 'Postres', 'orden' => 1]);
        $entradas = Category::factory()->create(['nombre' => 'Entradas', 'orden' => 2]);
        Dish::factory()->for($postres)->create(['nombre' => 'Tiramisú']);
        Dish::factory()->for($entradas)->create(['nombre' => 'Empanadas']);

        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertSeeInOrder(['Postres', 'Entradas']);
    }

    public function test_the_menu_shows_dishes_in_their_configured_order_within_a_category(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $category = Category::factory()->create();
        Dish::factory()->for($category)->create(['nombre' => 'Pizza pepperoni', 'orden' => 2]);
        Dish::factory()->for($category)->create(['nombre' => 'Pizza Margarita', 'orden' => 1]);

        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertSeeInOrder(['Pizza Margarita', 'Pizza pepperoni']);
    }

    public function test_the_menu_hides_unavailable_dishes(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $category = Category::factory()->create();
        Dish::factory()->for($category)->create(['nombre' => 'Lomo saltado']);
        Dish::factory()->for($category)->agotado()->create(['nombre' => 'Sopa de onion']);

        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertSee('Lomo saltado')
            ->assertDontSee('Sopa de onion');
    }

    public function test_the_menu_hides_a_category_whose_only_dish_is_unavailable(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $agotada = Category::factory()->create(['nombre' => 'Sopa del día']);
        Dish::factory()->for($agotada)->agotado()->create();

        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertDontSee('Sopa del día');
    }
}
