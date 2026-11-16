<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the password reset link request page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            // Mail server unreachable / bad Gmail credentials: log it, tell the user kindly.
            Log::error('Password reset e-mail failed: '.$e->getMessage());

            throw ValidationException::withMessages([
                'email' => 'We could not send the e-mail right now. Please try again in a few minutes.',
            ]);
        }

        // Same answer whether or not the account exists (no account enumeration).
        return back()->with('status', 'If an account exists for that e-mail, a reset link has been sent. Check your inbox and spam folder.');
    }
}
