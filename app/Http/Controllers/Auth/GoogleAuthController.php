<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login')->with('status', 'Google sign-in failed. Please try again.');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Only adopt an existing password account if the provider says it
                // verified the address. Without this check, any provider that lets
                // a user claim an unverified email hands them that account. Google
                // does verify, so this is a guard against the second provider —
                // not a live hole today.
                if (! $this->providerVerifiedEmail($googleUser)) {
                    return redirect()->route('login')->with(
                        'status',
                        'That email is already registered. Please sign in with your password first.'
                    );
                }

                $user->update(['google_id' => $googleUser->getId()]);
            } else {
                $user = User::forceCreate([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                    // No password: the account signs in with Google until the user
                    // sets one from their profile. A random hash here would be
                    // indistinguishable from a password the user actually chose.
                    'password' => null,
                ]);
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('bookings.index');
    }

    /**
     * Socialite does not expose the OIDC `email_verified` claim as a first-class
     * property, so it has to be read out of the raw user payload. Absent claim is
     * treated as unverified.
     */
    private function providerVerifiedEmail(SocialiteUser $googleUser): bool
    {
        return (bool) ($googleUser->user['email_verified'] ?? false);
    }
}
