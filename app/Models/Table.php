<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use Database\Factories\TableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['numero', 'estado'])]
class Table extends Model
{
    /** @use HasFactory<TableFactory> */
    use HasFactory;

    /**
     * @return HasMany<Order>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasOne<QRCode>
     */
    public function qrCode(): HasOne
    {
        return $this->hasOne(QRCode::class);
    }

    /**
     * Whether the table still has orders the staff are working on. A table
     * without open orders can be released.
     */
    public function hasOpenOrders(): bool
    {
        return $this->orders()
            ->whereIn('estado', OrderStatus::open())
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => TableStatus::class,
        ];
    }
}
