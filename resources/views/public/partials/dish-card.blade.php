{{--
    Dish card for the public menu.

    The photograph fills the top at a 16:9 ratio. Below: title (yellow),
    short description, price, and a "Ver plato" pill link.
    Leaf branch decorations appear on every other card.

    @param \App\Models\Dish $dish
    @param string           $code  QR code of the table that opened the menu.
--}}
@props([
    'dish',
    'code',
])

<article
    data-dish-card
    data-categoria="{{ $dish->category_id }}"
    class="group relative flex flex-col overflow-hidden rounded-2xl border-2 border-amarillo-500 bg-noche-900 transition duration-300 hover:border-amarillo-400"
>
    {{-- Photo. `data-imagen-respaldo` is the scene this dish would show without
         an uploaded photograph: imagen-respaldo.js puts it in place if the file
         cannot be fetched, so a broken URL never leaves the frame empty. --}}
    <div class="relative aspect-[16/9] overflow-hidden bg-noche-950">
        <img
            src="{{ asset(\App\Support\MenuImagen::paraDish($dish)) }}"
            data-imagen-respaldo="{{ asset(\App\Support\MenuImagen::respaldoParaDish($dish)) }}"
            alt=""
            width="1200"
            height="675"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
        >
    </div>

    {{-- Content --}}
    <div class="relative flex flex-1 flex-col gap-2 p-5">

        <h3 class="text-xl leading-tight font-bold text-amarillo-500 sm:text-[1.35rem]">
            {{ $dish->nombre }}
        </h3>

        @if ($dish->descripcion)
            <p class="line-clamp-2 text-sm leading-relaxed text-humo-100">
                {{ $dish->descripcion }}
            </p>
        @endif

        <p class="mt-1 text-2xl font-extrabold tracking-tight tabular-nums text-amarillo-500">
            $ {{ number_format($dish->precio, 0, ',', '.') }}
        </p>

        <div class="mt-auto pt-2">
            <a
                href="{{ route('menu.dish.show', ['code' => $code, 'dish' => $dish->id]) }}"
                class="inline-flex h-[46px] items-center gap-2.5 rounded-full border-2 border-amarillo-500 bg-noche-950 px-5 text-sm font-semibold text-amarillo-500 transition hover:bg-amarillo-500 hover:text-noche-950 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amarillo-500"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="h-4.5 w-4.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <use href="#icono-libro"></use>
                </svg>
                Ver plato
                <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 8h10"/>
                    <path d="M9 4l4 4-4 4"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Leaf decoration on alternating cards --}}
    @if (($loop->index % 2) === 1)
        <svg viewBox="0 0 100 100" aria-hidden="true" focusable="false"
             class="pointer-events-none absolute right-3 bottom-3 h-14 w-14 text-amarillo-600 opacity-40">
            <use href="#deco-rama"></use>
        </svg>
    @endif
</article>
