{{--
    Icon set for the public menu.

    Rendered once per page from the layout and referenced with
    `<use href="#icono-...">`, so the category bar costs no extra requests and
    every category keeps the same stroke weight and optical size.
--}}
<svg aria-hidden="true" focusable="false" class="hidden">
    <defs>
        <g id="icono-plato" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <circle cx="12" cy="12" r="4.4"/>
        </g>

        <g id="icono-cloche" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3.4 18.4h17.2"/>
            <path d="M5.4 18.4a6.6 6.6 0 0 1 13.2 0"/>
            <path d="M12 8.6V7.2"/>
            <circle cx="12" cy="5.6" r="1.5"/>
            <path d="M7.4 18.4a4.6 4.6 0 0 1 4.6-4.4"/>
        </g>

        <g id="icono-burger" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4.6 10.4a7.4 4.6 0 0 1 14.8 0"/>
            <path d="M4.6 10.4h14.8"/>
            <path d="M3.4 14.6c1.4-1.3 2.6.5 4 .1s2.6.9 4 .1 2.6.9 4 .1 2.6.9 4 .1"/>
            <path d="M3.6 17.4h16.8"/>
            <path d="M4.6 20.4h14.8a1.6 1.6 0 0 0 1.6-1.6H3a1.6 1.6 0 0 0 1.6 1.6Z"/>
        </g>

        <g id="icono-pizza" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3.2 3.6 18.6a1 1 0 0 0 .9 1.5h15a1 1 0 0 0 .9-1.5Z"/>
            <path d="M8.4 12.6h7.2"/>
            <circle cx="12" cy="16.2" r="1.1"/>
        </g>

        <g id="icono-pasta" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3.6 11.4h16.8a8.4 8.4 0 0 1-8.4 8.4 8.4 8.4 0 0 1-8.4-8.4Z"/>
            <path d="M8.8 11.4c0-2.1 1.4-3.4 3.2-3.4s3.2 1.3 3.2 3.4"/>
            <path d="M6.6 7.6c1.6-1.2 3 .4 4.6-.4"/>
        </g>

        <g id="icono-postre" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 3.4h8l-1.5 16.4a1.6 1.6 0 0 1-1.6 1.4h-1.8a1.6 1.6 0 0 1-1.6-1.4Z"/>
            <path d="M8.5 9.4h7"/>
            <path d="M8.1 14.4h7.8"/>
            <path d="M12 3.4V1.6"/>
        </g>

        <g id="icono-bebida" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6.8 6h10.4l-1.2 13.6a1.6 1.6 0 0 1-1.6 1.4H9.6A1.6 1.6 0 0 1 8 19.6Z"/>
            <path d="M7.2 11h9.6"/>
            <path d="M15 6 18.6 2.4"/>
        </g>

        {{-- "Todas": every category at once. --}}
        <g id="icono-todas" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3.4" y="3.4" width="7" height="7" rx="1.6"/>
            <rect x="13.6" y="3.4" width="7" height="7" rx="1.6"/>
            <rect x="3.4" y="13.6" width="7" height="7" rx="1.6"/>
            <rect x="13.6" y="13.6" width="7" height="7" rx="1.6"/>
        </g>

        <g id="icono-ensalada" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3.2 12.2h17.6a8.8 8.8 0 0 1-8.8 8.8 8.8 8.8 0 0 1-8.8-8.8Z"/>
            <path d="M7 12.2c0-3.4 2.2-6 5-6s5 2.6 5 6"/>
            <path d="M9.4 8.2c1.6-1.2 3.6-1.2 5.2 0"/>
        </g>

        {{-- Chef hat, used by the brand block in the header. --}}
        <g id="icono-gorro" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 20.4h10"/>
            <path d="M7.6 20.4v-3.2a4.6 4.6 0 0 1-2.2-3.9 3.4 3.4 0 0 1 2.6-3.3 4.2 4.2 0 0 1 8 0 3.4 3.4 0 0 1 2.6 3.3 4.6 4.6 0 0 1-2.2 3.9v3.2"/>
            <path d="M9.6 17.2h4.8"/>
        </g>

        {{-- Table/mesa icon used by the mesa badge in the header. --}}
        <g id="icono-mesa" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3.4 8.8h17.2"/>
            <path d="M6 8.8v7.6"/>
            <path d="M18 8.8v7.6"/>
            <path d="M4.4 5.6h15.2a1 1 0 0 1 1 1v2.2H3.4V6.6a1 1 0 0 1 1-1Z"/>
        </g>

        {{-- Open menu book, used by the "Ver plato" button. --}}
        <g id="icono-libro" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 6.4C10.4 4.8 8.2 4.2 4 4.4v13.2c4.2-.2 6.4.4 8 2 1.6-1.6 3.8-2.2 8-2V4.4c-4.2-.2-6.4.4-8 2Z"/>
            <path d="M12 6.4v13.2"/>
        </g>

        {{-- Augmented reality: a cube seen in perspective, used by the
             "Ver en mi espacio" button. --}}
        <g id="icono-ar" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2.8 20.6 7v10L12 21.2 3.4 17V7Z"/>
            <path d="M3.4 7 12 11.4 20.6 7"/>
            <path d="M12 11.4v9.8"/>
        </g>
    </defs>
</svg>
