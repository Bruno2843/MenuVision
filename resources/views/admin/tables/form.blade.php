<div>
    <label for="numero" class="mb-1 block text-sm font-medium text-gray-300">Número o nombre</label>
    <input
        id="numero"
        type="text"
        name="numero"
        value="{{ old('numero', $table->numero) }}"
        required
        autofocus
        maxlength="50"
        placeholder="1, Barra 2, Terraza…"
        class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
    >
    <p class="mt-1 text-xs text-gray-500">
        Es lo que ven los clientes y lo que identifica la mesa en los pedidos.
    </p>
</div>
