<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List the staff accounts.
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form to create a staff account.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User,
            'roles' => Role::options(),
        ]);
    }

    /**
     * Store a new staff account.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except('activo');
        $datos['activo'] = $request->boolean('activo');

        User::create($datos);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario creado.');
    }

    /**
     * Show the form to edit a staff account.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::options(),
        ]);
    }

    /**
     * Update a staff account.
     *
     * Three things are refused here, each of which would leave the installation
     * with no way back in: deactivating your own account while signed in with
     * it, deactivating the last active administrator, and demoting the last
     * active administrator to another role.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $activo = $request->boolean('activo');
        $rol = $request->string('role')->toString();

        $quedariaSinAdmins = $this->esUltimoAdministradorActivo($user)
            && (! $activo || $rol !== Role::Administrador->value);

        if ($quedariaSinAdmins) {
            return back()->with(
                'error',
                'No podés dejar la instalación sin ningún administrador activo.',
            );
        }

        if (! $activo && $user->is($request->user())) {
            return back()->with(
                'error',
                'No podés desactivar tu propia cuenta. Pedile a otro administrador que lo haga.',
            );
        }

        $datos = $request->safe()->except(['password', 'activo']);
        $datos['activo'] = $activo;

        if ($request->filled('password')) {
            $datos['password'] = $request->validated('password');
        }

        $user->update($datos);

        return redirect()
            ->route('admin.users.index')
            ->with('status', $request->filled('password') ? 'Usuario actualizado y contraseña cambiada.' : 'Usuario actualizado.');
    }

    /**
     * Whether this account is the only administrator that can still sign in.
     */
    private function esUltimoAdministradorActivo(User $user): bool
    {
        return $user->isAdmin()
            && $user->estaActivo()
            && User::query()
                ->administradoresActivos()
                ->where('id', '!=', $user->id)
                ->doesntExist();
    }
}
