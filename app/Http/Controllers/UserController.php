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
        ]);
    }

    public function update(UpdateUserRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Profile updated.');
    }
}
