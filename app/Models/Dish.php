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

#[Fillable(['category_id', 'nombre', 'descripcion', 'precio', 'disponible', 'orden'])]
class Dish extends Model
{
    /** @use HasFactory<DishFactory> */
    use HasFactory;

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
