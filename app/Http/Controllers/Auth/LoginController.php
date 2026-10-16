<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->role->dashboardRoute());
        }

        return view('auth.login');
    }

    /**
     * Attempt to authenticate the staff member.
     *
     * The password is verified first and the account is deactivated afterwards,
     * so a wrong password reports the same failure whether the account is active
     * or not. A deactivated account is signed back out immediately: leaving the
     * session open would let it reach the staff panels on a cookie that was
     * issued while the account was still active.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! Auth::user()->estaActivo()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada. Pedile a un administrador que la reactive.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route(Auth::user()->role->dashboardRoute()));
    }

    /**
     * Log the staff member out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
