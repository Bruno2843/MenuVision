@use('Illuminate\Support\Str')

@extends('layouts.admin')

@section('title', 'Admin · Mesas y QR')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Mesas y QR</h1>
            <p class="text-sm text-gray-400">Cada mesa tiene un código que abre su carta.</p>
        </div>

        <a
            href="{{ route('admin.tables.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
        >
            @include('partials.icon', ['icon' => 'mas', 'iconClass' => 'h-4 w-4'])
            Nueva mesa
        </a>
    </div>

    @include('admin.partials.flash')

    {{-- The QR encodes an address, and the person printing the stickers is the
         only one who can notice it is one their phone cannot open. The address is
         shown verbatim, and a local-only host is called out instead of waiting for
         a client to report that their scan did nothing. --}}
    @if ($problemaQr)
        <div class="mb-6 rounded-xl border border-amber-900 bg-amber-950 px-4 py-3 text-sm text-amber-200">
            {{ $problemaQr }}
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-gray-800 bg-gray-900 p-5">
        <h2 class="text-sm font-semibold text-white">QR del acceso al personal</h2>
        <p class="mt-1 text-sm text-gray-400">
            Este código abre la pantalla de acceso para mozos y cocina. Imprimilo una vez,
            fuera de la vista de los clientes.
        </p>

        <div class="mt-3 flex flex-wrap items-center gap-4">
            <img
                src="{{ route('qr.personal') }}"
                alt="Código QR del acceso al personal"
                width="120"
                height="120"
                class="h-28 w-28 rounded-lg border border-gray-800 bg-white p-1"
            >

            <a
                href="{{ route('qr.personal') }}"
                download="menuvision-personal-qr.png"
                class="rounded-lg border border-gold-600 px-4 py-2 text-sm font-semibold text-gold-400 transition hover:bg-gold-500/10"
            >
                Descargar PNG
            </a>

            <p class="w-full break-all rounded-lg bg-gray-950 px-3 py-2 font-mono text-xs text-gray-400">
                {{ $urlLogin }}
            </p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($tables as $table)
            <div class="flex flex-col rounded-xl border border-gray-800 bg-gray-900 p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Mesa {{ $table->numero }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ $table->orders->count() }}
                            {{ Str::plural('pedido', $table->orders->count()) }}
                        </p>
                    </div>

                    <span @class([
                        'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium',
                        $table->estado->badgeClasses(),
                    ])>
                        {{ $table->estado->label() }}
                    </span>
                </div>

                @if ($table->qrCode)
                    <img
                        src="{{ route('qr.menu', $table->qrCode->code) }}"
                        alt="Código QR de la mesa {{ $table->numero }}"
                        width="120"
                        height="120"
                        loading="lazy"
                        class="mx-auto mt-4 h-40 w-40 rounded-lg border border-gray-800 bg-white p-1"
                    >

                    <p class="mt-3 truncate text-center font-mono text-xs text-gray-500">
                        {{ $table->qrCode->code }}
                    </p>

                    {{-- The exact string drawn inside the square above. Printed
                         verbatim so the sticker can be checked against the phone
                         that has to open it. --}}
                    <p class="mt-1 break-all rounded-lg bg-gray-950 px-2 py-1.5 text-center font-mono text-[0.7rem] leading-snug text-gray-500">
                        {{ \App\Support\MenuUrl::menu($table->qrCode->code) }}
                    </p>

                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <a
                            href="{{ route('qr.menu', $table->qrCode->code) }}"
                            download="mesa-{{ $table->numero }}-qr.png"
                            class="rounded-lg border border-gold-600 px-3 py-1.5 text-xs font-semibold text-gold-400 transition hover:bg-gold-500/10"
                        >
                            Descargar
                        </a>

                        {{-- Minting a new code invalidates the sticker already on
                             the table. Changing QR_BASE_URL does not need this:
                             the code stays and the PNG is simply drawn again. --}}
                        <form method="POST" action="{{ route('admin.tables.qr.regenerate', $table) }}">
                            @csrf
                            <button
                                type="submit"
                                onclick="return confirm('Se genera un código nuevo y el sticker impreso de esta mesa deja de funcionar. ¿Seguir?')"
                                class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-300 transition hover:bg-gray-800"
                            >
                                Nuevo código
                            </button>
                        </form>

                        <a
                            href="{{ route('admin.tables.edit', $table) }}"
                            class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-300 transition hover:bg-gray-800"
                        >
                            Editar
                        </a>
                    </div>
                @else
                    <p class="mt-4 rounded-lg border border-amber-900 bg-amber-950 px-3 py-2 text-xs text-amber-300">
                        Esta mesa no tiene código QR.
                    </p>

                    <form method="POST" action="{{ route('admin.tables.qr.regenerate', $table) }}" class="mt-3">
                        @csrf
                        <button
                            type="submit"
                            class="w-full rounded-lg border border-gold-600 px-3 py-1.5 text-xs font-semibold text-gold-400 transition hover:bg-gold-500/10"
                        >
                            Crear código QR
                        </button>
                    </form>
                @endif

                <form
                    method="POST"
                    action="{{ route('admin.tables.destroy', $table) }}"
                    class="mt-3 border-t border-gray-800 pt-3"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-300">
                        Eliminar mesa
                    </button>
                </form>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-8 text-center text-sm text-gray-500 sm:col-span-2 xl:col-span-3">
                Todavía no hay mesas.
            </p>
        @endforelse
    </div>
@endsection
