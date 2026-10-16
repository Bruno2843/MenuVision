<?php

namespace App\Http\Controllers\Waiter;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Waiter\ResendOrderRequest;
use App\Http\Requests\Waiter\StoreOrderRequest;
use App\Http\Requests\Waiter\UpdateOrderStatusRequest;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Support\TableQr;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Show the waiter's orders dashboard inside the staff layout.
     */
    public function index(): View
    {
        return view('waiter.dashboard', $this->boardData());
    }

    /**
     * Return only the live part of the dashboard, polled by the front end so
     * the waiter sees the kitchen advance orders without reloading.
     */
    public function board(): View
    {
        return view('waiter.partials.board', $this->boardData());
    }

    /**
     * The data behind the waiter dashboard, shared by the full page and the
     * polled fragment.
     *
     * @return array<string, mixed>
     */
    private function boardData(): array
    {
        $tables = Table::query()
            ->orderBy('numero')
            ->with(['orders' => fn (Relation $orders) => $orders->latest()])
            ->get();

        return [
            'tables' => $tables,
            'orders' => Order::query()
                ->with('table', 'items')
                ->latest()
                ->get(),
        ];
    }

    /**
     * Show the form to take a new order for a table.
     *
     * The table comes from the scanner when the form is reached from it: the
     * query parameter is the code the camera read, and the table is resolved
     * from the stored QR here rather than accepted as an id, so the form cannot
     * be pointed at a table the waiter never scanned. A code that does not
     * resolve sends the waiter back to the dashboard with the reason instead of
     * showing an order form for no table.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $codigo = trim((string) $request->query('codigo', ''));
        $table = $codigo === '' ? null : TableQr::resolver($codigo)['table'] ?? null;

        if ($codigo !== '' && $table === null) {
            return redirect()
                ->route('waiter.dashboard')
                ->with('error', 'Ese código QR no corresponde a ninguna mesa.');
        }

        $categories = Category::query()
            ->ordered()
            ->with(['dishes' => fn (Relation $dishes) => $dishes->disponibles()->ordered()])
            ->get()
            ->filter(fn (Category $category): bool => $category->dishes->isNotEmpty());

        return view('waiter.orders.create', [
            'categories' => $categories,
            'tables' => $table === null ? Table::query()->orderBy('numero')->get() : collect([$table]),
            // Non-null when the form was reached through the scanner: the table
            // is then shown as resolved and cannot be changed.
            'table' => $table,
            'codigoQr' => $codigo === '' ? null : $codigo,
        ]);
    }

    /**
     * Store a new order with the dishes chosen by the waiter.
     *
     * The table is the one the request resolves (from the scanned code when there
     * is one) and the waiter is the signed-in account, never a value the browser
     * supplied.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated, $request): Order {
            $order = Order::create([
                'table_id' => $request->tableId(),
                'user_id' => $request->user()->id,
                'estado' => OrderStatus::Pendiente,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            $dishes = Dish::whereIn('id', array_column($validated['items'], 'dish_id'))->get();

            foreach ($validated['items'] as $item) {
                $dish = $dishes->firstWhere('id', $item['dish_id']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'dish_id' => $dish->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $dish->precio,
                    'observaciones' => $item['observaciones'] ?? null,
                ]);
            }

            $table = $order->table;
            $table->update(['estado' => TableStatus::Ocupada]);

            return $order;
        });

        return redirect()
            ->route('waiter.orders.show', $order)
            ->with('status', 'Pedido registrado.');
    }

    /**
     * Show the form where the waiter adjusts what a handed back order is made
     * of before sending it to the kitchen again.
     */
    public function resendForm(Order $order): View|RedirectResponse
    {
        if (! $order->puedeReenviarse()) {
            return $this->resendNoDisponible($order);
        }

        $order->load(['table', 'items.dish']);

        $enElPedido = $order->items->pluck('dish_id')->all();

        $categorias = Category::query()
            ->ordered()
            ->with(['dishes' => fn (Relation $dishes) => $dishes->disponibles()->ordered()])
            ->get()
            ->map(function (Category $category) use ($enElPedido): array {
                // A dish already on the order is edited in its own table, so it
                // is left out here to avoid rendering two inputs for the same
                // field name.
                $platos = $category->dishes
                    ->reject(fn (Dish $dish): bool => in_array($dish->id, $enElPedido, true))
                    ->values();

                return [
                    'nombre' => $category->nombre,
                    'lineas' => $platos->map(fn (Dish $dish): array => $this->linea($dish, 0, (float) $dish->precio))->values(),
                ];
            })
            ->filter(fn (array $categoria): bool => $categoria['lineas']->isNotEmpty())
            ->values();

        return view('waiter.orders.resend', [
            'order' => $order,
            // The dishes already on the order keep the price they were frozen
            // at, and can be kept even if they are now sold out.
            'lineas' => $order->items
                ->map(fn (OrderItem $item): array => $this->linea(
                    $item->dish,
                    $item->cantidad,
                    (float) $item->precio_unitario,
                    ! $item->dish->disponible,
                ))
                ->values(),
            'categorias' => $categorias,
        ]);
    }

    /**
     * Send a handed back order to the kitchen again with the dishes the waiter
     * settled on, keeping the unit price of every dish the order already held
     * and freezing the current price only for the ones just added.
     */
    public function resend(ResendOrderRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($order, $validated, $request): void {
            $pedidos = collect($validated['items'])->keyBy('dish_id');
            $existentes = $order->items()->get()->keyBy('dish_id');

            $existentes
                ->reject(fn (OrderItem $item): bool => $pedidos->has($item->dish_id))
                ->each->delete();

            $existentes->each(function (OrderItem $item) use ($pedidos): void {
                if ($pedidos->has($item->dish_id)) {
                    $item->update(['cantidad' => $pedidos[$item->dish_id]['cantidad']]);
                }
            });

            Dish::whereIn('id', $pedidos->keys()->diff($existentes->keys())->all())
                ->get()
                ->each(function (Dish $dish) use ($order, $pedidos): void {
                    $order->items()->create([
                        'dish_id' => $dish->id,
                        'cantidad' => $pedidos[$dish->id]['cantidad'],
                        'precio_unitario' => $dish->precio,
                    ]);
                });

            $order->update([
                'estado' => OrderStatus::Confirmado,
                'motivo_devolucion' => null,
                'observaciones' => $this->observacionesDelReenvio(
                    $validated['observaciones'] ?? null,
                    $request->boolean('preparar_igual'),
                ),
            ]);
        });

        return redirect()
            ->route('waiter.orders.show', $order)
            ->with('status', 'Pedido reenviado a cocina.');
    }

    /**
     * Build the observation the kitchen will read, adding the standard note
     * when the waiter confirms the dishes did not change.
     */
    private function observacionesDelReenvio(?string $observaciones, bool $prepararIgual): ?string
    {
        if (! $prepararIgual) {
            return $observaciones;
        }

        return $observaciones
            ? $observaciones.' — '.Order::OBSERVACION_REENVIO
            : Order::OBSERVACION_REENVIO;
    }

    /**
     * Turn a dish into the row the quantity tables render, so the view only
     * has to lay it out.
     *
     * @return array{dish: Dish, cantidad: int, precio: float, agotado: bool}
     */
    private function linea(Dish $dish, int $cantidad, float $precio, bool $agotado = false): array
    {
        return compact('dish', 'cantidad', 'precio', 'agotado');
    }

    /**
     * The order is not a handback any more, so there is nothing to resend.
     */
    private function resendNoDisponible(Order $order): RedirectResponse
    {
        return redirect()
            ->route('waiter.orders.show', $order)
            ->with('error', 'Este pedido ya no se puede reenviar a cocina.');
    }

    /**
     * Show a single order with its items.
     */
    public function show(Order $order): View
    {
        $order->load(['table', 'items.dish']);

        return view('waiter.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Confirm a pending order, reject it, or resolve one the kitchen handed
     * back. Rejecting releases the table when the rejected order was the only
     * one still being served on it.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $estado = OrderStatus::from($request->validated('estado'));

        $order->update([
            'estado' => $estado,
            // Re-sending clears the handback reason; a rejection keeps it as
            // the record of why the order never made it to the table.
            'motivo_devolucion' => $estado === OrderStatus::Confirmado
                ? null
                : $order->motivo_devolucion,
        ]);

        $order->freeTableIfNoOpenOrders();

        return redirect()
            ->route('waiter.orders.show', $order)
            ->with('status', 'Pedido actualizado.');
    }
}
