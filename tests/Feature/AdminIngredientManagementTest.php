<?php

namespace Tests\Feature;

use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ingredients are shared by every dish, so the name is the identity: two
 * "Masa" entries would make the menu lie about what a pizza contains.
 */
class AdminIngredientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_list_shows_every_ingredient(): void
    {
        Ingredient::factory()->create(['nombre' => 'Masa']);
        Ingredient::factory()->create(['nombre' => 'Queso']);

        $this->actingAs($this->admin())
            ->get(route('admin.ingredients.index'))
            ->assertOk()
            ->assertSeeInOrder(['Masa', 'Queso']);
    }

    public function test_an_ingredient_is_created(): void
    {
        $this->actingAs($this->admin())->post(route('admin.ingredients.store'), [
            'nombre' => 'Oregano',
        ])->assertRedirect(route('admin.ingredients.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ingredients', ['nombre' => 'Oregano']);
    }

    public function test_the_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.ingredients.store'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(0, Ingredient::query()->count());
    }

    public function test_two_ingredients_cannot_share_a_name(): void
    {
        Ingredient::factory()->create(['nombre' => 'Masa']);

        $this->actingAs($this->admin())
            ->post(route('admin.ingredients.store'), ['nombre' => 'Masa'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Ingredient::query()->where('nombre', 'Masa')->count());
    }

    public function test_the_name_is_also_unique_while_editing(): void
    {
        Ingredient::factory()->create(['nombre' => 'Masa']);
        $queso = Ingredient::factory()->create(['nombre' => 'Queso']);

        $this->actingAs($this->admin())
            ->put(route('admin.ingredients.update', $queso), ['nombre' => 'Masa'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame('Queso', $queso->fresh()->nombre);
    }

    public function test_an_ingredient_keeps_its_own_name_while_editing(): void
    {
        $masa = Ingredient::factory()->create(['nombre' => 'Masa']);

        $this->actingAs($this->admin())
            ->put(route('admin.ingredients.update', $masa), ['nombre' => 'Masa'])
            ->assertRedirect(route('admin.ingredients.index'))
            ->assertSessionHas('status');
    }

    public function test_an_ingredient_is_renamed(): void
    {
        $masa = Ingredient::factory()->create(['nombre' => 'Masa']);

        $this->actingAs($this->admin())
            ->put(route('admin.ingredients.update', $masa), ['nombre' => 'Masa deTagged'])
            ->assertRedirect(route('admin.ingredients.index'));

        $this->assertSame('Masa deTagged', $masa->fresh()->nombre);
    }

    public function test_a_dish_keeps_the_ingredient_after_it_is_renamed(): void
    {
        $masa = Ingredient::factory()->create(['nombre' => 'Masa']);
        $plato = Dish::factory()->create();
        $plato->ingredients()->attach($masa);

        $this->actingAs($this->admin())
            ->put(route('admin.ingredients.update', $masa), ['nombre' => 'Masa de pizza']);

        $this->assertSame(['Masa de pizza'], $plato->fresh()->ingredients->pluck('nombre')->all());
    }

    public function test_deleting_an_ingredient_takes_it_off_the_dishes_that_listed_it(): void
    {
        $masa = Ingredient::factory()->create(['nombre' => 'Masa']);
        $plato = Dish::factory()->create(['nombre' => 'Napolitana']);
        $plato->ingredients()->attach($masa);

        $this->actingAs($this->admin())
            ->delete(route('admin.ingredients.destroy', $masa))
            ->assertRedirect(route('admin.ingredients.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($masa);
        // The dish keeps its name, price and history; only the ingredient is gone.
        $this->assertModelExists($plato);
        $this->assertCount(0, $plato->fresh()->ingredients);
    }

    public function test_an_unused_ingredient_is_deleted(): void
    {
        $masa = Ingredient::factory()->create(['nombre' => 'Masa']);

        $this->actingAs($this->admin())
            ->delete(route('admin.ingredients.destroy', $masa))
            ->assertRedirect(route('admin.ingredients.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($masa);
    }

    public function test_the_form_is_shown_for_creating_and_editing(): void
    {
        $admin = $this->admin();
        $masa = Ingredient::factory()->create(['nombre' => 'Masa']);

        $this->actingAs($admin)->get(route('admin.ingredients.create'))->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.ingredients.edit', $masa))
            ->assertOk()
            ->assertSee('Masa');
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
