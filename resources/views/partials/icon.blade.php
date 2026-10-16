{{--
    Inline icon for the staff panels.

    Rendered with @include rather than as a component: the project has no
    resources/views/components folder, and the icon is a fixed set of paths
    rather than a reusable element with slots.

        @include('partials.icon', ['icon' => 'platos', 'iconClass' => 'h-4 w-4'])

    Every icon is drawn on a 24x24 grid with the same stroke weight, so they sit
    evenly next to each other in the sidebar and in the top bar.
--}}
@php
    $icon ??= 'panel';
    $iconClass ??= 'h-5 w-5';
@endphp

<svg
    viewBox="0 0 24 24"
    aria-hidden="true"
    focusable="false"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="{{ $iconClass }}"
>
    @switch($icon)
        @case('panel')
            <path d="M3 10.5 12 4l9 6.5" />
            <path d="M5 9.8V20h14V9.8" />
            <path d="M9.5 20v-5.5h5V20" />
            @break

        @case('categorias')
            <path d="M3.5 7.5 12 3l8.5 4.5-8.5 4.5z" />
            <path d="M3.5 12 12 16.5 20.5 12" />
            <path d="M3.5 16.5 12 21l8.5-4.5" />
            @break

        @case('platos')
            <path d="M6 3v8a2.5 2.5 0 0 0 5 0V3" />
            <path d="M8.5 11v10" />
            <path d="M17.5 3c-1.5 1.5-2 3-2 5s.5 3 2 3.5V21" />
            @break

        @case('ingredientes')
            <path d="M20 4c-6 0-11 4-11 9a5 5 0 0 0 5 5c5 0 6-6 6-14z" />
            <path d="M4 20c2-4 5-6 9-7" />
            @break

        @case('mesas')
            <rect x="3" y="4" width="18" height="10" rx="2" />
            <path d="M6 14v6M18 14v6M3 9h18" />
            @break

        @case('pedidos')
            <path d="M6 3h12v18l-2-1.5L14 21l-2-1.5L10 21l-2-1.5L6 21z" />
            <path d="M9.5 8h5M9.5 12h5" />
            @break

        @case('usuarios')
            <circle cx="9" cy="8" r="3.2" />
            <path d="M3.5 20a5.5 5.5 0 0 1 11 0" />
            <path d="M16 5.5a3 3 0 0 1 0 5.5" />
            <path d="M17.5 14.5a5 5 0 0 1 3.5 5.5" />
            @break

        @case('qr')
            <rect x="3.5" y="3.5" width="6.5" height="6.5" rx="1.5" />
            <rect x="14" y="3.5" width="6.5" height="6.5" rx="1.5" />
            <rect x="3.5" y="14" width="6.5" height="6.5" rx="1.5" />
            <path d="M14 14h3v3h-3zM20.5 14v3M14 20.5h3M20.5 17.5v3h-3" />
            @break

        @case('mas')
            <path d="M12 5v14M5 12h14" />
            @break

        @case('editar')
            <path d="M4 20h4l10-10a2.8 2.8 0 0 0-4-4L4 16z" />
            <path d="M13.5 6.5 17.5 10.5" />
            @break

        @case('basura')
            <path d="M4 7h16" />
            <path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
            <path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12" />
            <path d="M10.5 11v6M13.5 11v6" />
            @break

        @case('ojo')
            <path d="M2.5 12S6 6 12 6s9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />
            <circle cx="12" cy="12" r="2.8" />
            @break

        @case('imagen')
            <rect x="3" y="4.5" width="18" height="15" rx="2.5" />
            <circle cx="8.5" cy="10" r="1.6" />
            <path d="M4 17l4.5-4.5 3.5 3.5 3-3L20 17.5" />
            @break

        @case('cubo')
            <path d="M12 3 4 7v10l8 4 8-4V7z" />
            <path d="M4 7l8 4 8-4M12 11v10" />
            @break

        @case('subir')
            <path d="M12 16V4" />
            <path d="M7.5 8.5 12 4l4.5 4.5" />
            <path d="M4 15v3.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V15" />
            @break

        @case('volver')
            <path d="M20 12H4" />
            <path d="M10 6l-6 6 6 6" />
            @break

        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break

        @case('cerrar')
            <path d="M6 6l12 12M18 6 6 18" />
            @break

        @case('salir')
            <path d="M14 4H6.5A1.5 1.5 0 0 0 5 5.5v13A1.5 1.5 0 0 0 6.5 20H14" />
            <path d="M17 8.5 20.5 12 17 15.5M20 12H9" />
            @break

        @default
            <circle cx="12" cy="12" r="8" />
    @endswitch
</svg>
