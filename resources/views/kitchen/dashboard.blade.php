@extends('layouts.staff')

@section('title', 'Cocina · Pedidos')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Cocina</h1>
        <p class="text-sm text-gray-400">
            Pedidos confirmados por el mozo, de la preparación a la entrega.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-900 bg-green-950 px-4 py-3 text-sm text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <div data-poll-url="{{ route('kitchen.board') }}">
        @include('kitchen.partials.board')
    </div>
@endsection
