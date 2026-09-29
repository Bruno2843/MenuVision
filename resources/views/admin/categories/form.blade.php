<div class="space-y-4">
    <div>
        <label for="nombre" class="mb-1 block text-sm font-medium text-gray-300">Nombre</label>
        <input
            id="nombre"
            type="text"
            name="nombre"
            value="{{ old('nombre', $category->nombre) }}"
            required
            autofocus
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
    </div>

    <div>
        <label for="descripcion" class="mb-1 block text-sm font-medium text-gray-300">Descripción (opcional)</label>
        <input
            id="descripcion"
            type="text"
            name="descripcion"
            value="{{ old('descripcion', $category->descripcion) }}"
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
    </div>

    <div>
        <label for="orden" class="mb-1 block text-sm font-medium text-gray-300">Orden</label>
        <input
            id="orden"
            type="number"
            name="orden"
            value="{{ old('orden', $category->orden ?? 1) }}"
            min="0"
            required
            class="w-32 rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
        <p class="mt-1 text-xs text-gray-500">Número menor = aparece antes en el menú.</p>
    </div>
</div>
