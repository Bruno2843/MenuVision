<?php

namespace App\Models;

use Database\Factories\DishFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['category_id', 'nombre', 'descripcion', 'imagen', 'precio', 'disponible', 'orden'])]
class Dish extends Model
{
    /** @use HasFactory<DishFactory> */
    use HasFactory;

    /**
     * Default disk holding the photographs. It is the public disk so the
     * browser can fetch them directly, the same reason the 3D models live
     * there. The disk actually used is resolved by {@see static::disco()}, so
     * production can point it at Supabase Storage without touching the code.
     */
    public const DISCO = 'public';

    /**
     * Directory inside the disk where uploaded photographs are written, named
     * after the dish id so two dishes never share a file.
     */
    public const CARPETA_IMAGENES = 'platos';

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Ingredient>
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class);
    }

    /**
     * @return HasOne<Model3D>
     */
    public function model3D(): HasOne
    {
        return $this->hasOne(Model3D::class);
    }

    /**
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Public URL of the photograph this dish owns, or null when it has none.
     *
     * The path is resolved against the disk rather than echoed from the column,
     * so a dish whose file was removed from the server keeps rendering its
     * fallback scene instead of a broken image.
     */
    public function imagenUrl(): ?string
    {
        if (! $this->tieneImagen()) {
            return null;
        }

        return Storage::disk(static::disco())->url($this->rutaImagenEnDisco());
    }

    /**
     * Whether the photograph really is on the disk.
     */
    public function tieneImagen(): bool
    {
        return filled($ruta = $this->rutaImagenEnDisco())
            && Storage::disk(static::disco())->exists($ruta);
    }

    /**
     * The disk the photographs are stored on, as configured for this
     * environment: `public` locally, `supabase` in production.
     */
    public static function disco(): string
    {
        return (string) config('menuvision.disk', static::DISCO);
    }

    /**
     * The photograph path as the disk knows it, whatever the column happens to
     * hold.
     *
     * Uploads store a plain relative path such as `platos/8.jpg`. Rows written
     * before that, or from outside the panel, can hold the same file written as a
     * server path (`C:\...\storage\app\public\platos\8.jpg`), as a path relative
     * to `public` (`storage/platos/8.jpg`) or with `\` separators, and each of
     * those is a valid file that Flysystem would simply report as missing. Only
     * the lookup is normalised; the column is left exactly as it was written, so
     * nothing about the existing rows changes and no data is rewritten to make
     * the menu work.
     */
    public function rutaImagenEnDisco(): ?string
    {
        $ruta = $this->imagen;

        if (blank($ruta)) {
            return null;
        }

        $ruta = str_replace('\\', '/', trim($ruta));

        // Everything up to and including the disk root is noise: the bytes live
        // under `storage/app/public/`, wherever the project happens to sit.
        foreach (['/storage/app/public/', 'storage/app/public/'] as $prefijo) {
            if (str_contains($ruta, $prefijo)) {
                $ruta = Str::after($ruta, $prefijo);

                break;
            }
        }

        // A path relative to `public`, where the served copy of the file is.
        foreach (['/storage/', 'storage/', '/public/', 'public/'] as $prefijo) {
            if (str_starts_with($ruta, $prefijo)) {
                $ruta = Str::after($ruta, $prefijo);

                break;
            }
        }

        $ruta = ltrim($ruta, '/');

        return $ruta === '' ? null : $ruta;
    }

    /**
     * Order the dishes the way they should appear inside their category.
     *
     * @param  Builder<Dish>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * @param  Builder<Dish>  $query
     */
    public function scopeDisponibles(Builder $query): void
    {
        $query->where('disponible', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'disponible' => 'boolean',
            'orden' => 'integer',
        ];
    }
}
