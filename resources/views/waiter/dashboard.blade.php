@extends('layouts.staff')

@section('title', 'Mozo · Pedidos')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Pedidos del mozo</h1>
            <p class="text-sm text-cocina-gris">Revisá y confirmá los pedidos de las mesas.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                data-waiter-scan
                data-resolve-url="{{ route('waiter.qr.resolve') }}"
                class="inline-flex items-center gap-2 rounded-lg border-2 border-cocina-amarillo/60 px-4 py-2 text-sm font-semibold text-cocina-amarillo transition hover:bg-cocina-amarillo/10"
            >
                @include('partials.icon', ['icon' => 'qr', 'iconClass' => 'h-4 w-4'])
                Escanear QR
            </button>

            <a
                href="{{ route('waiter.orders.create') }}"
                class="rounded-lg bg-cocina-amarillo px-4 py-2 text-sm font-semibold text-black transition hover:brightness-110"
            >
                Tomar pedido
            </a>
        </div>
    </div>

    @include('waiter.partials.qr-scanner')

    @if (session('status'))
        <div class="mb-4 rounded-lg border-2 border-cocina-verde/50 bg-cocina-panel px-4 py-3 text-sm text-cocina-verde">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border-2 border-red-500/50 bg-cocina-panel px-4 py-3 text-sm text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div data-poll-url="{{ route('waiter.board') }}">
        @include('waiter.partials.board')
    </div>
@endsection
