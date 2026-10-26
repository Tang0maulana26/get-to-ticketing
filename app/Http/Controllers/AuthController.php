<?php
namespace App\Http\Controllers;

use App\Http\Requests\{LoginRequest, RegisterRequest};
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login() { return view('auth.login'); }
    public function register() { return view('auth.register'); }
    public function authenticate(LoginRequest $request)
    {
        if (!Auth::attempt(['email' => strtolower(trim($request->email)), 'password' => $request->password])) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi belum sesuai.']);
        }
        $request->session()->regenerate();
        return redirect()->intended(route('orders.index'));
    }
    public function store(RegisterRequest $request)
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));
        Auth::login($user); $request->session()->regenerate();
        return redirect()->intended(route('catalog'));
    }
    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return to_route('catalog');
    }
}
