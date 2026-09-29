<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'orden'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Dish>
     */
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    /**
     * Order the categories the way they should appear on the menu.
     *
     * @param  Builder<Category>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Only include the categories that have at least one available dish.
     *
     * @param  Builder<Category>  $query
     */
    public function scopeConPlatosDisponibles(Builder $query): void
    {
        $query->whereHas('dishes', fn (Builder $dishes) => $dishes->where('disponible', true));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
        ];
    }
}
