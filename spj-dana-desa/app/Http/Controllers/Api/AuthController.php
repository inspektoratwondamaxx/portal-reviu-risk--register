<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Bab VI.2 kajian teknis. Token bertingkat: token "pre-2fa" berumur pendek diterbitkan saat
 * kredensial valid tapi OTP belum diverifikasi; token penuh (semua abilities role) diterbitkan
 * setelah login-2fa sukses. Role selain inspektorat/admin langsung menerima token penuh.
 */
class AuthController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            return $this->fail('Email atau kata sandi salah.', status: 401);
        }

        if (! $user->is_active) {
            return $this->fail('Akun tidak aktif. Hubungi admin sistem.', status: 403);
        }

        if (! $user->wajib2fa()) {
            return $this->ok([
                'requires_2fa' => false,
                'token' => $user->createToken('mobile', [$user->role])->plainTextToken,
                'user' => $user->only(['id', 'name', 'email', 'role', 'kampung_id']),
            ]);
        }

        $preAuthToken = $user->createToken('pre-2fa', ['2fa-pending'], now()->addMinutes(5))->plainTextToken;

        if (! $user->two_factor_secret) {
            $secret = $this->twoFactor->generateSecret();
            $user->forceFill(['two_factor_secret' => encrypt($secret)])->save();

            return $this->ok([
                'requires_2fa' => true,
                'setup_required' => true,
                'otpauth_url' => $this->twoFactor->otpAuthUrl($user, $secret),
                'pre_auth_token' => $preAuthToken,
            ]);
        }

        return $this->ok([
            'requires_2fa' => true,
            'setup_required' => false,
            'pre_auth_token' => $preAuthToken,
        ]);
    }

    public function login2fa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Data tidak valid.', $validator->errors()->toArray());
        }

        $token = $request->user()?->currentAccessToken();

        if (! $request->user() || ! $token || ! in_array('2fa-pending', $token->abilities, true)) {
            return $this->fail('Sesi verifikasi 2FA tidak valid, silakan login ulang.', status: 401);
        }

        $user = $request->user();

        if (! $this->twoFactor->verify(decrypt($user->two_factor_secret), $request->string('otp'))) {
            return $this->fail('Kode OTP salah.', status: 401);
        }

        $user->two_factor_confirmed_at ??= now();
        $user->save();

        $token->delete();

        return $this->ok([
            'token' => $user->createToken('mobile', [$user->role])->plainTextToken,
            'user' => $user->only(['id', 'name', 'email', 'role', 'kampung_id']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->ok(['message' => 'Berhasil keluar.']);
    }

    public function me(Request $request)
    {
        return $this->ok($request->user()->load('kampung'));
    }
}
