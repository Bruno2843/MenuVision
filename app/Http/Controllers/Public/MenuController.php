<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
        $qrCode = QRCode::where('code', $code)->firstOrFail();

        $categories = Category::query()
            ->ordered()
            ->conPlatosDisponibles()
            ->with([
                'dishes' => fn (Relation $dishes) => $dishes
                    ->disponibles()
                    ->ordered()
                    ->with(['ingredients', 'model3D']),
            ])
            ->get();

        return view('public.menu', [
            'table' => $qrCode->table,
            'categories' => $categories,
        ]);
    }
}
