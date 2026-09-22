<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'MenuVision') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 font-sans text-gray-900 antialiased">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
                <span class="text-xl font-bold">MenuVision</span>
                <span class="text-sm text-gray-500">Menú digital interactivo</span>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-16 text-center">
            <h1 class="text-3xl font-bold">MenuVision</h1>
            <p class="mt-3 text-gray-500">
                Menú digital por mesa con visualización 3D de los platos.
            </p>

            <div class="mt-8 rounded-xl bg-white p-6 text-left shadow-sm">
                <p class="text-sm text-gray-500">
                    Escanea el código QR de tu mesa para ver el menú desde tu teléfono.
                </p>
            </div>
        </main>
    </body>
</html>