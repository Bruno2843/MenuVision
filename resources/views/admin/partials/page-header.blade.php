{{--
    Page heading for the admin panel: an optional back link, a title and an
    optional line of context.

        @include('admin.partials.page-header', [
            'titulo' => 'Nueva categoría',
            'volver' => route('admin.categories.index'),
            'volverTexto' => 'Volver a las categorías',
        ])

    The back link is omitted when there is nowhere to go back to, which is the
    case for the index pages.
--}}
<div class="mb-6">
    @if (! empty($volver))
        <a href="{{ $volver }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gold-400">
            @include('partials.icon', ['icon' => 'volver', 'iconClass' => 'h-4 w-4'])
            {{ $volverTexto ?? 'Volver' }}
        </a>
    @endif

    <h1 class="{{ empty($volver) ? '' : 'mt-2 ' }}text-2xl font-bold text-white">
        {{ $titulo }}
    </h1>

    @if (! empty($subtitulo))
        <p class="mt-1 text-sm text-gray-400">{{ $subtitulo }}</p>
    @endif
</div>
