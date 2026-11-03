<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NeonAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use UnexpectedValueException;

class NeonAuthController extends Controller
{
    /**
     * Exchange a Neon Auth JWT for a Laravel session. Admins are not created here:
     * the token's verified email must belong to an existing user.
     */
    public function store(Request $request, NeonAuth $neonAuth): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        try {
            $claims = $neonAuth->verify($request->string('token'));
        } catch (UnexpectedValueException) {
            throw ValidationException::withMessages(['neon' => 'Your Neon Auth session could not be verified. Sign in again.']);
        }

        if (($claims['emailVerified'] ?? false) !== true) {
            throw ValidationException::withMessages(['neon' => 'Verify your email address with Neon Auth before signing in.']);
        }

        $user = User::where('email', $claims['email'] ?? null)->first();

        if (! $user) {
            throw ValidationException::withMessages(['neon' => 'This Neon Auth account is not an admin of ParaMeter.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
