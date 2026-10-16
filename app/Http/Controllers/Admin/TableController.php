<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTableRequest;
use App\Http\Requests\Admin\UpdateTableRequest;
use App\Models\QRCode;
use App\Models\Table;
use App\Support\MenuUrl;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TableController extends Controller
{
    /**
     * List the tables with their QR code and how many orders each one holds.
     *
     * The address each QR encodes comes along too, because the sticker is the
     * thing that has to work on a phone and the panel is where it gets printed.
     */
    public function index(): View
    {
        return view('admin.tables.index', [
            'tables' => Table::query()
                // The closure receives the relation, not a builder: a type hint of
                // Builder here makes this screen fail with a 500 as soon as any
                // table has orders.
                ->with(['qrCode', 'orders' => fn (Relation $orders) => $orders->latest()])
                ->orderBy('numero')
                ->get(),
            'problemaQr' => MenuUrl::problema(),
            'urlLogin' => MenuUrl::login(),
        ]);
    }

    /**
     * Show the form to create a new table.
     */
    public function create(): View
    {
        return view('admin.tables.create', [
            'table' => new Table,
        ]);
    }

    /**
     * Store a new table together with the QR code that opens its menu.
     *
     * The two rows are created in one transaction: a table without a code cannot
     * be reached by a client, and a code without a table is unreachable itself.
     */
    public function store(StoreTableRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $table = Table::create($request->validated());

            $this->crearQrPara($table);
        });

        return redirect()
            ->route('admin.tables.index')
            ->with('status', 'Mesa creada con su código QR.');
    }

    /**
     * Show the form to edit a table.
     */
    public function edit(Table $table): View
    {
        return view('admin.tables.edit', [
            'table' => $table,
        ]);
    }

    /**
     * Update an existing table.
     */
    public function update(UpdateTableRequest $request, Table $table): RedirectResponse
    {
        $table->update($request->validated());

        return redirect()
            ->route('admin.tables.index')
            ->with('status', 'Mesa actualizada.');
    }

    /**
     * Delete a table that has never been ordered from.
     *
     * A table that already has orders is kept and set free instead: its history
     * is what explains what was served where, and the order rows point at it.
     */
    public function destroy(Table $table): RedirectResponse
    {
        if ($table->orders()->exists()) {
            return back()->with(
                'error',
                'No se puede eliminar la mesa «'.$table->numero.'» porque ya tiene pedidos. '
                .'Sus pedidos la necesitan para seguir siendo trazables.',
            );
        }

        $table->qrCode?->delete();

        $table->delete();

        return redirect()
            ->route('admin.tables.index')
            ->with('status', 'Mesa eliminada.');
    }

    /**
     * Mint a new code for a table, so a lost sticker can be reprinted.
     *
     * The printed stickers already in the restaurant stop working, which is the
     * point: this is for replacing a damaged or lost one, not for quietly
     * invalidating the codes that are already on the tables.
     *
     * Note what this does NOT have to be used for. Changing where MenuVision is
     * reachable — a different IP, a different port — leaves every code working
     * as it is, because the address is not stored in the row: the QR PNG is drawn
     * from the current configuration each time it is requested. Downloading the
     * PNG again is enough to reprint a sticker with the new address.
     */
    public function regenerateQr(Table $table): RedirectResponse
    {
        $qrCode = $table->qrCode;

        if ($qrCode) {
            $qrCode->update(['code' => $this->codigoUnico()]);
        } else {
            $qrCode = $this->crearQrPara($table);
        }

        return redirect()
            ->route('admin.tables.index')
            ->with('status', 'Código nuevo para la mesa '.$table->numero
                .'. Los stickers anteriores dejan de funcionar: reimprimí el que está acá.');
    }

    /**
     * Create the QR row of a table with a code nothing else is using.
     */
    private function crearQrPara(Table $table): QRCode
    {
        return $table->qrCode()->create([
            'code' => $this->codigoUnico(),
        ]);
    }

    /**
     * A code that does not collide with an existing one.
     *
     * The column is unique, so the code is generated and then verified rather
     * than assumed unique: 16 random characters practically never repeat, but a
     * clash here would make one table's menu unreachable under another's link.
     */
    private function codigoUnico(): string
    {
        do {
            $code = Str::random(16);
        } while (QRCode::where('code', $code)->exists());

        return $code;
    }
}
