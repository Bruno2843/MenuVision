@extends('layouts.staff')

@section('title', 'Mozo · Pedidos')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Pedidos del mozo</h1>
            <p class="text-sm text-gray-400">Revisá y confirmá los pedidos de las mesas.</p>
        </div>

        <a
            href="{{ route('waiter.orders.create') }}"
            class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
        >
            Tomar pedido
        </a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-900 bg-green-950 px-4 py-3 text-sm text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <div data-poll-url="{{ route('waiter.board') }}">
        @include('waiter.partials.board')
    </div>
@endsection
