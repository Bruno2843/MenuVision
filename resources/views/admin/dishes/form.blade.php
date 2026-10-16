<div class="space-y-6">
    {{-- Basic fields. --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="category_id" class="mb-1 block text-sm font-medium text-gray-300">Categoría</label>
            <select
                id="category_id"
                name="category_id"
                required
                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
            >
                <option value="">Elegí una categoría</option>
                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected(old('category_id', $dish->category_id) == $category->id)
                    >
                        {{ $category->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="orden" class="mb-1 block text-sm font-medium text-gray-300">Orden en la categoría</label>
            <input
                id="orden"
                type="number"
                name="orden"
                value="{{ old('orden', $dish->orden ?? 1) }}"
                min="0"
                required
                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
            >
        </div>
    </div>

    <div>
        <label for="nombre" class="mb-1 block text-sm font-medium text-gray-300">Nombre</label>
        <input
            id="nombre"
            type="text"
            name="nombre"
            value="{{ old('nombre', $dish->nombre) }}"
            required
            autofocus
            maxlength="255"
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
    </div>

    <div>
        <label for="descripcion" class="mb-1 block text-sm font-medium text-gray-300">Descripción (opcional)</label>
        <textarea
            id="descripcion"
            name="descripcion"
            rows="3"
            maxlength="1000"
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >{{ old('descripcion', $dish->descripcion) }}</textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="precio" class="mb-1 block text-sm font-medium text-gray-300">Precio</label>
            <input
                id="precio"
                type="number"
                name="precio"
                value="{{ old('precio', $dish->precio) }}"
                min="0"
                step="0.01"
                required
                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
            >
        </div>

        <div>
            <span class="mb-1 block text-sm font-medium text-gray-300">Disponibilidad</span>
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                    {{-- Before the checkbox, never after: PHP keeps the last value sent
                         for a repeated field name, so a trailing hidden input would
                         overwrite the ticked "1" and mark every dish as agotado. --}}
                    <input type="hidden" name="disponible" value="0">
                    <input
                        type="checkbox"
                        name="disponible"
                        value="1"

                    @checked(old('disponible', $dish->exists ? $dish->disponible : true))
                    class="rounded border-gray-600 bg-gray-900 text-gold-500 focus:ring-gold-500"
                >
                <span class="text-sm text-gray-300">Disponible</span>
            </label>
        </div>
    </div>

    {{-- Photograph. --}}
    <fieldset class="rounded-xl border border-gray-800 p-4">
        <legend class="px-1 text-sm font-medium text-gray-300">Fotografía</legend>

        @if ($dish->imagenUrl())
            <div class="mb-3 flex items-start gap-4">
                <img
                    src="{{ $dish->imagenUrl() }}"
                    data-imagen-respaldo="{{ asset(\App\Support\MenuImagen::respaldoParaDish($dish)) }}"
                    alt="Foto actual de {{ $dish->nombre }}"
                    class="h-24 w-32 rounded-lg border border-gray-800 object-cover"
                >

                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-400">
                    <input
                        type="checkbox"
                        name="quitar_imagen"
                        value="1"
                        @checked(old('quitar_imagen'))
                        class="rounded border-gray-600 bg-gray-900 text-gold-500 focus:ring-gold-500"
                    >
                    Quitar la foto y volver a la imagen de la categoría
                </label>
            </div>
        @endif

        <label for="imagen" class="mb-1 block text-sm font-medium text-gray-300">
            {{ $dish->imagenUrl() ? 'Reemplazar la foto' : 'Subir una foto' }}
        </label>
        <input
            id="imagen"
            type="file"
            name="imagen"
            accept="image/avif,image/webp,image/jpeg,image/png"
            class="block w-full cursor-pointer rounded-lg border border-gray-700 bg-gray-800 text-sm text-gray-400 file:mr-3 file:cursor-pointer file:border-0 file:bg-gray-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-100 hover:file:bg-gray-600"
        >
        <p class="mt-1 text-xs text-gray-500">
            JPG, PNG, WebP o AVIF, hasta 4 MB. Sin foto, el menú muestra una imagen de la categoría.
        </p>
    </fieldset>

    {{-- Ingredients. --}}
    <fieldset class="rounded-xl border border-gray-800 p-4">
        <legend class="px-1 text-sm font-medium text-gray-300">Ingredientes</legend>

        @if ($ingredients->isEmpty())
            <p class="text-sm text-gray-500">
                Todavía no hay ingredientes. <a href="{{ route('admin.ingredients.create') }}" class="text-gold-400 hover:text-gold-300">Creá el primero</a>.
            </p>
        @else
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($ingredients as $ingredient)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-800 bg-gray-800/40 px-3 py-2 text-sm text-gray-300">
                        <input
                            type="checkbox"
                            name="ingredientes[]"
                            value="{{ $ingredient->id }}"
                            @checked(in_array($ingredient->id, old('ingredientes', $ingredientesElegidos), false))
                            class="rounded border-gray-600 bg-gray-900 text-gold-500 focus:ring-gold-500"
                        >
                        <span class="truncate">{{ $ingredient->nombre }}</span>
                    </label>
                @endforeach
            </div>
        @endif
    </fieldset>

    {{-- 3D model. The button that removes it lives outside this form. --}}
    <fieldset class="rounded-xl border border-gray-800 p-4">
        <legend class="px-1 text-sm font-medium text-gray-300">Modelo 3D y realidad aumentada</legend>

        @if ($dish->model3D?->existe())
            <div class="mb-3 flex flex-wrap items-start gap-4">
                {{--
                    The admin preview reuses the same viewer the rest of the
                    project already ships: app.js mounts every element marked
                    with data-glb-viewer, so this needs no extra JavaScript.
                --}}
                <div
                    data-glb-viewer="{{ $dish->model3D->url() }}"
                    class="h-40 w-40 overflow-hidden rounded-lg border border-gray-800 bg-gray-950"
                ></div>

                <p class="text-xs text-gray-500">
                    <span class="block font-mono text-gray-400">{{ $dish->model3D->archivo }}</span>
                    Se muestra en 3D y en realidad aumentada en el detalle del plato.
                </p>
            </div>
        @elseif ($dish->model3D)
            <p class="mb-3 rounded-lg border border-amber-900 bg-amber-950 px-3 py-2 text-sm text-amber-300">
                Este plato tiene un modelo registrado pero el archivo no está en el disco.
                Subí uno nuevo para reemplazarlo.
            </p>
        @endif

        <label for="modelo" class="mb-1 block text-sm font-medium text-gray-300">
            {{ $dish->model3D ? 'Reemplazar el modelo' : 'Subir un modelo' }}
        </label>
        <input
            id="modelo"
            type="file"
            name="modelo"
            accept=".glb,model/gltf-binary"
            class="block w-full cursor-pointer rounded-lg border border-gray-700 bg-gray-800 text-sm text-gray-400 file:mr-3 file:cursor-pointer file:border-0 file:bg-gray-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-100 hover:file:bg-gray-600"
        >
        <p class="mt-1 text-xs text-gray-500">
            Archivo .glb, hasta 100 MB. Si el archivo supera el límite del servidor, subí un
            modelo liviano: el límite de PHP suele ser menor que este.
        </p>
    </fieldset>
</div>
