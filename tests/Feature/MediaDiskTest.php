<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Model3D;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * The same image must work in two very different places: the local disk served
 * by Apache under `/storage`, and a bucket that lives outside the container.
 * What decides it is `menuvision.disk`, never the code, so these tests pin that
 * every read and every write goes through the configured disk.
 */
class MediaDiskTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://proyecto.supabase.co/storage/v1/object/public/menuvision';

    public function test_the_photo_url_comes_from_the_configured_disk(): void
    {
        config(['menuvision.disk' => 'supabase']);
        Storage::fake('supabase', ['url' => self::URL]);

        $plato = Dish::factory()->create(['imagen' => 'platos/7.jpg']);
        Storage::disk('supabase')->put('platos/7.jpg', 'foto');

        $this->assertSame(self::URL.'/platos/7.jpg', $plato->imagenUrl());
    }

    public function test_the_model_url_comes_from_the_configured_disk(): void
    {
        config(['menuvision.disk' => 'supabase']);
        Storage::fake('supabase', ['url' => self::URL]);

        $modelo = new Model3D(['archivo' => 'models/7.glb']);

        $this->assertSame(self::URL.'/models/7.glb', $modelo->url());
    }

    public function test_uploads_land_on_the_configured_disk_and_not_on_the_public_one(): void
    {
        config(['menuvision.disk' => 'supabase']);
        Storage::fake('supabase', ['url' => self::URL]);
        Storage::fake('public');

        $categoria = Category::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.dishes.store'), [
                'category_id' => $categoria->id,
                'nombre' => 'Napolitana',
                'descripcion' => 'Tomate y mozzarella.',
                'precio' => '8500.00',
                'orden' => 1,
                'disponible' => 1,
                'imagen' => UploadedFile::fake()->image('napolitana.jpg'),
                'modelo' => UploadedFile::fake()->createWithContent('pizza.glb', "glTF\x02\x00\x00\x00resto"),
            ])
            ->assertRedirect(route('admin.dishes.index'));

        $plato = Dish::sole();

        Storage::disk('supabase')->assertExists("platos/{$plato->id}.jpg");
        Storage::disk('supabase')->assertExists("models/{$plato->id}.glb");
        Storage::disk('public')->assertMissing("platos/{$plato->id}.jpg");
        Storage::disk('public')->assertMissing("models/{$plato->id}.glb");
    }

    public function test_production_refuses_to_start_without_the_bucket_credentials(): void
    {
        config([
            'menuvision.disk' => 'supabase',
            'filesystems.disks.supabase.key' => null,
            'filesystems.disks.supabase.secret' => null,
            'filesystems.disks.supabase.bucket' => 'menuvision',
            'filesystems.disks.supabase.region' => 'us-east-1',
            'filesystems.disks.supabase.endpoint' => 'https://proyecto.supabase.co/storage/v1/s3',
            'filesystems.disks.supabase.url' => self::URL,
        ]);

        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY');

        (new AppServiceProvider($this->app))->boot();
    }

    public function test_production_refuses_a_bucket_url_that_is_not_public(): void
    {
        config([
            'menuvision.disk' => 'supabase',
            'filesystems.disks.supabase.key' => 'clave',
            'filesystems.disks.supabase.secret' => 'secreto',
            'filesystems.disks.supabase.bucket' => 'menuvision',
            'filesystems.disks.supabase.region' => 'us-east-1',
            'filesystems.disks.supabase.endpoint' => 'https://proyecto.supabase.co/storage/v1/s3',
            'filesystems.disks.supabase.url' => 'https://proyecto.supabase.co/storage/v1/s3',
        ]);

        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AWS_URL');

        (new AppServiceProvider($this->app))->boot();
    }
}
