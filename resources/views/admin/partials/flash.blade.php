@if (session('status'))
    <div class="mb-4 rounded-lg border-2 border-green-900 bg-green-950 px-4 py-3 text-sm text-green-300">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-lg border-2 border-red-900 bg-red-950 px-4 py-3 text-sm text-red-300">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg border-2 border-red-900 bg-red-950 px-4 py-3 text-sm text-red-300">
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
