<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dish;
use App\Models\QRCode;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Show the digital menu for the table identified by the QR code.
     */
    public function show(string $code): View
    {
        $qrCode = QRCode::where('code', $code)
            ->with('table')
            ->firstOrFail();

        $categories = Category::query()
            ->ordered()
            ->conPlatosDisponibles()
            ->with([
                'dishes' => fn (Relation $dishes) => $dishes
                    ->disponibles()
                    ->ordered()
                    ->with(['category', 'ingredients']),
            ])
            ->get();

        return view('public.menu', [
            'code' => $qrCode->code,
            'mesa' => $qrCode->table?->numero,
            'categories' => $categories,
        ]);
    }

    /**
     * Show a single dish of the menu reached from a table.
     *
     * The QR code still has to resolve, so the link only works for the table
     * that opened the menu. A dish that is not available is a 404 for the same
     * reason it is hidden from the menu itself.
     *
     * The 3D model is resolved here rather than in the view: the file is read
     * from disk, and the page only offers the 3D viewer and the augmented
     * reality button when the model this dish owns is really there. The URL is
     * built from the stored path, so the client can never ask for another one.
     */
    public function showDish(string $code, Dish $dish): View
    {
        QRCode::where('code', $code)->firstOrFail();

        abort_unless($dish->disponible, 404);

        $dish->load(['category', 'ingredients', 'model3D']);

        $modelo = $dish->model3D;

        return view('public.dish', [
            'code' => $code,
            'dish' => $dish,
            'modelo3dUrl' => $modelo?->existe() ? $modelo->url() : null,
        ]);
    }
}
