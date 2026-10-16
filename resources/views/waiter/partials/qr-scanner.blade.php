{{--
    The scanner the waiter opens from the dashboard.

    The camera is the whole screen on a phone, so the overlay is full-bleed and
    the video keeps a square frame: that is the shape a table sticker is printed
    in, and a frame that matches what is being aimed at reads faster than a wide
    one. The element is inert until `resources/js/waiter/qr-scanner.js` opens it,
    and the form is a plain progressive fallback: without JavaScript the waiter
    can still type the code of the table.
--}}
<div
    data-waiter-scan-overlay
    hidden
    class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/90 p-4"
>
    <div class="flex h-full w-full max-w-lg flex-col justify-center">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-white">Escanear QR</h2>

            <button
                type="button"
                data-waiter-scan-close
                aria-label="Cerrar"
                class="rounded-lg px-3 py-2 text-sm text-gray-300 transition hover:bg-gray-800"
            >
                ✕
            </button>
        </div>

        <video
            data-waiter-scan-video
            autoplay
            playsinline
            muted
            class="aspect-square w-full max-h-[60vh] rounded-xl bg-black object-cover"
        ></video>

        <p data-waiter-scan-status class="mt-4 text-sm text-gray-300" role="status">
            Apuntá la cámara al código QR de la mesa.
        </p>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row">
            <button
                type="button"
                data-waiter-scan-close
                class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800"
            >
                Cancelar
            </button>

            <form
                method="GET"
                action="{{ route('waiter.orders.create') }}"
                class="flex flex-1 items-center gap-2"
            >
                <label for="waiter-scan-code" class="sr-only">Código de la mesa</label>
                <input
                    id="waiter-scan-code"
                    type="text"
                    name="codigo"
                    autocomplete="off"
                    placeholder="O escribí el código"
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                >
                <button
                    type="submit"
                    class="shrink-0 rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-black transition hover:bg-gold-600"
                >
                    Ir
                </button>
            </form>
        </div>
    </div>
</div>
