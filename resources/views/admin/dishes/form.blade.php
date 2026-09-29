<div class="space-y-4">
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
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
    </div>

    <div>
        <label for="descripcion" class="mb-1 block text-sm font-medium text-gray-300">Descripción (opcional)</label>
        <textarea
            id="descripcion"
            name="descripcion"
            rows="3"
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
                <input
                    type="checkbox"
                    name="disponible"
                    value="1"
                    @checked(old('disponible', $dish->exists ? $dish->disponible : true))
                    class="rounded border-gray-600 bg-gray-900 text-gold-500 focus:ring-gold-500"
                >
                <span class="text-sm text-gray-300">Disponible</span>
            </label>
            <input type="hidden" name="disponible" value="0">
        </div>
    </div>
</div>
