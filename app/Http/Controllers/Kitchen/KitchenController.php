<?php

namespace App\Http\Controllers\Kitchen;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kitchen\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KitchenController extends Controller
{
    /**
     * Show the kitchen board inside the staff layout.
     */
    public function index(): View
    {
        return view('kitchen.dashboard', $this->boardData());
    }

    /**
     * Return only the live part of the board, polled by the front end to keep
     * the kitchen list fresh without a full page reload.
     */
    public function board(): View
    {
        return view('kitchen.partials.board', $this->boardData());
    }

    /**
     * Show a single order with its items.
     */
    public function show(Order $order): View
    {
        $order->load(['table', 'items.dish']);

        return view('kitchen.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Move an order to the next kitchen status, or hand it back to the waiter
     * when an ingredient is missing. The table is released once the last order
     * on it has been delivered.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $estado = OrderStatus::from($request->validated('estado'));

        $order->update([
            'estado' => $estado,
            // The handback reason is only meaningful while the order is out of
            // the kitchen, so re-sending the order clears it.
            'motivo_devolucion' => $estado === OrderStatus::SinInsumos
                ? $request->validated('motivo_devolucion')
                : null,
        ]);

        $order->freeTableIfNoOpenOrders();

        return redirect()
            ->route('kitchen.dashboard')
            ->with('status', "Pedido #{$order->id} actualizado.");
    }

    /**
     * The data behind the kitchen board, shared by the full page and the
     * polled fragment so the queries are never duplicated.
     *
     * The set of statuses is the one the kitchen has always owned: an order
     * reaches this board once the waiter confirmed it, and leaves it when it
     * is delivered. Only the shape of the data changed for the list layout;
     * what is shown and what is excluded is exactly as before.
     *
     * @return array<string, mixed>
     */
    private function boardData(): array
    {
        $states = [
            OrderStatus::Confirmado,
            OrderStatus::EnPreparacion,
            OrderStatus::Listo,
            OrderStatus::SinInsumos,
        ];

        $orders = Order::query()
            ->whereIn('estado', array_map(
                fn (OrderStatus $estado): string => $estado->value,
                $states
            ))
            ->with(['table', 'user', 'items.dish.category'])
            ->oldest()
            ->get();

        return [
            'orders' => $orders,
            'delivered' => Order::query()
                ->where('estado', OrderStatus::Entregado)
                ->with('table')
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }
}
