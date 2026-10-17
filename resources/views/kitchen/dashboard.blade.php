@extends('layouts.staff')

@section('title', 'Cocina · Pedidos para preparar')

@section('content')
    <div class="space-y-3">
        {{-- Encabezado de la pantalla: título y estado de la cocina. --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                @include('partials.icon', ['icon' => 'gorro', 'iconClass' => 'h-8 w-8 text-cocina-amarillo'])
                <div>
                    <h1 class="text-2xl leading-none font-bold text-white">Pedidos para preparar</h1>
                    <div class="mt-1.5 h-0.5 w-16 bg-cocina-amarillo"></div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full border-2 border-cocina-verde px-3 py-1 text-xs font-semibold text-cocina-verde">
                    <span class="h-2 w-2 rounded-full bg-cocina-verde"></span>
                    Cocina activa
                </span>

                <span class="inline-flex items-center gap-1.5 text-xs text-white">
                    @include('partials.icon', ['icon' => 'reloj', 'iconClass' => 'h-4 w-4 text-cocina-amarillo'])
                    {{ now()->format('H:i') }}
                </span>
            </div>
        </header>

        {{-- Aviso informativo. --}}
        <div class="flex items-center gap-3 rounded-xl border-2 border-cocina-amarillo bg-cocina-panel px-4 py-2.5">
            @include('partials.icon', ['icon' => 'info', 'iconClass' => 'h-5 w-5 shrink-0 text-cocina-amarillo'])
            <p class="text-sm text-white">
                <span class="font-semibold">Pedidos confirmados por el mozo.</span>
                <span class="text-cocina-gris">La cocina no ve pedidos pendientes ni rechazados.</span>
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-xl border-2 border-cocina-verde bg-cocina-panel px-4 py-2.5 text-sm font-medium text-cocina-verde">
                {{ session('status') }}
            </div>
        @endif

        {{-- Listado en vivo: se refresca solo y conserva el filtro elegido. --}}
        <div data-poll-url="{{ route('kitchen.board') }}" data-kitchen-board>
            @include('kitchen.partials.board')
        </div>
    </div>
@endsection
