<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\TableStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Model3D;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The numbers shown on the admin dashboard.
 *
 * Every figure is read from the database instead of being kept in a counter
 * column, so a number can never drift away from the rows it summarises. The
 * queries are deliberately plain aggregates: the dashboard is a read-only
 * summary and must stay cheap no matter how large the order history grows.
 */
class PanelEstadisticas
{
    /**
     * How many dishes the "most ordered" list shows.
     */
    public const TOP_PLATOS = 5;

    /**
     * How many recent orders the dashboard lists.
     */
    public const ULTIMOS_PEDIDOS = 6;

    /**
     * Every figure the dashboard renders, in one array.
     *
     * @return array<string, mixed>
     */
    public static function resumen(): array
    {
        return [
            'ingresos' => static::ingresos(),
            'pedidos' => static::pedidos(),
            'mesas' => static::mesas(),
            'menu' => static::menu(),
            'personal' => static::personal(),
            'ultimosPedidos' => static::ultimosPedidos(),
        ];
    }

    /**
     * Money taken from the orders that actually reached the table.
     *
     * Only delivered orders count: a pending or rejected order is not revenue,
     * and adding it up would make the figure change every time the kitchen
     * advances an order. The total is built from `order_items.precio_unitario`,
     * the price frozen when the order was taken, so a later price change never
     * rewrites past revenue.
     */
    public static function ingresos(): float
    {
        return (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.estado', OrderStatus::Entregado->value)
            ->selectRaw('COALESCE(SUM(order_items.cantidad * order_items.precio_unitario), 0) AS total')
            ->value('total');
    }

    /**
     * Order counters, plus the split by status used by the status list.
     *
     * @return array<string, mixed>
     */
    public static function pedidos(): array
    {
        $porEstado = Order::query()
            ->select('estado')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'total' => Order::query()->count(),
            'hoy' => Order::query()->whereDate('created_at', today())->count(),
            'abiertos' => Order::query()->whereIn('estado', OrderStatus::open())->count(),
            // Every status is listed, including the ones with no orders, so the
            // dashboard does not silently change shape as the day goes on.
            'porEstado' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $estado): array => [
                    'estado' => $estado,
                    'total' => (int) ($porEstado[$estado->value] ?? 0),
                ]),
            'topPlatos' => static::topPlatos(),
        ];
    }

    /**
     * The dishes that were actually served most often.
     *
     * @return Collection<int, array{dish: Dish, cantidad: int}>
     */
    public static function topPlatos(): Collection
    {
        $cantidades = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.estado', OrderStatus::Entregado->value)
            ->groupBy('order_items.dish_id')
            ->orderByDesc('total')
            ->orderBy('order_items.dish_id')
            ->limit(static::TOP_PLATOS)
            ->selectRaw('order_items.dish_id, SUM(order_items.cantidad) AS total')
            ->pluck('total', 'dish_id');

        if ($cantidades->isEmpty()) {
            return collect();
        }

        $platos = Dish::query()
            ->whereIn('id', $cantidades->keys())
            ->get()
            ->keyBy('id');

        return $cantidades
            ->map(fn (int|string $cantidad, int|string $dishId): ?array => isset($platos[$dishId])
                ? ['dish' => $platos[$dishId], 'cantidad' => (int) $cantidad]
                : null)
            ->filter()
            ->values();
    }

    /**
     * How many tables there are and how many are being served right now.
     *
     * A table counts as occupied only while it really has an open order, which
     * is the same rule the waiter panel uses to release a table. Reading the
     * stored status instead would show a table as busy after its last order was
     * delivered.
     *
     * @return array<string, int>
     */
    public static function mesas(): array
    {
        $total = Table::query()->count();

        return [
            'total' => $total,
            'ocupadas' => Table::query()
                ->whereIn('estado', [TableStatus::Ocupada->value])
                ->whereHas('orders', fn (Builder $orders) => $orders->whereIn('estado', OrderStatus::open()))
                ->count(),
        ];
    }

    /**
     * Counters for the menu itself.
     *
     * `sinFoto` and `sinModelo` are the dishes that will render a placeholder
     * on the public menu, so they double as a to-do list for the administrator.
     *
     * @return array<string, int>
     */
    public static function menu(): array
    {
        return [
            'categorias' => Category::query()->count(),
            'platos' => Dish::query()->count(),
            'disponibles' => Dish::query()->where('disponible', true)->count(),
            'agotados' => Dish::query()->where('disponible', false)->count(),
            'ingredientes' => Ingredient::query()->count(),
            'sinFoto' => Dish::query()->whereNull('imagen')->count(),
            'sinModelo' => Dish::query()
                ->whereDoesntHave('model3D')
                ->count(),
            'modelos' => Model3D::query()->count(),
        ];
    }

    /**
     * Staff counters.
     *
     * @return array<string, int>
     */
    public static function personal(): array
    {
        return [
            'total' => User::query()->count(),
            'activos' => User::query()->where('activo', true)->count(),
            'administradores' => User::query()
                ->where('role', Role::Administrador->value)
                ->where('activo', true)
                ->count(),
        ];
    }

    /**
     * The last orders, newest first, with just enough to place them.
     *
     * The total is left to `Order::total()` rather than repeated as a raw sum
     * here: the model already owns that calculation, and a second copy of it in
     * a query string is one more place for the two to disagree. Six orders with
     * their items is a cheap amount to load.
     *
     * @return Collection<int, Order>
     */
    public static function ultimosPedidos(): Collection
    {
        return Order::query()
            ->with(['table', 'items'])
            ->latest()
            ->limit(static::ULTIMOS_PEDIDOS)
            ->get();
    }
}
