<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile', [
            'user' => auth()->user()->only('name', 'email', 'phone', 'preferred_location'),
            // OAuth-only accounts have no password to confirm, so the page shows
            // a set-password form instead of a change-password one.
            'hasPassword' => auth()->user()->password !== null,
        ]);
    }

    public function update(UpdateUserRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Profile updated.');
    }
}
