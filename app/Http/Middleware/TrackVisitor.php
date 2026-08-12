<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use App\Models\Visitor;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET storefront requests (skip admin, livewire internal endpoints, ajax assets)
        if (! $request->isMethod('GET') || $request->is('admin*') || $request->is('livewire*') || $request->is('up') || $request->ajax()) {
            return $response;
        }

        try {
            $this->trackVisitor($request);
        } catch (\Throwable $e) {
            // Silently catch tracking errors to never block storefront page rendering
            report($e);
        }

        return $response;
    }

    protected function trackVisitor(Request $request): void
    {
        $cookieName = 'saffron_visitor_uuid';
        $uuid = $request->cookie($cookieName) ?: $request->cookies->get($cookieName);

        if (! $uuid) {
            $uuid = (string) Str::uuid();
            Cookie::queue(Cookie::make($cookieName, $uuid, 60 * 24 * 365, null, null, false, false));
        }

        $userAgent = (string) $request->userAgent();
        $ip = (string) $request->ip();
        $currentUrl = $request->fullUrl();
        $rawReferrer = $request->headers->get('referer');
        $parsedReferrer = $this->parseReferrer($rawReferrer, $request->getHost());

        $deviceInfo = $this->parseUserAgent($userAgent);

        // Find existing record for this UUID today or create new
        $visitor = Visitor::where('visitor_uuid', $uuid)
            ->whereDate('last_activity_at', now()->today())
            ->first();

        if ($visitor) {
            $visitor->update([
                'current_page' => $currentUrl,
                'ip_address' => $ip,
                'user_id' => auth()->id() ?? $visitor->user_id,
                'page_views' => $visitor->page_views + 1,
                'last_activity_at' => now(),
            ]);
        } else {
            Visitor::create([
                'visitor_uuid' => $uuid,
                'user_id' => auth()->id(),
                'ip_address' => $ip,
                'user_agent' => substr($userAgent, 0, 500),
                'device_type' => $deviceInfo['device'],
                'browser' => $deviceInfo['browser'],
                'platform' => $deviceInfo['platform'],
                'landing_page' => $currentUrl,
                'current_page' => $currentUrl,
                'referrer' => $parsedReferrer,
                'page_views' => 1,
                'country' => $this->detectCountry($ip),
                'city' => 'India',
                'last_activity_at' => now(),
            ]);
        }
    }

    protected function parseUserAgent(string $userAgent): array
    {
        $device = 'Desktop';
        $platform = 'Unknown OS';
        $browser = 'Unknown Browser';

        // Platform detection
        if (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
            $device = 'Mobile';
        } elseif (preg_match('/iphone/i', $userAgent)) {
            $platform = 'iOS';
            $device = 'Mobile';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $platform = 'iPadOS';
            $device = 'Tablet';
        } elseif (preg_match('/windows nt/i', $userAgent)) {
            $platform = 'Windows';
            $device = 'Desktop';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
            $device = 'Desktop';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
            $device = 'Desktop';
        }

        // Specific tablet check
        if (preg_match('/tablet|tab/i', $userAgent)) {
            $device = 'Tablet';
        }

        // Browser detection
        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome|crios/i', $userAgent) && ! preg_match('/opr|opera/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/safari/i', $userAgent) && ! preg_match('/chrome|crios/i', $userAgent)) {
            $browser = 'Apple Safari';
        } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/opr|opera/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/samsungbrowser/i', $userAgent)) {
            $browser = 'Samsung Internet';
        }

        return [
            'device' => $device,
            'platform' => $platform,
            'browser' => $browser,
        ];
    }

    protected function parseReferrer(?string $referrer, string $host): string
    {
        if (empty($referrer)) {
            return 'Direct / Bookmark';
        }

        $parsed = parse_url($referrer);
        $refHost = $parsed['host'] ?? '';

        if (empty($refHost) || str_contains($refHost, $host)) {
            return 'Direct / Internal';
        }

        if (str_contains($refHost, 'google.')) {
            return 'Google Search';
        }
        if (str_contains($refHost, 'facebook.com') || str_contains($refHost, 'fb.com')) {
            return 'Facebook';
        }
        if (str_contains($refHost, 'instagram.com')) {
            return 'Instagram';
        }
        if (str_contains($refHost, 'whatsapp') || str_contains($refHost, 'wa.me')) {
            return 'WhatsApp';
        }
        if (str_contains($refHost, 't.co') || str_contains($refHost, 'twitter.com') || str_contains($refHost, 'x.com')) {
            return 'Twitter / X';
        }
        if (str_contains($refHost, 'youtube.com')) {
            return 'YouTube';
        }
        if (str_contains($refHost, 'bing.com')) {
            return 'Bing';
        }

        return $refHost;
    }

    protected function detectCountry(string $ip): string
    {
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'Localhost / India';
        }

        return 'India';
    }
}
