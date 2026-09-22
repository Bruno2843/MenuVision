<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;

class KitchenController extends Controller
{
    /**
     * Show the kitchen dashboard.
     */
    public function index()
    {
        return view('kitchen.dashboard');
    }
}