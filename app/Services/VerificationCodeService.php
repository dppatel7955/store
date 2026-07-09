<?php

namespace App\Services;

use App\Models\VerificationCode;

class VerificationCodeService
{
    public static function issue(string $type, string $identifier): VerificationCode
    {
        return VerificationCode::updateOrCreate(
            ['type' => $type, 'identifier' => $identifier],
            [
                'code' => (string) random_int(100000, 999999),
                'expires_at' => now()->addSeconds(VerificationCode::OTP_VALID_SECONDS),
                'verified_at' => null,
            ]
        );
    }

    public static function find(string $type, string $identifier): ?VerificationCode
    {
        return VerificationCode::where('type', $type)
            ->where('identifier', $identifier)
            ->first();
    }

    public static function timerState(?VerificationCode $verification): array
    {
        if (!$verification) {
            return [
                'resendAvailableIn' => 0,
                'otpExpiresIn' => 0,
            ];
        }

        return [
            'resendAvailableIn' => $verification->secondsUntilResendAllowed(),
            'otpExpiresIn' => $verification->secondsUntilExpiry(),
        ];
    }
}
