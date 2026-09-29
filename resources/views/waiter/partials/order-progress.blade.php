@use('App\Enums\OrderStatus')

@props(['estado'])

@php
    $flujo = OrderStatus::flow();
    // A rejection or a handback sits outside the happy path, so no step can be
    // marked as reached and the whole indicator stays muted.
    $indiceActual = array_search($estado, $flujo, true);
    $fueraDeFlujo = $indiceActual === false;
@endphp

<ol class="flex items-start">
    @foreach ($flujo as $paso)
        @php
            $completado = ! $fueraDeFlujo && $loop->index < $indiceActual;
            $actual = $paso === $estado;
        @endphp

        <li class="flex flex-1 flex-col items-center text-center">
            <div class="flex w-full items-center">
                <span
                    class="h-0.5 flex-1 {{ $loop->first ? 'bg-transparent' : ($completado || $actual ? 'bg-gold-500' : 'bg-gray-700') }}"
                ></span>

                <span
                    @class([
                        'flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full',
                        'bg-gold-500' => $actual,
                        'ring-4 ring-gold-500/20' => $actual,
                        'bg-gold-500' => $completado,
                        'bg-gray-700' => ! $completado && ! $actual,
                    ])
                >
                    @if ($completado)
                        <span class="text-[9px] font-bold text-black">✓</span>
                    @endif
                </span>

                <span
                    class="h-0.5 flex-1 {{ $loop->last ? 'bg-transparent' : ($completado ? 'bg-gold-500' : 'bg-gray-700') }}"
                ></span>
            </div>

            <span
                @class([
                    'mt-2 px-1 text-[11px] leading-tight',
                    'font-semibold text-white' => $actual,
                    'text-gold-400' => $completado && ! $actual,
                    'text-gray-500' => ! $completado && ! $actual,
                ])
            >
                {{ $paso->label() }}
            </span>
        </li>
    @endforeach
</ol>
