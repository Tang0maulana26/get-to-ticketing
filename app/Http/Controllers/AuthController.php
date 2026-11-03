<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Handles visitor authentication: login/register forms, credential check, account creation and logout.
 * Validation lives in LoginRequest/RegisterRequest; login attempts are throttled by the "login" rate limiter.
 */
class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function authenticate(LoginRequest $request)
    {
        $login = strtolower(trim((string) $request->input('login', $request->input('email'))));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => $login, 'password' => $request->password];
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            $altField = $field === 'email' ? 'username' : 'email';
            if (! Auth::attempt([$altField => $login, 'password' => $request->password], $remember)) {
                $errorField = $request->filled('login') ? 'login' : 'email';
                throw ValidationException::withMessages([$errorField => 'Email/username atau kata sandi belum sesuai.']);
            }
        }
        $request->session()->regenerate();

        $target = redirect()->intended(route('orders.index'));

        return $request->expectsJson() ? response()->json(['redirect' => $target->getTargetUrl()]) : $target;
    }

    public function store(RegisterRequest $request)
    {
        Auth::login(User::create($request->safe()->only(['name', 'username', 'email', 'password'])));
        $request->session()->regenerate();

        return redirect()->intended(route('catalog'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('catalog');
    }
}
