<div class="space-y-4">
    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-gray-300">Nombre</label>
        <input
            id="name"
            type="text"
            name="name"
            value="{{ old('name', $user->name) }}"
            required
            autofocus
            maxlength="255"
            autocomplete="name"
            class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-medium text-gray-300">Correo</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email', $user->email) }}"
            required
            maxlength="255"
            autocomplete="username"
            class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
        <p class="mt-1 text-xs text-gray-500">Es el usuario con el que esta persona inicia sesión.</p>
    </div>

    <div>
        <label for="role" class="mb-1 block text-sm font-medium text-gray-300">Rol</label>
        <select
            id="role"
            name="role"
            required
            class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
        >
            @foreach ($roles as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('role', $user->role?->value) === $valor)>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Determina a qué panel entra la persona al iniciar sesión.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-gray-300">
                {{ $user->exists ? 'Nueva contraseña' : 'Contraseña' }}
            </label>
            <input
                id="password"
                type="password"
                name="password"
                @required(! $user->exists)
                minlength="8"
                autocomplete="new-password"
                class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
            >
            @if ($user->exists)
                <p class="mt-1 text-xs text-gray-500">Dejala vacía para no cambiarla.</p>
            @endif
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-300">
                Repetir contraseña
            </label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                @required(! $user->exists)
                minlength="8"
                autocomplete="new-password"
                class="w-full rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
            >
        </div>
    </div>

    <div>
        <span class="mb-1 block text-sm font-medium text-gray-300">Acceso</span>
        <label class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-gray-700 bg-gray-800 px-3 py-2">
            {{-- Before the checkbox, never after: PHP keeps the last value sent for
                 a repeated field name, so a trailing hidden input would overwrite
                 the ticked "1" and lock the account out. --}}
            <input type="hidden" name="activo" value="0">
            <input
                type="checkbox"
                name="activo"
                value="1"
                @checked(old('activo', $user->exists ? $user->estaActivo() : true))
                class="rounded border-gray-600 bg-gray-900 text-gold-500 focus:ring-gold-500"
            >
            <span class="text-sm text-gray-300">Puede iniciar sesión</span>
        </label>
    </div>
</div>
