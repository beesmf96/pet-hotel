<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        // password is deliberately outside $fillable, so force the assignment as
        // ResetPasswordController does. The 'hashed' cast handles hashing.
        $request->user()->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        return back()->with('success', 'Password updated.');
    }
}
