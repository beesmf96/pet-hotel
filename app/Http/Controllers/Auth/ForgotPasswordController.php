<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Always reports the same outcome whether or not the address is registered.
     *
     * Surfacing Password::INVALID_USER (or RESET_THROTTLED, which also only fires
     * for real accounts) turns this endpoint into a user-enumeration oracle: an
     * attacker learns which emails hold accounts by diffing the responses.
     */
    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
