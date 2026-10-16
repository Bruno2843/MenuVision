<?php

namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Waiter\ResolveTableQrRequest;
use App\Support\TableQr;
use Illuminate\Http\JsonResponse;

class QrCodeController extends Controller
{
    /**
     * Resolve a scanned QR code into the table it stands for.
     *
     * The waiter panel sends whatever the camera decoded and this decides, on
     * the server, which table that is: the code is looked up in `qr_codes` and
     * the table comes from the row, never from the payload. A code that is not
     * stored, or one whose table is gone, is reported as a plain message the
     * scanner shows as is, with no Laravel error behind it.
     *
     * The response also carries the URL of the order form for that table, so the
     * browser never has to build a URL out of an id it was told.
     */
    public function resolve(ResolveTableQrRequest $request): JsonResponse
    {
        $resuelto = TableQr::resolver($request->validated('code'));

        if ($resuelto === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una mesa asociada a este código QR.',
            ]);
        }

        $table = $resuelto['table'];

        return response()->json([
            'success' => true,
            'table' => [
                'id' => $table->id,
                'numero' => $table->numero,
                'estado' => $table->estado->value,
            ],
            // The code travels back rather than the table id, so the order form
            // resolves the same table on its own instead of trusting a number
            // that came from the browser.
            'url' => route('waiter.orders.create', ['codigo' => $resuelto['qrCode']->code]),
        ]);
    }
}
