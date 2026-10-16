<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\QRCode;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_cliente_can_browse_the_menu_without_logging_in(): void
    {
        $table = Table::factory()->create(['numero' => '7']);
        $qrCode = QRCode::factory()->create(['table_id' => $table->id]);
        $category = Category::factory()->create(['nombre' => 'Carnes']);
        $dish = Dish::factory()->create([
            'category_id' => $category->id,
            'nombre' => 'Lomo saltado',
        ]);

        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertSee('Carnes')
            ->assertSee('Lomo saltado');
    }

    public function test_the_menu_never_shows_the_table_number(): void
    {
        $table = Table::factory()->create(['numero' => '7']);
        $qrCode = QRCode::factory()->create(['table_id' => $table->id]);
        $category = Category::factory()->create(['nombre' => 'Carnes']);
        Dish::factory()->for($category)->create(['nombre' => 'Lomo saltado']);

        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertDontSee('Mesa 7')
            ->assertDontSee('Estás en la');
    }

    public function test_an_unknown_qr_code_returns_a_not_found_response(): void
    {
        $this->get(route('menu.show', 'no-existe'))
            ->assertNotFound();
    }

    public function test_the_category_bar_renders_above_the_dishes(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $category = Category::factory()->create(['nombre' => 'Carnes']);
        Dish::factory()->for($category)->create(['nombre' => 'Lomo saltado']);

        $html = $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertSeeInOrder(['data-category-nav', 'Lomo saltado'])
            ->getContent();

        $barra = strpos($html, 'data-category-nav');
        $grilla = strpos($html, 'id="platos"');

        $this->assertNotFalse($barra, 'La barra de categorías no se renderizó.');
        $this->assertNotFalse($grilla, 'No se renderizó la grilla de platos.');
        $this->assertLessThan(
            $grilla,
            $barra,
            'La barra de categorías tiene que estar arriba de la grilla, no en medio.',
        );
    }

    public function test_the_category_bar_renders_one_chip_per_category(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $entradas = Category::factory()->create(['nombre' => 'Entradas']);
        $postres = Category::factory()->create(['nombre' => 'Postres']);
        Dish::factory()->for($entradas)->create();
        Dish::factory()->for($postres)->create();

        $html = $this->get(route('menu.show', $qrCode->code))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-category-link'));
    }

    public function test_only_the_todas_chip_starts_highlighted(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $entradas = Category::factory()->create(['nombre' => 'Entradas']);
        $postres = Category::factory()->create(['nombre' => 'Postres']);
        Dish::factory()->for($entradas)->create();
        Dish::factory()->for($postres)->create();

        $html = $this->get(route('menu.show', $qrCode->code))->assertOk()->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'aria-pressed="true"'),
            'Solo un botón debe venir marcado, para que el filtro arranque en "Todas".',
        );

        $this->assertSame(
            1,
            preg_match(
                '/<button\b[^>]*data-category-all\b[^>]*aria-pressed="true"/',
                $html
            ),
            'El botón marcado al cargar tiene que ser el de "Todas", porque todavía no se filtró por ninguna categoría.',
        );
    }

    public function test_the_cliente_can_open_the_detail_of_an_available_dish(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $category = Category::factory()->create(['nombre' => 'Carnes']);
        $dish = Dish::factory()->for($category)->create([
            'nombre' => 'Lomo saltado',
            'descripcion' => 'Lomo a la parrilla con papas doradas.',
            'precio' => 32.5,
        ]);
        $dish->ingredients()->attach(Ingredient::factory()->create(['nombre' => 'Papas']));

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertSee('Lomo saltado')
            ->assertSee('Lomo a la parrilla con papas doradas.')
            ->assertSee('32.50')
            ->assertSee('Papas')
            ->assertSee('Carnes');
    }

    public function test_an_unavailable_dish_detail_is_not_reachable(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $dish = Dish::factory()->for(Category::factory())->create([
            'nombre' => 'Lomo saltado',
            'disponible' => false,
        ]);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertNotFound();
    }

    public function test_a_dish_detail_requires_a_valid_qr_code(): void
    {
        $dish = Dish::factory()->for(Category::factory())->create();

        $this->get(route('menu.dish.show', ['code' => 'no-existe', 'dish' => $dish->id]))
            ->assertNotFound();
    }

    public function test_the_dish_detail_links_back_to_the_menu(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $dish = Dish::factory()->for(Category::factory())->create();

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertSee(route('menu.show', $qrCode->code), escape: false);
    }

    public function test_the_public_menu_has_no_login_or_ordering_controls(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $category = Category::factory()->create(['nombre' => 'Carnes']);
        Dish::factory()->for($category)->create(['nombre' => 'Lomo saltado']);

        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertDontSee(route('login'), escape: false)
            ->assertDontSee('Iniciar sesión')
            ->assertDontSee('Ingresar')
            ->assertDontSee('Carrito')
            ->assertDontSee('Pedir ahora')
            ->assertDontSee('Agregar');
    }

    public function test_every_ver_plato_link_opens_its_own_dish(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $pizza = Dish::factory()->for(Category::factory()->create(['nombre' => 'Pizzas']))->create([
            'nombre' => 'Pizza Margarita',
        ]);
        $postre = Dish::factory()->for(Category::factory()->create(['nombre' => 'Postres']))->create([
            'nombre' => 'Tiramisú',
        ]);

        $html = $this->get(route('menu.show', $qrCode->code))->assertOk()->getContent();

        $this->assertStringContainsString(
            route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $pizza->id]),
            $html,
            'La tarjeta de la pizza no enlaza a su propio detalle.',
        );
        $this->assertStringContainsString(
            route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $postre->id]),
            $html,
            'La tarjeta del postre no enlaza a su propio detalle.',
        );

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $pizza->id]))
            ->assertOk()
            ->assertSee('Pizza Margarita')
            ->assertDontSee('Tiramisú');
    }

    public function test_the_menu_references_no_broken_image(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $category = Category::factory()->create(['nombre' => 'Pizzas']);
        Dish::factory()->for($category)->create(['nombre' => 'Pizza Margarita']);

        $html = $this->get(route('menu.show', $qrCode->code))->assertOk()->getContent();

        preg_match_all('#(?:img/menu|platos)/[A-Za-z0-9._-]+#', $html, $coincidencias);

        $this->assertNotEmpty($coincidencias[0], 'El menú no mostró ninguna imagen.');

        foreach (array_unique($coincidencias[0]) as $ruta) {
            $this->assertFileExists(
                public_path($ruta),
                "La imagen «{$ruta}» está referenciada en el menú pero no existe en disco.",
            );
        }
    }

    public function test_a_dish_with_a_photograph_uses_it_instead_of_the_fallback(): void
    {
        $ruta = public_path('platos/pizza-margarita.webp');
        @mkdir(dirname($ruta), 0777, true);
        file_put_contents($ruta, 'contenido de prueba');

        try {
            $qrCode = QRCode::factory()->for(Table::factory())->create();
            Dish::factory()->for(Category::factory()->create(['nombre' => 'Pizzas']))->create([
                'nombre' => 'Pizza Margarita',
            ]);

            $this->get(route('menu.show', $qrCode->code))
                ->assertOk()
                ->assertSee('platos/pizza-margarita.webp', escape: false);
        } finally {
            @unlink($ruta);
        }
    }

    public function test_a_dish_without_a_photograph_falls_back_to_a_scene_of_its_category(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        Dish::factory()->for(Category::factory()->create(['nombre' => 'Postres']))->create([
            'nombre' => 'Tiramisú',
        ]);

        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertSee('img/menu/postres-', escape: false);
    }
}
