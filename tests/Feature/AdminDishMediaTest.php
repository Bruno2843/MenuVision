<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Model3D;
use App\Models\User;
use App\Support\MenuImagen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Photos and GLB files are the only place where the panel writes to the disk,
 * so these tests pin where the bytes end up and, just as importantly, that a
 * rejected or failed request never leaves a file behind that no row points to.
 */
class AdminDishMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_a_dish_is_created_with_its_photo_on_the_public_disk(): void
    {
        $categoria = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'imagen' => UploadedFile::fake()->image('napolitana.jpg'),
        ]))->assertRedirect(route('admin.dishes.index'));

        $plato = Dish::sole();

        Storage::disk('public')->assertExists("platos/{$plato->id}.jpg");
        $this->assertSame("platos/{$plato->id}.jpg", $plato->imagen);
        $this->assertTrue($plato->tieneImagen());
    }

    public function test_the_new_photo_is_the_one_the_public_menu_shows(): void
    {
        $categoria = Category::factory()->create();
        Storage::disk('public')->put('platos/subida.jpg', 'foto');

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'nombre' => 'Fugazzeta',
            'imagen' => UploadedFile::fake()->image('subida.jpg'),
        ]));

        $plato = Dish::sole();

        $this->assertSame(Storage::disk('public')->url("platos/{$plato->id}.jpg"), MenuImagen::paraDish($plato));
    }

    public function test_a_dish_is_created_with_a_webp_photo(): void
    {
        $categoria = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'imagen' => UploadedFile::fake()->image('napolitana.webp'),
        ]))->assertRedirect(route('admin.dishes.index'));

        $plato = Dish::sole();

        // The extension comes from the detected content type, so a .webp upload
        // does not land on disk as a .jpg with the wrong bytes inside it.
        Storage::disk('public')->assertExists("platos/{$plato->id}.webp");
        $this->assertSame("platos/{$plato->id}.webp", $plato->imagen);
    }

    public function test_a_dish_is_created_with_a_png_photo(): void
    {
        $categoria = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'imagen' => UploadedFile::fake()->image('napolitana.png'),
        ]))->assertRedirect(route('admin.dishes.index'));

        $plato = Dish::sole();

        Storage::disk('public')->assertExists("platos/{$plato->id}.png");
        $this->assertSame("platos/{$plato->id}.png", $plato->imagen);
    }

    public function test_the_stored_path_is_relative_to_the_disk_and_never_a_server_path(): void
    {
        $categoria = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'imagen' => UploadedFile::fake()->image('napolitana.jpg'),
        ]));

        $ruta = Dish::sole()->getRawOriginal('imagen');

        $this->assertStringStartsWith('platos/', $ruta);
        $this->assertStringNotContainsString('\\', $ruta);
        $this->assertStringNotContainsString(':', $ruta);
        $this->assertStringNotContainsString('storage', $ruta);
        $this->assertDatabaseHas('dishes', ['imagen' => $ruta]);
    }

    public function test_editing_a_dish_without_choosing_a_new_photo_keeps_the_one_it_has(): void
    {
        $plato = Dish::factory()->create(['imagen' => 'platos/keeps.jpg']);
        Storage::disk('public')->put('platos/keeps.jpg', 'la foto buena');

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'nombre' => 'Fetuccini Alfredo',
        ]))->assertRedirect(route('admin.dishes.index'));

        $this->assertSame('Fetuccini Alfredo', $plato->fresh()->nombre);
        $this->assertSame('platos/keeps.jpg', $plato->fresh()->imagen);
        Storage::disk('public')->assertExists('platos/keeps.jpg');
        $this->assertSame('la foto buena', Storage::disk('public')->get('platos/keeps.jpg'));
    }

    public function test_a_photo_over_the_size_limit_is_rejected_and_never_stored(): void
    {
        $categoria = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'imagen' => UploadedFile::fake()->image('enorme.jpg')->size(5000),
        ]))->assertSessionHasErrors([
            'imagen' => 'La foto no puede pesar más de 4 MB. Bajala de resolución y volvé a subirla.',
        ]);

        $this->assertModelMissing(Dish::class);
        $this->assertEmpty(Storage::disk('public')->allFiles('platos'));
    }

    public function test_a_photo_stored_as_a_server_path_still_shows_and_is_never_rewritten_on_its_own(): void
    {
        // A row written before uploads were named after the dish can hold the
        // very same file as an absolute Windows path. The file is the same one, so
        // the menu has to show it without the row having to be touched.
        $plato = Dish::factory()->create();
        $plato->update(['imagen' => 'C:\\laragon\\www\\storage\\app\\public\\platos\\'.$plato->id.'.jpg']);
        Storage::disk('public')->put("platos/{$plato->id}.jpg", 'foto');

        $this->assertSame("platos/{$plato->id}.jpg", $plato->rutaImagenEnDisco());
        $this->assertTrue($plato->tieneImagen());
        $this->assertSame('/storage/platos/'.$plato->id.'.jpg', $plato->imagenUrl());

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'descripcion' => 'Sin cambios en la foto.',
        ]))->assertRedirect(route('admin.dishes.index'));

        Storage::disk('public')->assertExists("platos/{$plato->id}.jpg");

        // The normalisation happens on the lookup, and the saved column ends up
        // holding the same file by its relative path instead of by a path that
        // only means something on the machine that uploaded it.
        $this->assertSame("platos/{$plato->id}.jpg", $plato->fresh()->imagen);
    }

    public function test_a_photo_another_dish_still_points_at_is_never_deleted(): void
    {
        // Rows written before files were named after the dish id can share one
        // path. Deleting the file when the first of them goes would blank the
        // photograph of the second without anyone having touched it.
        $compartida = 'platos/compartida.jpg';
        Storage::disk('public')->put($compartida, 'foto compartida');

        $primero = Dish::factory()->create(['imagen' => $compartida]);
        $segundo = Dish::factory()->create(['imagen' => $compartida]);

        $this->actingAs($this->admin())
            ->delete(route('admin.dishes.destroy', $primero))
            ->assertRedirect(route('admin.dishes.index'));

        Storage::disk('public')->assertExists($compartida);
        $this->assertTrue($segundo->fresh()->tieneImagen());
    }

    public function test_replacing_a_photo_deletes_the_file_it_replaced(): void
    {
        $plato = Dish::factory()->create(['imagen' => 'platos/vieja.png']);
        Storage::disk('public')->put('platos/vieja.png', 'foto vieja');

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'imagen' => UploadedFile::fake()->image('nueva.png'),
        ]))->assertRedirect(route('admin.dishes.index'));

        Storage::disk('public')->assertMissing('platos/vieja.png');
        Storage::disk('public')->assertExists("platos/{$plato->id}.png");
        $this->assertSame("platos/{$plato->id}.png", $plato->fresh()->imagen);
    }

    public function test_removing_a_photo_clears_the_column_and_the_disk(): void
    {
        $plato = Dish::factory()->create(['imagen' => 'platos/actual.jpg']);
        Storage::disk('public')->put('platos/actual.jpg', 'foto');

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'quitar_imagen' => 1,
        ]))->assertRedirect(route('admin.dishes.index'));

        Storage::disk('public')->assertMissing('platos/actual.jpg');
        $this->assertNull($plato->fresh()->imagen);
    }

    public function test_a_file_that_is_not_an_image_is_rejected_and_never_stored(): void
    {
        $categoria = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'imagen' => UploadedFile::fake()->create('programa.php', 8, 'application/x-php'),
        ]))->assertSessionHasErrors('imagen');

        $this->assertModelMissing(Dish::class);
        $this->assertEmpty(Storage::disk('public')->allFiles('platos'));
    }

    public function test_a_valid_glb_is_stored_and_attached_to_the_dish(): void
    {
        $categoria = Category::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'modelo' => $this->glb('pizza.glb'),
        ]))->assertRedirect(route('admin.dishes.index'));

        $plato = Dish::sole();

        Storage::disk('public')->assertExists("models/{$plato->id}.glb");
        $this->assertDatabaseHas('model3ds', [
            'dish_id' => $plato->id,
            'archivo' => "models/{$plato->id}.glb",
        ]);
    }

    public function test_a_model_over_the_size_limit_is_rejected_and_never_stored(): void
    {
        $categoria = Category::factory()->create();

        // 101 MB, one over the limit. The limit is well above what a browser
        // will hold, but a GLB with uncompressed textures is routinely that big,
        // so the check has to stay reachable rather than sitting at 20 MB.
        $enorme = UploadedFile::fake()->createWithContent('enorme.glb', 'glTF')->size(101 * 1024);

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'modelo' => $enorme,
        ]))->assertSessionHasErrors('modelo');

        $this->assertModelMissing(Dish::class);
        $this->assertModelMissing(Model3D::class);
        $this->assertEmpty(Storage::disk('public')->allFiles('models'));
    }

    public function test_a_heavy_but_allowed_model_is_accepted(): void
    {
        $categoria = Category::factory()->create();

        // 40 MB: over the old 20 MB ceiling and inside the current one. Faking
        // the size only writes the declared size, so this exercises the rule
        // without writing 40 MB to disk.
        $pesado = $this->glb('pesado.glb')->size(40 * 1024);

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'modelo' => $pesado,
        ]))->assertRedirect(route('admin.dishes.index'));

        $this->assertSame(1, Model3D::count());
    }

    public function test_a_file_with_the_glb_extension_but_the_wrong_bytes_is_rejected(): void
    {
        $categoria = Category::factory()->create();
        $falso = UploadedFile::fake()->createWithContent('falso.glb', 'esto no es un glb');

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'modelo' => $falso,
        ]))->assertSessionHasErrors('modelo');

        $this->assertModelMissing(Dish::class);
        $this->assertModelMissing(Model3D::class);
        $this->assertEmpty(Storage::disk('public')->allFiles('models'));
    }

    public function test_replacing_a_model_deletes_the_file_it_replaced(): void
    {
        $plato = Dish::factory()->create();
        $modelo = Model3D::factory()->for($plato)->create(['archivo' => 'models/anterior.glb']);
        Storage::disk('public')->put('models/anterior.glb', 'glb viejo');

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'modelo' => $this->glb('nuevo.glb'),
        ]))->assertRedirect(route('admin.dishes.index'));

        Storage::disk('public')->assertMissing('models/anterior.glb');
        Storage::disk('public')->assertExists("models/{$plato->id}.glb");
        $this->assertSame(1, Model3D::query()->count());
        $this->assertSame("models/{$plato->id}.glb", $modelo->fresh()->archivo);
    }

    public function test_a_model_can_be_removed_from_the_dish(): void
    {
        $plato = Dish::factory()->create();
        $modelo = Model3D::factory()->for($plato)->create(['archivo' => "models/{$plato->id}.glb"]);
        Storage::disk('public')->put("models/{$plato->id}.glb", 'glb');

        $this->actingAs($this->admin())
            ->from(route('admin.dishes.edit', $plato))
            ->delete(route('admin.dishes.model.destroy', $plato))
            ->assertRedirect(route('admin.dishes.edit', $plato))
            ->assertSessionHas('status');

        Storage::disk('public')->assertMissing("models/{$plato->id}.glb");
        $this->assertModelMissing($modelo);
    }

    public function test_destroying_a_dish_removes_its_photo_and_its_model(): void
    {
        $plato = Dish::factory()->create(['imagen' => 'platos/borrar.jpg']);
        Model3D::factory()->for($plato)->create(['archivo' => "models/{$plato->id}.glb"]);

        Storage::disk('public')->put('platos/borrar.jpg', 'foto');
        Storage::disk('public')->put("models/{$plato->id}.glb", 'glb');

        $this->actingAs($this->admin())
            ->delete(route('admin.dishes.destroy', $plato))
            ->assertRedirect(route('admin.dishes.index'));

        Storage::disk('public')->assertMissing('platos/borrar.jpg');
        Storage::disk('public')->assertMissing("models/{$plato->id}.glb");
        $this->assertModelMissing($plato);
        $this->assertModelMissing(Model3D::class);
    }

    public function test_a_rejected_upload_keeps_the_file_the_dish_already_has(): void
    {
        $plato = Dish::factory()->create(['imagen' => 'platos/actual.jpg']);
        Storage::disk('public')->put('platos/actual.jpg', 'foto buena');

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'nombre' => '',
            'imagen' => UploadedFile::fake()->create('ataque.php', 8),
        ]))->assertSessionHasErrors('nombre');

        Storage::disk('public')->assertExists('platos/actual.jpg');
        $this->assertSame('platos/actual.jpg', $plato->fresh()->imagen);
    }

    public function test_a_failed_update_never_destroys_the_file_the_dish_already_points_at(): void
    {
        $plato = Dish::factory()->create();
        $plato->update(['imagen' => "platos/{$plato->id}.jpg"]);
        Storage::disk('public')->put("platos/{$plato->id}.jpg", 'la foto que tenia antes');

        // Make the row write fail the way a real error would: the new photo is
        // stored at the very same path, because the file is named after the id.
        Dish::updating(function (): void {
            throw new RuntimeException('fallo la base de datos');
        });

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'imagen' => UploadedFile::fake()->image('nueva.jpg'),
        ]));

        Storage::disk('public')->assertExists("platos/{$plato->id}.jpg");
        $this->assertSame('la foto que tenia antes', Storage::disk('public')->get("platos/{$plato->id}.jpg"));
    }

    public function test_a_failed_update_never_destroys_the_model_the_dish_already_points_at(): void
    {
        $plato = Dish::factory()->create();
        Model3D::factory()->for($plato)->create(['archivo' => "models/{$plato->id}.glb"]);
        Storage::disk('public')->put("models/{$plato->id}.glb", 'el modelo que tenia antes');

        // The model always lands on the same `models/{id}.glb`, so writing the
        // replacement overwrites the file the row still points at. This listener
        // fails the very write that records it, which is the moment where the old
        // bytes are already gone.
        Model3D::saving(function (): void {
            throw new RuntimeException('fallo la base de datos');
        });

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'modelo' => $this->glb('nuevo.glb'),
        ]));

        Storage::disk('public')->assertExists("models/{$plato->id}.glb");
        $this->assertSame('el modelo que tenia antes', Storage::disk('public')->get("models/{$plato->id}.glb"));
    }

    public function test_a_failure_while_attaching_ingredients_leaves_the_photo_alone(): void
    {
        $plato = Dish::factory()->create();
        $plato->update(['imagen' => "platos/{$plato->id}.jpg"]);
        Storage::disk('public')->put("platos/{$plato->id}.jpg", 'la foto que tenia antes');

        $ingrediente = Ingredient::factory()->create();

        // Fails on the pivot write, which is the last thing the transaction does
        // and therefore the one failure that lands after the new photo exists.
        DB::listen(function ($consulta): void {
            if (str_contains($consulta->sql, 'dish_ingredient')) {
                throw new RuntimeException('fallo la base de datos');
            }
        });

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'imagen' => UploadedFile::fake()->image('nueva.jpg'),
            'ingredientes' => [$ingrediente->id],
        ]));

        Storage::disk('public')->assertExists("platos/{$plato->id}.jpg");
        $this->assertSame('la foto que tenia antes', Storage::disk('public')->get("platos/{$plato->id}.jpg"));

        // The parked copy must not survive as an orphan, while the photo the row
        // still points at has to be the only thing left in the folder.
        $this->assertSame(
            ["platos/{$plato->id}.jpg"],
            Storage::disk('public')->allFiles('platos'),
        );
    }

    public function test_the_ingredients_picked_in_the_form_are_attached_to_the_dish(): void
    {
        $masa = Ingredient::factory()->create(['nombre' => 'Masa']);
        $queso = Ingredient::factory()->create(['nombre' => 'Queso']);

        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'ingredientes' => [$queso->id, $masa->id],
        ]))->assertRedirect(route('admin.dishes.index'));

        $plato = Dish::sole();

        $this->assertEqualsCanonicalizing(
            [$masa->id, $queso->id],
            $plato->ingredients->pluck('id')->all(),
        );
    }

    public function test_editing_a_dish_replaces_its_ingredients_instead_of_appending_to_them(): void
    {
        $plato = Dish::factory()->create();
        $masa = Ingredient::factory()->create();
        $queso = Ingredient::factory()->create();

        $plato->ingredients()->sync([$masa->id]);

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'ingredientes' => [$queso->id],
        ]))->assertRedirect(route('admin.dishes.index'));

        $this->assertSame([$queso->id], $plato->fresh()->ingredients->pluck('id')->all());
    }

    public function test_the_hidden_zero_comes_before_the_available_checkbox(): void
    {
        // PHP keeps the last value sent for a repeated field name. With the
        // hidden "0" after the checkbox, a ticked box would still arrive as 0 and
        // every dish would silently be saved as agotado, so the order in the markup
        // is part of the contract rather than a cosmetic detail.
        $categoria = Category::factory()->create();
        Dish::factory()->for($categoria)->create();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dishes.edit', Dish::sole()))
            ->assertOk()
            ->getContent();

        $oculto = strpos($html, '<input type="hidden" name="disponible" value="0">');
        $casilla = strpos($html, 'name="disponible"');

        $this->assertIsInt($oculto);
        $this->assertIsInt($casilla);
        $this->assertLessThan($casilla, $oculto, 'El input oculto tiene que ir antes del checkbox.');
    }

    public function test_ticking_and_unticking_the_available_box_is_persisted(): void
    {
        $this->actingAs($this->admin())->post(route('admin.dishes.store'), $this->payload([
            'disponible' => 1,
        ]));
        $this->assertTrue(Dish::sole()->disponible);

        $plato = Dish::sole();

        $this->actingAs($this->admin())->put(route('admin.dishes.update', $plato), $this->payload([
            'disponible' => 0,
        ]));

        $this->assertFalse($plato->fresh()->disponible);
    }

    public function test_the_dish_editor_shows_the_photo_and_the_model_it_already_has(): void
    {
        $categoria = Category::factory()->create();
        $plato = Dish::factory()->for($categoria)->create(['imagen' => 'platos/editor.jpg']);
        Model3D::factory()->for($plato)->create(['archivo' => 'models/editor.glb']);
        Ingredient::factory()->create(['nombre' => 'Oregano']);

        Storage::disk('public')->put('platos/editor.jpg', 'foto');
        Storage::disk('public')->put('models/editor.glb', 'glb');

        $this->actingAs($this->admin())
            ->get(route('admin.dishes.edit', $plato))
            ->assertOk()
            ->assertSee('Oregano')
            ->assertSee('Quitar la foto y volver a la imagen de la categoría')
            ->assertSee('Reemplazar el modelo');

        $this->actingAs($this->admin())
            ->get(route('admin.dishes.create'))
            ->assertOk()
            ->assertSee('Subir una foto')
            ->assertSee('Subir un modelo');
    }

    /**
     * The fields every dish form posts, plus whatever a given test changes.
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function payload(array $cambios = []): array
    {
        $categoria = Category::query()->first() ?? Category::factory()->create();

        return array_merge([
            'category_id' => $categoria->id,
            'nombre' => 'Napolitana',
            'descripcion' => 'Tomate, mozzarella y albahaca.',
            'precio' => '8500.00',
            'orden' => 1,
            'disponible' => 1,
        ], $cambios);
    }

    /**
     * A file whose first bytes say "glTF", which is all the check can look at.
     */
    private function glb(string $nombre = 'modelo.glb'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, "glTF\x02\x00\x00\x00resto-del-modelo");
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
