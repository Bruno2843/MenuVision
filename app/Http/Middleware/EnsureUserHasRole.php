<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Redirect authenticated users who lack one of the allowed roles to
     * their own dashboard. Guests are handled by the auth middleware.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
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
