<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'MenuVision') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-950 font-sans text-gray-100 antialiased">
        <header class="border-b border-gold-600/40 bg-black">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                <span class="text-xl font-bold text-gold-400">MenuVision</span>
                <span class="text-sm text-gray-400">Menú digital interactivo</span>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-12">
            <h1 class="text-center text-3xl font-bold text-white">MenuVision</h1>
            <p class="mt-3 text-center text-gray-400">
                Menú digital por mesa con visualización 3D de los platos.
            </p>

            <div class="mt-10 grid gap-6 md:grid-cols-2">
                <section class="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <h2 class="text-xl font-semibold text-white">¿Sos cliente?</h2>
                    <p class="mt-2 text-sm text-gray-400">
                        Escaneá el código QR de tu mesa con la cámara para ver el menú.
                    </p>

                    <button
                        id="scan-qr-button"
                        type="button"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-gold-500 px-4 py-3 text-sm font-semibold text-black hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:ring-offset-2 focus:ring-offset-gray-950"
                    >
                        <span aria-hidden="true">📷</span>
                        Escanear QR
                    </button>

                    <form id="menu-code-form" class="mt-4">
                        <label for="menu-code" class="mb-1 block text-sm font-medium text-gray-300">
                            ¿No tenés el QR a mano? Escribí el código:
                        </label>
                        <div class="flex gap-2">
                            <input
                                id="menu-code"
                                type="text"
                                name="code"
                                placeholder="Código de mesa"
                                required
                                autocomplete="off"
                                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                            >
                            <button
                                type="submit"
                                class="shrink-0 rounded-lg border border-gold-600 px-4 py-2 text-sm font-semibold text-gold-400 hover:bg-gold-500/10 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 focus:ring-offset-gray-950"
                            >
                                Ver menú
                            </button>
                        </div>
                    </form>

                    @if ($qrCodes->isNotEmpty())
                        <div class="mt-6 border-t border-gray-800 pt-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                Probalo desde el celular
                            </p>
                            <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($qrCodes as $qr)
                                    <figure class="flex flex-col items-center rounded-lg border border-gray-700 bg-gray-950 p-2">
                                        <img
                                            src="{{ url('/qr/menu/'.$qr->code) }}"
                                            alt="QR de la mesa {{ $qr->table->numero }}"
                                            class="h-20 w-20"
                                        >
                                        <figcaption class="mt-1 text-xs text-gray-400">Mesa {{ $qr->table->numero }}</figcaption>
                                    </figure>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                <section class="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <h2 class="text-xl font-semibold text-white">¿Sos parte del personal?</h2>
                    <p class="mt-2 text-sm text-gray-400">
                        Accedé al panel de administración, pedidos o cocina desde el inicio
                        de sesión del personal.
                    </p>

                    <a
                        href="{{ route('login') }}"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-gold-600 px-4 py-3 text-sm font-semibold text-black hover:bg-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:ring-offset-2 focus:ring-offset-gray-950"
                    >
                        Iniciar sesión del personal
                    </a>

                    <div class="mt-6 border-t border-gray-800 pt-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            QR de acceso del personal
                        </p>
                        <figure class="mt-2 inline-flex flex-col items-center rounded-lg border border-gray-700 bg-gray-950 p-2">
                            <img
                                src="{{ url('/qr/personal') }}"
                                alt="QR de acceso del personal"
                                class="h-20 w-20"
                            >
                            <figcaption class="mt-1 text-xs text-gray-400">Abre el login</figcaption>
                        </figure>
                    </div>
                </section>
            </div>
        </main>

        <div id="scan-overlay" class="fixed inset-0 hidden flex-col items-center justify-center bg-black/85 p-4">
            <div class="w-full max-w-md rounded-xl border border-gray-800 bg-gray-900 p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-white">Escanear QR</h2>
                    <button
                        id="close-scan-button"
                        type="button"
                        class="rounded-lg px-2 py-1 text-sm text-gray-400 hover:bg-gray-800"
                        aria-label="Cerrar"
                    >
                        ✕
                    </button>
                </div>

                <video
                    id="scan-video"
                    autoplay
                    playsinline
                    muted
                    class="aspect-square w-full rounded-lg bg-black"
                ></video>

                <p id="scan-status" class="mt-4 text-sm text-gray-400"></p>
            </div>
        </div>

        <script>
            document.getElementById('menu-code-form').addEventListener('submit', (event) => {
                event.preventDefault();

                const code = document.getElementById('menu-code').value.trim();

                if (code) {
                    window.location.href = '/menu/' + encodeURIComponent(code);
                }
            });
        </script>
    </body>
</html>