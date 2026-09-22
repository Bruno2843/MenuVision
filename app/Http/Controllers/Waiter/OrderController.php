<?php

namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    /**
     * Show the waiter's orders dashboard.
     */
    public function index()
    {
        return view('waiter.dashboard');
    }
}