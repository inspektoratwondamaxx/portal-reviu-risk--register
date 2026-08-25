<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

/**
 * Autentikasi dua faktor wajib untuk role inspektorat & admin (Bab IV.3 kajian teknis).
 * Kompatibel aplikasi authenticator standar (Google Authenticator, Authy, dll) via protokol TOTP.
 */
class TwoFactorService
{
    public function __construct(private readonly Google2FA $engine) {}

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    public function otpAuthUrl(User $user, string $secret): string
    {
        return $this->engine->getQRCodeUrl(
            config('app.name', 'SPJ Dana Desa'),
            $user->email,
            $secret,
        );
    }

    public function verify(string $secret, string $otp): bool
    {
        return $this->engine->verifyKey($secret, $otp) !== false;
    }
}
