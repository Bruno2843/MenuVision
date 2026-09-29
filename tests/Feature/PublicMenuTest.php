<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
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
            ->assertSee('Mesa 7')
            ->assertSee('Carnes')
            ->assertSee('Lomo saltado');
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
            ->assertSeeInOrder(['Lomo saltado'])
            ->getContent();

        $barra = strpos($html, 'data-category-nav');
        $primeraSeccion = strpos($html, '<section id="categoria-');

        $this->assertNotFalse($barra, 'La barra de categorías no se renderizó.');
        $this->assertNotFalse($primeraSeccion, 'No se renderizó ninguna sección de categoría.');
        $this->assertLessThan(
            $primeraSeccion,
            $barra,
            'La barra de categorías tiene que estar arriba de las secciones, no al final.',
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

    public function test_only_the_first_category_chip_starts_highlighted(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $entradas = Category::factory()->create(['nombre' => 'Entradas']);
        $postres = Category::factory()->create(['nombre' => 'Postres']);
        Dish::factory()->for($entradas)->create();
        Dish::factory()->for($postres)->create();

        $html = $this->get(route('menu.show', $qrCode->code))->assertOk()->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'bg-gold-500/15'),
            'Solo el primer chip debe venir marcado, para que el scroll-spy no parpadee al cargar.',
        );
    }
}
