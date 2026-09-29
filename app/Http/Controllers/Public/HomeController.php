<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QRCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the public landing page or send authenticated staff to their panel.
     */
    public function index(): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->role->dashboardRoute());
        }

        return view('welcome', [
            'qrCodes' => QRCode::with('table')->orderBy('table_id')->get(),
        ]);
    }
}
