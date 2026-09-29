<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['table_id', 'estado', 'observaciones', 'motivo_devolucion'])]
class Order extends Model
{
    /**
     * The note the kitchen reads when the waiter sends an order back exactly
     * as it was, so the dish does not have to be changed.
     */
    public const OBSERVACION_REENVIO = 'Preparar de igual manera';

    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Table, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    /**
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Whether the waiter can send this order back to the kitchen. Only an
     * order the kitchen handed back qualifies, since that is the one whose
     * dishes may still need changing.
     */
    public function puedeReenviarse(): bool
    {
        return $this->estado === OrderStatus::SinInsumos;
    }

    /**
     * The order total, calculated from the unit prices frozen at order time.
     */
    public function total(): float
    {
        return (float) $this->items->sum(
            fn (OrderItem $item): float => $item->cantidad * (float) $item->precio_unitario
        );
    }

    /**
     * Release the table once nothing is being served on it any more, which
     * happens when the last order of the table is rejected or delivered.
     *
     * The relation is resolved by calling the method on purpose: inside the
     * model `$this->table` would read Model's protected `$table` property
     * (the table name) instead of triggering the relation.
     */
    public function freeTableIfNoOpenOrders(): void
    {
        $table = $this->table()->first();

        if ($table && ! $table->hasOpenOrders()) {
            $table->update(['estado' => TableStatus::Libre]);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => OrderStatus::class,
        ];
    }
}
