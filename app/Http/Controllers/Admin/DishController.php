<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDishRequest;
use App\Http\Requests\Admin\UpdateDishRequest;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Support\ArchivosPlato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class DishController extends Controller
{
    /**
     * List the dishes, optionally filtered by category or dish name.
     */
    public function index(Request $request): View
    {
        $dishes = Dish::query()
            ->ordered()
            ->with(['category', 'model3D'])
            ->when(
                $request->filled('categoria'),
                fn (Builder $query) => $query->where('category_id', $request->integer('categoria')),
            )
            ->when(
                $request->filled('buscar'),
                fn (Builder $query) => $query->whereLike('nombre', '%'.$request->string('buscar').'%'),
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
            'ingredients' => $this->ingredients(),
            'ingredientesElegidos' => [],
        ]);
    }

    /**
     * Store a new dish, with its photograph, ingredients and 3D model.
     */
    public function store(StoreDishRequest $request): RedirectResponse
    {
        $this->guardar($request, new Dish, $request->safe()->except(['imagen', 'modelo', 'quitar_imagen']));

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
            'dish' => $dish->load('model3D'),
            'categories' => $this->categories(),
            'ingredients' => $this->ingredients(),
            'ingredientesElegidos' => $dish->ingredients()->pluck('ingredients.id')->all(),
        ]);
    }

    /**
     * Update an existing dish, replacing its files only once the new state is
     * safely stored.
     */
    public function update(UpdateDishRequest $request, Dish $dish): RedirectResponse
    {
        $this->guardar($request, $dish, $request->safe()->except(['imagen', 'modelo', 'quitar_imagen']));

        return redirect()
            ->route('admin.dishes.index')
            ->with('status', 'Plato actualizado.');
    }

    /**
     * Delete a dish that is not part of any order yet.
     *
     * The photograph and the model go with it: nothing else references either
     * file, so leaving them behind would grow the disk on every deleted dish.
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

        $imagen = $dish->rutaImagenEnDisco();
        $modelo = ArchivosPlato::eliminarModelo($dish);

        $dish->delete();

        // Both rows are gone by now, so neither of these files counts as being
        // in use by the dish being deleted; the helper still checks, because a
        // path written before files were named after the dish id can be shared.
        ArchivosPlato::eliminarSiNoEstaEnUso($imagen);
        ArchivosPlato::eliminarSiNoEstaEnUso($modelo);

        return redirect()
            ->route('admin.dishes.index')
            ->with('status', 'Plato eliminado.');
    }

    /**
     * Remove the 3D model of a dish, leaving the dish itself untouched.
     */
    public function destroyModel(Dish $dish): RedirectResponse
    {
        $ruta = ArchivosPlato::eliminarModelo($dish);

        if ($ruta === null) {
            return back()->with('error', 'Ese plato no tiene ningún modelo 3D.');
        }

        ArchivosPlato::eliminarSiNoEstaEnUso($ruta);

        return back()->with('status', 'Modelo 3D eliminado.');
    }

    /**
     * Apply a validated dish submission.
     *
     * The order of operations is the whole point of this method:
     *
     *   1. The row is written first, so a failure here happens while the files
     *      the dish already points at are still untouched.
     *   2. An upload is parked next to its destination, never over it, and the
     *      row is updated to reference the destination inside one transaction.
     *   3. A failure at step 2 deletes only the parked copies. The dish is left
     *      pointing at files that are still there and still correct.
     *   4. Once the transaction has committed, the parked copies take the place
     *      of the previous files, and only then is the file the dish no longer
     *      uses deleted.
     *
     * Promoting before committing, or deleting the old file first, would let an
     * unrelated error destroy the photograph or the model a working dish has
     * had for months.
     *
     * @param  array<string, mixed>  $datos
     */
    protected function guardar(StoreDishRequest $request, Dish $dish, array $datos): Dish
    {
        // Read as the disk knows the path, so a row holding a legacy server path
        // can still have its file replaced and cleaned up correctly.
        $imagenAnterior = $dish->exists ? $dish->rutaImagenEnDisco() : null;
        $modeloAnterior = $dish->exists ? $dish->model3D?->archivo : null;

        $imagenResultante = $imagenAnterior;
        $modeloResultante = $modeloAnterior;
        $pendientes = [];

        try {
            DB::transaction(function () use ($request, $dish, $datos, &$pendientes, &$imagenResultante, &$modeloResultante): void {
                $dish->fill($datos);
                $dish->save();

                if ($request->boolean('quitar_imagen')) {
                    $imagenResultante = null;
                } elseif ($request->hasFile('imagen')) {
                    $pendiente = ArchivosPlato::prepararImagen($dish, $request->file('imagen'));
                    $pendientes[] = $pendiente;
                    $imagenResultante = $pendiente->rutaFinal;
                }

                if ($dish->imagen !== $imagenResultante) {
                    $dish->update(['imagen' => $imagenResultante]);
                }

                if ($request->hasFile('modelo')) {
                    $pendiente = ArchivosPlato::prepararModelo($dish, $request->file('modelo'));
                    $pendientes[] = $pendiente;
                    $modeloResultante = $pendiente->rutaFinal;

                    ArchivosPlato::associateModelo($dish, $modeloResultante);
                }

                $dish->ingredients()->sync($request->input('ingredientes', []));
            });
        } catch (Throwable $e) {
            // The rows were rolled back, so the parked copies are unreferenced.
            // The original exception is rethrown untouched rather than turned
            // into a form error, so a real fault stays visible.
            foreach ($pendientes as $pendiente) {
                ArchivosPlato::descartar($pendiente);
            }

            throw $e;
        }

        foreach ($pendientes as $pendiente) {
            ArchivosPlato::confirmar($pendiente);
        }

        // Committed and promoted, so whatever the dish pointed at before is now
        // unreferenced.
        ArchivosPlato::descartarReemplazada($imagenAnterior, $imagenResultante);
        ArchivosPlato::descartarReemplazada($modeloAnterior, $modeloResultante);

        return $dish;
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

    /**
     * The ingredients to pick from in the dish form.
     *
     * @return Collection<int, Ingredient>
     */
    private function ingredients(): Collection
    {
        return Ingredient::query()->orderBy('nombre')->get();
    }
}
