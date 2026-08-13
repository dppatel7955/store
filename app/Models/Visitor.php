<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_uuid',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'screen_resolution',
        'language',
        'timezone',
        'connection_type',
        'landing_page',
        'current_page',
        'pages_history',
        'referrer',
        'page_views',
        'total_visits',
        'cart_items_count',
        'cart_total',
        'country',
        'state',
        'city',
        'isp',
        'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'page_views' => 'integer',
        'total_visits' => 'integer',
        'cart_items_count' => 'integer',
        'cart_total' => 'float',
        'pages_history' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        if ($this->user) {
            return $this->user->name;
        }
        if (! empty($this->guest_name)) {
            return $this->guest_name;
        }
        return 'Guest Visitor #' . substr($this->visitor_uuid, 0, 6);
    }

    public function displayEmail(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    public function displayPhone(): ?string
    {
        return $this->guest_phone ?? $this->user?->phone ?? null;
    }

    public function hasLeadInfo(): bool
    {
        return ! empty($this->user_id) || ! empty($this->guest_name) || ! empty($this->guest_email) || ! empty($this->guest_phone);
    }

    public function hasCart(): bool
    {
        return (int) $this->cart_items_count > 0;
    }

    public function isOnline(int $minutes = 15): bool
    {
        return $this->last_activity_at && $this->last_activity_at->gt(now()->subMinutes($minutes));
    }

    public function scopeActiveNow($query, int $minutes = 15)
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('last_activity_at', now()->today());
    }

    public function deviceIcon(): string
    {
        return match (strtolower($this->device_type)) {
            'mobile' => '📱',
            'tablet' => '📟',
            default => '💻',
        };
    }
}
