<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Model3D;
use App\Models\QRCode;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Augmented reality on the public dish detail.
 *
 * The feature promises a real AR experience, so the tests pin down the parts of
 * the contract the server is responsible for: the model shown is always the one
 * belonging to the dish being viewed, the button appears only when that model
 * is really on disk, and the page keeps working when it is not. The runtime
 * behaviour of `<model-viewer>` belongs to the browser, not here.
 */
class DishAugmentedRealityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Storage::fake('public');
    }

    public function test_a_dish_with_a_model_shows_the_3d_viewer(): void
    {
        $this->colocarModelo($ruta = 'models/pizza-margarita.glb');

        [$qrCode, $dish] = $this->platoConModelo($ruta);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertSee('<model-viewer', escape: false)
            ->assertSee('data-ar-visor', escape: false);
    }

    public function test_a_dish_with_a_model_offers_the_augmented_reality_button(): void
    {
        $this->colocarModelo($ruta = 'models/pizza-margarita.glb');

        [$qrCode, $dish] = $this->platoConModelo($ruta);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertSee('Ver en mi espacio')
            ->assertSee('data-ar-boton', escape: false);
    }

    public function test_the_button_uses_the_model_of_the_dish_being_viewed(): void
    {
        $this->colocarModelo('models/pizza-margarita.glb');
        $this->colocarModelo('models/tiramisu.glb');

        $qrCode = QRCode::factory()->for(Table::factory())->create();

        $pizza = Dish::factory()->for(Category::factory()->create(['nombre' => 'Pizzas']))->create([
            'nombre' => 'Pizza Margarita',
        ]);
        $postre = Dish::factory()->for(Category::factory()->create(['nombre' => 'Postres']))->create([
            'nombre' => 'Tiramisú',
        ]);

        Model3D::factory()->for($pizza)->create(['archivo' => 'models/pizza-margarita.glb']);
        Model3D::factory()->for($postre)->create(['archivo' => 'models/tiramisu.glb']);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $pizza->id]))
            ->assertOk()
            ->assertSee('Pizza Margarita')
            ->assertSee('models/pizza-margarita.glb', escape: false)
            ->assertDontSee('models/tiramisu.glb', escape: false);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $postre->id]))
            ->assertOk()
            ->assertSee('Tiramisú')
            ->assertSee('models/tiramisu.glb', escape: false)
            ->assertDontSee('models/pizza-margarita.glb', escape: false);
    }

    public function test_a_dish_without_a_model_does_not_offer_augmented_reality(): void
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $dish = Dish::factory()->for(Category::factory())->create(['nombre' => 'Lomo saltado']);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertDontSee('Ver en mi espacio')
            ->assertDontSee('data-ar-boton', escape: false)
            ->assertDontSee('<model-viewer', escape: false)
            ->assertSee('Este plato todavía no tiene un modelo 3D disponible.');
    }

    public function test_a_model_whose_file_is_missing_does_not_break_the_page(): void
    {
        // The row exists but nothing was ever written to the disk, which is a
        // real state when a model is deleted from the server by hand.
        [$qrCode, $dish] = $this->platoConModelo('models/eliminado.glb');

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertSee('Lomo saltado')
            ->assertDontSee('Ver en mi espacio')
            ->assertDontSee('<model-viewer', escape: false)
            ->assertDontSee('models/eliminado.glb', escape: false)
            ->assertSee('Este plato todavía no tiene un modelo 3D disponible.');
    }

    public function test_the_3d_viewer_stays_usable_on_a_computer(): void
    {
        $this->colocarModelo($ruta = 'models/pizza-margarita.glb');

        [$qrCode, $dish] = $this->platoConModelo($ruta);

        $html = $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            // Rotating and zooming on desktop is what `camera-controls` gives,
            // and the same element is the one AR starts from.
            ->assertSee('camera-controls', escape: false)
            ->assertSee('auto-rotate', escape: false)
            ->getContent();

        $this->assertSame(
            1,
            substr_count($html, '<model-viewer'),
            'La página no puede montar dos visores del mismo modelo.',
        );
    }

    public function test_the_viewer_declares_every_ar_mode_with_a_fallback(): void
    {
        $this->colocarModelo($ruta = 'models/pizza-margarita.glb');

        [$qrCode, $dish] = $this->platoConModelo($ruta);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertSee('ar-modes="webxr scene-viewer quick-look fallback"', escape: false)
            ->assertSee('ar-placement="floor"', escape: false)
            ->assertSee('ar-scale="auto"', escape: false);
    }

    public function test_the_button_waits_for_the_model_and_reports_state_in_the_page(): void
    {
        $this->colocarModelo($ruta = 'models/pizza-margarita.glb');

        [$qrCode, $dish] = $this->platoConModelo($ruta);

        $html = $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->getContent();

        // AR needs the file in memory, so the button cannot be used yet, and the
        // state is announced in the page instead of through a JavaScript alert.
        $this->assertMatchesRegularExpression(
            '/<button\b[^>]*data-ar-boton\b[^>]*\bdisabled\b/',
            $html,
            'El botón de AR no debería estar habilitado antes de que cargue el modelo.',
        );

        $this->assertMatchesRegularExpression(
            '/<p\b[^>]*data-ar-estado\b[^>]*aria-live="polite"/',
            $html,
            'Los estados de AR tienen que anunciarse en la página, sin alertas.',
        );

        $this->assertStringContainsString('Cargando modelo 3D', $html);
    }

    public function test_the_detail_can_be_left_after_the_augmented_reality(): void
    {
        $this->colocarModelo($ruta = 'models/pizza-margarita.glb');

        [$qrCode, $dish] = $this->platoConModelo($ruta);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertSee('Volver al menú')
            ->assertSee(route('menu.show', $qrCode->code), escape: false);
    }

    public function test_augmented_reality_adds_no_login_or_ordering_controls(): void
    {
        $this->colocarModelo($ruta = 'models/pizza-margarita.glb');

        [$qrCode, $dish] = $this->platoConModelo($ruta);

        $this->get(route('menu.dish.show', ['code' => $qrCode->code, 'dish' => $dish->id]))
            ->assertOk()
            ->assertDontSee(route('login'), escape: false)
            ->assertDontSee('Iniciar sesión')
            ->assertDontSee('Carrito')
            ->assertDontSee('Pedir ahora')
            ->assertDontSee('Agregar');
    }

    public function test_the_menu_never_loads_the_models_of_every_dish(): void
    {
        $this->colocarModelo('models/pizza-margarita.glb');

        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $category = Category::factory()->create(['nombre' => 'Pizzas']);
        $dish = Dish::factory()->for($category)->create(['nombre' => 'Pizza Margarita']);
        Model3D::factory()->for($dish)->create(['archivo' => 'models/pizza-margarita.glb']);

        // Listing dishes must not pull the model, or opening the menu would
        // download a GLB per dish before the client asked for any of them.
        $this->get(route('menu.show', $qrCode->code))
            ->assertOk()
            ->assertDontSee('models/pizza-margarita.glb', escape: false)
            ->assertDontSee('<model-viewer', escape: false);
    }

    /**
     * A dish with its own model, ready to be requested through a QR code.
     *
     * @return array{0: QRCode, 1: Dish}
     */
    protected function platoConModelo(string $archivo): array
    {
        $qrCode = QRCode::factory()->for(Table::factory())->create();
        $dish = Dish::factory()->for(Category::factory())->create(['nombre' => 'Lomo saltado']);

        Model3D::factory()->for($dish)->create(['archivo' => $archivo]);

        return [$qrCode, $dish];
    }

    /**
     * Write a model file on the public disk, the way the storage would hold it.
     */
    protected function colocarModelo(string $ruta): void
    {
        Storage::disk('public')->put($ruta, 'contenido de prueba');
    }
}
