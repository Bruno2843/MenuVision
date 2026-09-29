<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index(): View
    {
        return view('admin.dashboard', [
            'categoriesCount' => Category::query()->count(),
            'dishesCount' => Dish::query()->count(),
        ]);
    }
}
