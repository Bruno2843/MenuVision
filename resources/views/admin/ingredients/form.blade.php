<div>
    <label for="nombre" class="mb-1 block text-sm font-medium text-gray-300">Nombre</label>
    <input
        id="nombre"
        type="text"
        name="nombre"
        value="{{ old('nombre', $ingredient->nombre) }}"
        required
        autofocus
        maxlength="255"
        class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
    >
    <p class="mt-1 text-xs text-gray-500">
        Se muestra así en el detalle del plato. No puede repetirse.
    </p>
</div>
