<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PanelEstadisticas;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     *
     * The figures come from `PanelEstadisticas` rather than from this method so
     * the arithmetic is written, and tested, in one place.
     */
    public function index(): View
    {
        return view('admin.dashboard', PanelEstadisticas::resumen());
    }
}
