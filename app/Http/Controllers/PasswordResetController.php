<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request)
    {
        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Always show the same status message to prevent user enumeration
        $statusMessage = 'Jika alamat email terdaftar, tautan reset kata sandi telah dikirimkan.';

        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $resetUrl = route('password.reset', ['token' => $token, 'email' => $email]);

            // In local/testing mode, log and optionally flash the link for zero-friction demo testing
            logger("Password reset requested for {$email}: {$resetUrl}");

            if (app()->environment('local', 'testing')) {
                session()->flash('demo_reset_link', $resetUrl);
            }
        }

        return back()->with('status', $statusMessage);
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(ResetPasswordRequest $request)
    {
        $email = strtolower(trim($request->email));

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            throw ValidationException::withMessages(['email' => 'Permintaan reset kata sandi tidak ditemukan atau telah kedaluwarsa.']);
        }

        // Expiration check (60 minutes)
        if (now()->subMinutes(60)->gt($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages(['email' => 'Tautan reset kata sandi telah kedaluwarsa. Silakan ajukan ulang.']);
        }

        if (! Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages(['email' => 'Token reset kata sandi tidak valid.']);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => 'Pengguna tidak ditemukan.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return to_route('login')
            ->with('success', 'Kata sandi Anda berhasil diatur ulang. Silakan masuk menggunakan kata sandi baru Anda.');
    }
}

