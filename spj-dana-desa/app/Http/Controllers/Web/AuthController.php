<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

/**
 * Login dashboard web berbasis session (Bab IV.3: "session-based untuk dashboard web"),
 * dengan alur 2FA sesi (bukan token bertingkat seperti API mobile di Api\AuthController).
 */
class AuthController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Akun tidak aktif. Hubungi admin sistem.']);
        }

        if (! $user->wajib2fa()) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        Session::put('2fa_pending_user_id', $user->id);
        Session::put('2fa_remember', $request->boolean('remember'));

        if (! $user->two_factor_secret) {
            $secret = $this->twoFactor->generateSecret();
            $user->forceFill(['two_factor_secret' => encrypt($secret)])->save();
            Session::put('2fa_otpauth_url', $this->twoFactor->otpAuthUrl($user, $secret));
        }

        return redirect()->route('login.2fa');
    }

    public function show2fa()
    {
        if (! Session::has('2fa_pending_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.verify-2fa', [
            'otpauthUrl' => Session::get('2fa_otpauth_url'),
        ]);
    }

    public function verify2fa(Request $request): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'string']]);

        $userId = Session::get('2fa_pending_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $this->twoFactor->verify(decrypt($user->two_factor_secret), $request->string('otp'))) {
            return back()->withErrors(['otp' => 'Kode OTP salah.']);
        }

        $user->two_factor_confirmed_at ??= now();
        $user->save();

        Auth::login($user, (bool) Session::pull('2fa_remember', false));
        Session::forget(['2fa_pending_user_id', '2fa_otpauth_url']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
