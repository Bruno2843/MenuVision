<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_an_administrator_creates_a_category_with_its_order(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.categories.store'), [
                'nombre' => 'Entradas',
                'descripcion' => 'Para compartir',
                'orden' => 1,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'nombre' => 'Entradas',
            'descripcion' => 'Para compartir',
            'orden' => 1,
        ]);
    }

    public function test_a_duplicate_category_name_is_rejected(): void
    {
        Category::factory()->create(['nombre' => 'Postres']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.categories.store'), [
                'nombre' => 'Postres',
                'descripcion' => null,
                'orden' => 2,
            ])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Category::count());
    }

    public function test_an_administrator_updates_a_category(): void
    {
        $category = Category::factory()->create(['nombre' => 'Pizzas', 'orden' => 5]);

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.categories.index'))
            ->put(route('admin.categories.update', $category), [
                'nombre' => 'Pizzas al horno',
                'descripcion' => null,
                'orden' => 3,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSame('Pizzas al horno', $category->fresh()->nombre);
        $this->assertSame(3, $category->fresh()->orden);
    }

    public function test_a_category_with_dishes_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Dish::factory()->for($category)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }

    public function test_an_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertModelMissing($category);
    }

    public function test_an_administrator_creates_a_dish(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.dishes.store'), [
                'category_id' => $category->id,
                'nombre' => 'Empanadas de carne',
                'descripcion' => 'Seis unidades',
                'precio' => 8.5,
                'disponible' => 1,
                'orden' => 1,
            ])
            ->assertRedirect(route('admin.dishes.index'));

        $this->assertDatabaseHas('dishes', [
            'category_id' => $category->id,
            'nombre' => 'Empanadas de carne',
            'precio' => 8.5,
            'disponible' => true,
            'orden' => 1,
        ]);
    }

    public function test_a_dish_cannot_be_created_with_a_negative_price(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.dishes.store'), [
                'category_id' => Category::factory()->create()->id,
                'nombre' => 'Plato gratis',
                'descripcion' => null,
                'precio' => -5,
                'disponible' => 1,
                'orden' => 1,
            ])
            ->assertSessionHasErrors('precio');

        $this->assertSame(0, Dish::count());
    }

    public function test_an_administrator_marks_a_dish_as_unavailable(): void
    {
        $dish = Dish::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.dishes.update', $dish), [
                'category_id' => $dish->category_id,
                'nombre' => $dish->nombre,
                'descripcion' => $dish->descripcion,
                'precio' => $dish->precio,
                'disponible' => 0,
                'orden' => $dish->orden,
            ])
            ->assertRedirect(route('admin.dishes.index'));

        $this->assertFalse($dish->fresh()->disponible);
    }

    public function test_the_dish_list_can_be_filtered_by_category(): void
    {
        $pizzas = Category::factory()->create(['nombre' => 'Pizzas']);
        $postres = Category::factory()->create(['nombre' => 'Postres']);
        Dish::factory()->for($pizzas)->create(['nombre' => 'Pizza napolitana']);
        Dish::factory()->for($postres)->create(['nombre' => 'Tiramisú']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dishes.index', ['categoria' => $postres->id]))
            ->assertOk()
            ->assertSee('Tiramisú')
            ->assertDontSee('Pizza napolitana');
    }

    public function test_the_dish_search_ignores_case_on_both_databases(): void
    {
        $pizzas = Category::factory()->create(['nombre' => 'Pizzas']);
        Dish::factory()->for($pizzas)->create(['nombre' => 'Milanesa napolitana']);
        Dish::factory()->for($pizzas)->create(['nombre' => 'Tiramisú']);

        // SQLite compares `like` without case, PostgreSQL's `like` is case
        // sensitive. `whereLike` lets each grammar pick the operator, so the
        // same lowercase search finds "Milanesa" on both engines.
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dishes.index', ['buscar' => 'milanesa']))
            ->assertOk()
            ->assertSee('Milanesa napolitana')
            ->assertDontSee('Tiramisú');
    }

    public function test_a_dish_that_belongs_to_an_order_cannot_be_deleted(): void
    {
        $order = Order::factory()->create();
        $dish = Dish::factory()->create();
        OrderItem::factory()->for($order)->for($dish)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.dishes.index'))
            ->delete(route('admin.dishes.destroy', $dish))
            ->assertRedirect(route('admin.dishes.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($dish);
    }

    public function test_a_dish_without_orders_can_be_deleted(): void
    {
        $dish = Dish::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.dishes.destroy', $dish))
            ->assertRedirect(route('admin.dishes.index'));

        $this->assertModelMissing($dish);
    }

    public function test_a_waiter_cannot_reach_the_menu_management(): void
    {
        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('admin.categories.index'))
            ->assertRedirect(route('waiter.dashboard'));

        $this->actingAs(User::factory()->waiter()->create())
            ->post(route('admin.categories.store'), [
                'nombre' => 'Truco',
                'descripcion' => null,
                'orden' => 1,
            ])
            ->assertRedirect(route('waiter.dashboard'));

        $this->assertSame(0, Category::count());
    }
}
