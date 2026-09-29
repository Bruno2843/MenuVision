<?php

namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Show the menu grouped by category, including unavailable dishes so the
     * waiter can tell which plates ran out.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->ordered()
            ->with([
                'dishes' => fn (Relation $dishes) => $dishes
                    ->ordered()
                    ->with('ingredients'),
            ])
            ->get();

        return view('waiter.menu', [
            'categories' => $categories,
        ]);
    }
}
