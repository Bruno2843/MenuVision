@props(['lineas'])

<div class="overflow-hidden rounded-xl border-2 border-cocina-amarillo bg-cocina-panel">
    <table class="w-full text-left text-sm">
        <thead class="bg-cocina-fondo text-xs uppercase tracking-wide text-cocina-gris">
            <tr>
                <th scope="col" class="px-4 py-2 font-medium">Plato</th>
                <th scope="col" class="px-4 py-2 text-right font-medium">Precio</th>
                <th scope="col" class="px-4 py-2 text-right font-medium">Cantidad</th>
            </tr>
        </thead>
        <tbody class="divide-y-2 divide-cocina-amarillo/15">
            @foreach ($lineas as $linea)
                <tr>
                    <td class="px-4 py-3">
                        <span class="font-medium text-white">{{ $linea['dish']->nombre }}</span>

                        @if ($linea['agotado'])
                            <span class="ml-2 inline-flex items-center rounded-full border-2 border-cocina-amarillo/30 bg-cocina-fondo px-3 py-1 text-xs text-cocina-gris">Agotado</span>
                        @endif

                        @if ($linea['dish']->descripcion)
                            <p class="mt-0.5 text-xs text-cocina-gris">{{ $linea['dish']->descripcion }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-cocina-amarillo">
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
                            class="w-20 rounded-lg border-2 border-cocina-amarillo/30 bg-cocina-fondo px-2 py-1 text-right text-sm text-white focus:border-cocina-amarillo focus:outline-none focus:ring-1 focus:ring-cocina-amarillo"
                        >
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
