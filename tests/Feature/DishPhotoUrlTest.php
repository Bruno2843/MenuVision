<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\QRCode;
use App\Models\Table;
use App\Support\MenuImagen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The photograph is served by the browser, not by PHP, so whether it appears
 * depends entirely on the URL that reaches the markup.
 *
 * These tests pin the two things that decide it: that the URL of a file on the
 * public disk is root relative, so it resolves against whichever host served the
 * page (the Herd domain, 127.0.0.1, the LAN address a phone reaches), and that
 * every dish image carries the picture to fall back to, so a photograph the
 * browser cannot fetch does not leave the dark photo frame empty.
 */
class DishPhotoUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_url_of_a_public_file_is_root_relative_and_not_tied_to_one_host(): void
    {
        // The installation is reached through several hosts: menuvision2.0.test
        // locally, 127.0.0.1 while `artisan serve` is up, and the LAN address of
        // the machine from every phone that scans a table QR code. Baking one of
        // them into the URL means the others request a host they cannot reach,
        // and the photograph silently never loads.
        $url = Storage::disk('public')->url('platos/8.jpg');

        $this->assertStringStartsWith('/storage/', $url);
        $this->assertStringNotContainsString('localhost', $url);
        $this->assertStringNotContainsString('://', $url);
    }

    public function test_the_menu_and_the_detail_render_the_uploaded_photo_from_the_public_path(): void
    {
        Storage::fake('public');

        $plato = Dish::factory()->for(Category::factory()->create(['nombre' => 'Pastas']))->create();
        $plato->update(['imagen' => "platos/{$plato->id}.jpg"]);
        Storage::disk('public')->put("platos/{$plato->id}.jpg", 'foto');

        $codigo = QRCode::factory()->for(Table::factory())->create()->code;

        $this->get(route('menu.show', $codigo))
            ->assertOk()
            ->assertSee("/storage/platos/{$plato->id}.jpg", escape: false);

        $this->get(route('menu.dish.show', ['code' => $codigo, 'dish' => $plato->id]))
            ->assertOk()
            ->assertSee("/storage/platos/{$plato->id}.jpg", escape: false);
    }

    public function test_every_dish_image_declares_the_picture_to_fall_back_to(): void
    {
        Storage::fake('public');

        $codigo = QRCode::factory()->for(Table::factory())->create()->code;

        $conFoto = Dish::factory()->for(Category::factory()->create(['nombre' => 'Pizzas']))->create();
        $conFoto->update(['imagen' => "platos/{$conFoto->id}.jpg"]);
        Storage::disk('public')->put("platos/{$conFoto->id}.jpg", 'foto');

        $sinFoto = Dish::factory()->for(Category::factory()->create(['nombre' => 'Postres']))->create();

        // One card per dish, and each card carries exactly one dish image.
        $html = $this->get(route('menu.show', $codigo))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, '<article'), 'El menú debería pintar una tarjeta por plato.');
        $this->assertSame(2, substr_count($html, 'data-imagen-respaldo='));

        // The detail shows a single dish image, and it has the same safety net.
        $html = $this->get(route('menu.dish.show', ['code' => $codigo, 'dish' => $sinFoto->id]))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'data-imagen-respaldo='));

        // The fallback is the scene of the category, so a photograph that fails
        // lands on exactly what a dish with no photograph shows from the start.
        $this->assertSame('img/menu/pizzas-'.($conFoto->id % 2 + 1).'.svg', MenuImagen::respaldoParaDish($conFoto));
        $this->assertSame('img/menu/postres-'.($sinFoto->id % 2 + 1).'.svg', MenuImagen::respaldoParaDish($sinFoto));
    }

    public function test_the_fallback_is_the_photo_already_in_the_repository_when_there_is_one(): void
    {
        Storage::fake('public');

        $ruta = public_path('platos/fugazzeta.webp');
        @mkdir(dirname($ruta), 0777, true);
        file_put_contents($ruta, 'contenido de prueba');

        try {
            $plato = Dish::factory()->create(['nombre' => 'Fugazzeta']);

            $this->assertSame('platos/fugazzeta.webp', MenuImagen::respaldoParaDish($plato));
        } finally {
            @unlink($ruta);
        }
    }

    public function test_a_dish_without_a_photo_still_renders_a_picture_and_no_empty_frame(): void
    {
        Storage::fake('public');

        $codigo = QRCode::factory()->for(Table::factory())->create()->code;
        $plato = Dish::factory()->for(Category::factory()->create(['nombre' => 'Bebidas']))->create();

        $this->assertFalse($plato->tieneImagen());
        $this->assertNull($plato->imagenUrl());

        $this->get(route('menu.show', $codigo))
            ->assertOk()
            ->assertSee('/img/menu/bebidas-', escape: false)
            ->assertDontSee('/storage/platos/'.$plato->id, escape: false);

        $this->get(route('menu.dish.show', ['code' => $codigo, 'dish' => $plato->id]))
            ->assertOk()
            ->assertSee('/img/menu/bebidas-', escape: false);
    }
}
