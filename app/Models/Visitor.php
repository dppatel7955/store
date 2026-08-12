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
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'landing_page',
        'current_page',
        'referrer',
        'page_views',
        'country',
        'city',
        'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'page_views' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
