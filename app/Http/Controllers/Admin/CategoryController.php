<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * List the categories with how many dishes each one holds.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->ordered()
            ->withCount('dishes')
            ->get();

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form to create a new category.
     */
    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category(['orden' => Category::query()->max('orden') + 1]),
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Categoría creada.');
    }

    /**
     * Show the form to edit a category.
     */
    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Categoría actualizada.');
    }

    /**
     * Delete a category that has no dishes attached.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->dishes()->exists()) {
            return back()->with(
                'error',
                'No se puede eliminar «'.$category->nombre.'» porque tiene platos asociados. '
                .'Reasignalos o marcalos como no disponibles primero.',
            );
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Categoría eliminada.');
    }
}
