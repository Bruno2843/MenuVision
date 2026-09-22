<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\QRCode;

class MenuController extends Controller
{
    /**
     * Show the digital menu for the table identified by the QR code.
     */
    public function show(string $code)
    {
        $qrCode = QRCode::where('code', $code)->firstOrFail();

        $categories = Category::with('dishes.ingredients', 'dishes.model3D')->get();

        return view('public.menu', [
            'table' => $qrCode->table,
            'categories' => $categories,
        ]);
    }
}