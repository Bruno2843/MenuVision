<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Redirect authenticated users who lack one of the allowed roles to
     * their own dashboard. Guests are handled by the auth middleware.
     *
     * A deactivated account is signed out instead of being redirected, which is
     * what keeps it from bouncing between the login page and the panel it can no
     * longer use: the session that admitted it is destroyed on the way out.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! $user->estaActivo()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Tu cuenta está desactivada.');
        }

        $allowed = array_map(
            static fn (string $role): Role => Role::from($role),
            $roles,
        );

        if (in_array($user->role, $allowed, true)) {
            return $next($request);
        }

        return redirect()->route($user->role->dashboardRoute());
    }
}
