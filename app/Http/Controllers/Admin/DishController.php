<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDishRequest;
use App\Http\Requests\Admin\UpdateDishRequest;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DishController extends Controller
{
    /**
     * List the dishes, optionally filtered by category or dish name.
     */
    public function index(Request $request): View
    {
        $dishes = Dish::query()
            ->ordered()
            ->with('category')
            ->when(
                $request->filled('categoria'),
                fn (Builder $query) => $query->where('category_id', $request->integer('categoria')),
            )
            ->when(
                $request->filled('buscar'),
                fn (Builder $query) => $query->where('nombre', 'like', '%'.$request->string('buscar').'%'),
            )
            ->get();

        return view('admin.dishes.index', [
            'dishes' => $dishes,
            'categories' => Category::query()->ordered()->get(),
            'categoriaSeleccionada' => $request->integer('categoria'),
            'busqueda' => $request->string('buscar')->toString(),
        ]);
    }

    /**
     * Show the form to create a new dish.
     */
    public function create(): View
    {
        return view('admin.dishes.create', [
            'dish' => new Dish,
            'categories' => $this->categories(),
        ]);
    }

    /**
     * Store a new dish.
     */
    public function store(StoreDishRequest $request): RedirectResponse
    {
        Dish::create($request->validated());

        return redirect()
            ->route('admin.dishes.index')
            ->with('status', 'Plato creado.');
    }

    /**
     * Show the form to edit a dish.
     */
    public function edit(Dish $dish): View
    {
        return view('admin.dishes.edit', [
            'dish' => $dish,
            'categories' => $this->categories(),
        ]);
    }

    /**
     * Update an existing dish.
     */
    public function update(UpdateDishRequest $request, Dish $dish): RedirectResponse
    {
        $dish->update($request->validated());

        return redirect()
            ->route('admin.dishes.index')
            ->with('status', 'Plato actualizado.');
    }

    /**
     * Delete a dish that is not part of any order yet.
     */
    public function destroy(Dish $dish): RedirectResponse
    {
        if ($dish->items()->exists()) {
            return back()->with(
                'error',
                'No se puede eliminar «'.$dish->nombre.'» porque ya forma parte de un pedido. '
                .'Marcá el plato como no disponible en su lugar.',
            );
        }

        $dish->delete();

        return redirect()
            ->route('admin.dishes.index')
            ->with('status', 'Plato eliminado.');
    }

    /**
     * The categories to pick from in the dish forms.
     *
     * @return Collection<int, Category>
     */
    private function categories(): Collection
    {
        return Category::query()->ordered()->get();
    }
}
