<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    /**
     * List the orders, newest first, with optional filters.
     *
     * This screen only reads. The waiter confirms and rejects, the kitchen
     * advances and hands back; an administrator moving an order from here would
     * put a third pair of hands on a state machine the other two panels share,
     * and would skip the table bookkeeping those transitions perform.
     */
    public function index(Request $request): View
    {
        $desde = $this->fechaDesde($request);

        $orders = Order::query()
            ->with(['table', 'items'])
            ->when(
                $request->filled('estado'),
                fn (Builder $query) => $query->where('estado', $request->string('estado')->toString()),
            )
            ->when(
                $request->filled('mesa'),
                fn (Builder $query) => $query->where('table_id', $request->integer('mesa')),
            )
            ->when(
                $desde !== null,
                fn (Builder $query) => $query->whereDate('created_at', '>=', $desde),
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'tables' => Table::query()->orderBy('numero')->get(),
            'estados' => OrderStatus::options(),
            'estadoSeleccionado' => $request->string('estado')->toString(),
            'mesaSeleccionada' => $request->integer('mesa'),
            'desde' => $desde?->toDateString(),
        ]);
    }

    /**
     * The date the list starts at, or null when there is nothing to filter by.
     *
     * The value arrives from a query string, so it can be anything. A date that
     * cannot be read is treated as absent instead of blowing up: someone
     * hand-editing the URL should see the full list, not a 500.
     */
    private function fechaDesde(Request $request): ?Carbon
    {
        if (! $request->filled('desde')) {
            return null;
        }

        try {
            return $request->date('desde');
        } catch (Throwable) {
            // Caught broadly on purpose: the date parsers in play throw more than
            // one class for an unreadable string, and every one of them means the
            // same thing here, which is "this filter is not usable".
            return null;
        }
    }

    /**
     * Show a single order with its items.
     */
    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order->load(['table', 'items.dish']),
        ]);
    }
}
