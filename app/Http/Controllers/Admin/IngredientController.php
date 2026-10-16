<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIngredientRequest;
use App\Http\Requests\Admin\UpdateIngredientRequest;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IngredientController extends Controller
{
    /**
     * List the ingredients with how many dishes use each one.
     */
    public function index(): View
    {
        return view('admin.ingredients.index', [
            'ingredients' => Ingredient::query()
                ->withCount('dishes')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    /**
     * Show the form to create a new ingredient.
     */
    public function create(): View
    {
        return view('admin.ingredients.create', [
            'ingredient' => new Ingredient,
        ]);
    }

    /**
     * Store a new ingredient.
     */
    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        Ingredient::create($request->validated());

        return redirect()
            ->route('admin.ingredients.index')
            ->with('status', 'Ingrediente creado.');
    }

    /**
     * Show the form to edit an ingredient.
     */
    public function edit(Ingredient $ingredient): View
    {
        return view('admin.ingredients.edit', [
            'ingredient' => $ingredient,
        ]);
    }

    /**
     * Update an existing ingredient.
     */
    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $ingredient->update($request->validated());

        return redirect()
            ->route('admin.ingredients.index')
            ->with('status', 'Ingrediente actualizado.');
    }

    /**
     * Delete an ingredient.
     *
     * The pivot rows go with it, so the ingredient disappears from the dishes
     * that listed it. That is the only safe reading of the request: an
     * ingredient nothing references any more is a leftover, and the dishes keep
     * their name, price and history either way.
     */
    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $ingredient->delete();

        return redirect()
            ->route('admin.ingredients.index')
            ->with('status', 'Ingrediente eliminado.');
    }
}
