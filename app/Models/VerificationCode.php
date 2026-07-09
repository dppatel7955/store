<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    public const OTP_VALID_SECONDS = 120;

    public const RESEND_COOLDOWN_SECONDS = 120;

    protected $fillable = [
        'type',
        'identifier',
        'code',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Check if the verification code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the verification code is valid.
     */
    public function isValid(string $code): bool
    {
        return $this->code === $code && !$this->isExpired() && is_null($this->verified_at);
    }

    public function canResend(): bool
    {
        return $this->secondsUntilResendAllowed() === 0;
    }

    public function secondsUntilResendAllowed(): int
    {
        $availableAt = $this->updated_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        if ($availableAt->isPast()) {
            return 0;
        }

        return (int) now()->diffInSeconds($availableAt);
    }

    public function secondsUntilExpiry(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->expires_at);
    }
}
