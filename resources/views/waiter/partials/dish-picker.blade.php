@props(['lineas'])

<div class="overflow-hidden rounded-xl border border-gray-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-900 text-xs uppercase tracking-wide text-gray-500">
            <tr>
                <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                <th scope="col" class="px-4 py-2 text-right font-medium">Precio</th>
                <th scope="col" class="px-4 py-2 text-right font-medium">Cantidad</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @foreach ($lineas as $linea)
                <tr>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-100">{{ $linea['dish']->nombre }}</span>

                        @if ($linea['agotado'])
                            <span class="ml-2 rounded-full bg-gray-800 px-2 py-0.5 text-xs text-gray-400">Agotado</span>
                        @endif

                        @if ($linea['dish']->descripcion)
                            <p class="mt-0.5 text-xs text-gray-500">{{ $linea['dish']->descripcion }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gold-400">
                        ${{ number_format($linea['precio'], 2) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <input
                            type="number"
                            name="items[{{ $linea['dish']->id }}][cantidad]"
                            min="0"
                            max="99"
                            step="1"
                            value="{{ old("items.{$linea['dish']->id}.cantidad", $linea['cantidad']) }}"
                            aria-label="Cantidad de {{ $linea['dish']->nombre }}"
                            class="w-20 rounded-lg border border-gray-700 bg-gray-800 px-2 py-1 text-right text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                        >
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
