{{--
    Line decorations for the public menu.

    Drawn as hidden symbols and placed with `<use>`, so each decoration lives
    here once and the views only decide where it goes. They are all strokes on
    a transparent background and are placed with `pointer-events-none`, so they
    never intercept a click or cover the text.
--}}
<svg aria-hidden="true" focusable="false" class="hidden">
    <defs>
        {{-- A sprig drawn with a single continuous line. --}}
        <g id="deco-rama" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 94C26 78 40 56 46 28"/>
            <path d="M22 76c-4-9-4-17 0-24 5 6 6 15 0 24Z"/>
            <path d="M34 60c-3-10-2-18 3-24 4 7 3 16-3 24Z"/>
            <path d="M45 40c-1-9 1-15 6-19 3 7 1 14-6 19Z"/>
        </g>

        {{-- Four-point sparkle. --}}
        <g id="deco-brillo" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 2c2.6 9.4 5 11.8 14.4 14.4C25 19 22.6 21.4 20 30.8 17.4 21.4 15 19 5.6 16.4 15 13.8 17.4 11.4 20 2Z"/>
        </g>

        {{-- Loose hand-drawn curve, used to underline headings. --}}
        <g id="deco-trazo" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round">
            <path d="M4 18C34 8 62 22 96 12"/>
        </g>

        {{-- Corner bracket, for the bottom corners of a card. --}}
        <g id="deco-esquina" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 26V4h22"/>
            <path d="M12 34V12h22" opacity="0.45"/>
        </g>

        {{-- Small dotted garnish arc. --}}
        <g id="deco-puntos" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round">
            <path d="M6 30h2.4M18 30h2.4M30 30h2.4M42 30h2.4M54 30h2.4M66 30h2.4"/>
        </g>
    </defs>
</svg>
