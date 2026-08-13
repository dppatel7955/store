<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Visitor;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $deviceFilter = 'all';
    public string $statusFilter = 'all';
    public string $dateFilter = 'all';

    // Inspector Modal
    public ?int $inspectVisitorId = null;

    public function updatingSearch() { $this->resetPage(); }
    public function updatingDeviceFilter() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingDateFilter() { $this->resetPage(); }

    public function openInspector(int $id)
    {
        $this->inspectVisitorId = $id;
    }

    public function closeInspector()
    {
        $this->inspectVisitorId = null;
    }

    public function deleteVisitor(int $id)
    {
        Visitor::destroy($id);
        if ($this->inspectVisitorId === $id) {
            $this->inspectVisitorId = null;
        }
        $this->dispatch('swal', title: 'Deleted', text: 'Visitor record removed successfully.', icon: 'success');
    }

    public function clearOldVisitors()
    {
        $deleted = Visitor::where('last_activity_at', '<', now()->subDays(30))->delete();
        $this->dispatch('swal', title: 'Cleaned Up', text: "{$deleted} visitor records older than 30 days were cleared.", icon: 'success');
    }

    public function render()
    {
        // KPI metrics
        $totalUniqueVisitors = Visitor::count();
        $todayVisitors = Visitor::whereDate('last_activity_at', now()->today())->count();
        $activeNow = Visitor::where('last_activity_at', '>=', now()->subMinutes(15))->count();
        $totalPageviews = (int) Visitor::sum('page_views');
        $activeCartsCount = Visitor::where('cart_items_count', '>', 0)->count();
        $identifiedLeadsCount = Visitor::where(function ($q) {
            $q->whereNotNull('user_id')
                ->orWhereNotNull('guest_email')
                ->orWhereNotNull('guest_phone');
        })->count();

        // Device Breakdown
        $deviceStats = Visitor::select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        $desktopCount = $deviceStats['Desktop'] ?? 0;
        $mobileCount = $deviceStats['Mobile'] ?? 0;
        $tabletCount = $deviceStats['Tablet'] ?? 0;
        $allDeviceSum = max(1, $desktopCount + $mobileCount + $tabletCount);

        // Top Referrers
        $topReferrers = Visitor::select('referrer', DB::raw('count(*) as count'))
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Query Visitors
        $query = Visitor::with('user')->latest('last_activity_at');

        if (! empty($this->search)) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', $s)
                    ->orWhere('visitor_uuid', 'like', $s)
                    ->orWhere('current_page', 'like', $s)
                    ->orWhere('landing_page', 'like', $s)
                    ->orWhere('browser', 'like', $s)
                    ->orWhere('platform', 'like', $s)
                    ->orWhere('screen_resolution', 'like', $s)
                    ->orWhere('timezone', 'like', $s)
                    ->orWhere('city', 'like', $s)
                    ->orWhere('guest_name', 'like', $s)
                    ->orWhere('guest_email', 'like', $s)
                    ->orWhere('guest_phone', 'like', $s)
                    ->orWhereHas('user', function ($uq) use ($s) {
                        $uq->where('name', 'like', $s)->orWhere('email', 'like', $s);
                    });
            });
        }

        if ($this->deviceFilter !== 'all') {
            $query->where('device_type', $this->deviceFilter);
        }

        if ($this->statusFilter === 'online') {
            $query->where('last_activity_at', '>=', now()->subMinutes(15));
        } elseif ($this->statusFilter === 'leads') {
            $query->where(function ($q) {
                $q->whereNotNull('user_id')
                    ->orWhereNotNull('guest_email')
                    ->orWhereNotNull('guest_phone');
            });
        } elseif ($this->statusFilter === 'cart') {
            $query->where('cart_items_count', '>', 0);
        } elseif ($this->statusFilter === 'returning') {
            $query->where('total_visits', '>', 1);
        } elseif ($this->statusFilter === 'guest') {
            $query->whereNull('user_id');
        }

        if ($this->dateFilter === 'today') {
            $query->whereDate('last_activity_at', now()->today());
        } elseif ($this->dateFilter === 'week') {
            $query->where('last_activity_at', '>=', now()->subDays(7));
        } elseif ($this->dateFilter === 'month') {
            $query->where('last_activity_at', '>=', now()->subDays(30));
        }

        $visitors = $query->paginate(15);

        // Inspected Visitor Object
        $inspectedVisitor = $this->inspectVisitorId ? Visitor::with('user')->find($this->inspectVisitorId) : null;

        return view('components.admin.⚡visitors', [
            'visitors' => $visitors,
            'inspectedVisitor' => $inspectedVisitor,
            'totalUniqueVisitors' => $totalUniqueVisitors,
            'todayVisitors' => $todayVisitors,
            'activeNow' => $activeNow,
            'totalPageviews' => $totalPageviews,
            'activeCartsCount' => $activeCartsCount,
            'identifiedLeadsCount' => $identifiedLeadsCount,
            'desktopPct' => round(($desktopCount / $allDeviceSum) * 100),
            'mobilePct' => round(($mobileCount / $allDeviceSum) * 100),
            'tabletPct' => round(($tabletCount / $allDeviceSum) * 100),
            'topReferrers' => $topReferrers,
        ]);
    }
};
?>

<div class="space-y-6" wire:poll.15s>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 text-xl">🌐</span>
                Unique Visitors & User Telemetry
            </h1>
            <p class="text-xs text-slate-500 mt-1">Strict 1-to-1 unique visitor fingerprinting, screen resolutions, live carts, and contact leads.</p>
        </div>

        <div class="flex items-center gap-2">
            <button 
                type="button"
                wire:click="$refresh"
                class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 transition flex items-center gap-1.5 shadow-2xs"
            >
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>

            <button 
                type="button"
                wire:click="clearOldVisitors"
                wire:confirm="Are you sure you want to clear visitor logs older than 30 days?"
                class="rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 px-3 py-2 text-xs font-bold text-rose-700 transition shadow-2xs"
            >
                Clean 30d+ Logs
            </button>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Active Now -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Now</span>
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
            </div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($activeNow) }}</div>
            <p class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                <span>🟢</span> Online in last 15 mins
            </p>
        </div>

        <!-- Today's Visitors -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Today's Visits</span>
                <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 text-sm">📅</span>
            </div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($todayVisitors) }}</div>
            <p class="text-[11px] text-slate-400 font-medium">Active sessions today</p>
        </div>

        <!-- Total Unique Visitors -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Unique Visitors</span>
                <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600 text-sm">👥</span>
            </div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($totalUniqueVisitors) }}</div>
            <p class="text-[11px] text-slate-400 font-medium">100% Unique Device Profiles</p>
        </div>

        <!-- Identified Leads -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Identified Leads</span>
                <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 text-sm">👤</span>
            </div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($identifiedLeadsCount) }}</div>
            <p class="text-[11px] text-emerald-600 font-medium">Known name / email / phone</p>
        </div>

        <!-- Active Carts -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Carts</span>
                <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600 text-sm">🛒</span>
            </div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($activeCartsCount) }}</div>
            <p class="text-[11px] text-amber-600 font-medium">Visitors with items in cart</p>
        </div>
    </div>

    <!-- Analytics Insights Row (Devices & Referrers) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Device Breakdown -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Device Breakdown</h3>
            
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                        <span>📱 Mobile</span>
                        <span>{{ $mobilePct }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $mobilePct }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                        <span>💻 Desktop</span>
                        <span>{{ $desktopPct }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $desktopPct }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                        <span>📟 Tablet</span>
                        <span>{{ $tabletPct }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $tabletPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Traffic Sources -->
        <div class="lg:col-span-2 bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Top Traffic Sources</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse($topReferrers as $ref)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-xs font-bold text-slate-800 truncate">{{ $ref->referrer ?: 'Direct / Bookmark' }}</span>
                        <span class="text-xs font-extrabold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full shrink-0 ml-2">
                            {{ number_format($ref->count) }} visits
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No traffic source data available yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs space-y-3 sm:space-y-0 sm:flex sm:items-center sm:justify-between gap-3">
        <!-- Search -->
        <div class="relative flex-1 max-w-md">
            <span class="absolute left-3.5 top-2.5 text-xs text-slate-400">🔍</span>
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search by Name, Phone, Email, IP, Screen, City, URL..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-9 pr-3 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 transition"
            />
        </div>

        <!-- Filter Selects -->
        <div class="flex items-center flex-wrap gap-2">
            <!-- Device -->
            <select wire:model.live="deviceFilter" class="bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 font-semibold focus:outline-none">
                <option value="all">All Devices</option>
                <option value="Mobile">📱 Mobile</option>
                <option value="Desktop">💻 Desktop</option>
                <option value="Tablet">📟 Tablet</option>
            </select>

            <!-- Status -->
            <select wire:model.live="statusFilter" class="bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 font-semibold focus:outline-none">
                <option value="all">All Visitors</option>
                <option value="online">🟢 Online Now</option>
                <option value="leads">👤 Identified Leads</option>
                <option value="cart">🛒 Active Cart (Abandoners)</option>
                <option value="returning">🔁 Returning (2+ Visits)</option>
                <option value="guest">🕵️ Guests Only</option>
            </select>

            <!-- Date -->
            <select wire:model.live="dateFilter" class="bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 font-semibold focus:outline-none">
                <option value="all">All Time</option>
                <option value="today">Today Only</option>
                <option value="week">Past 7 Days</option>
                <option value="month">Past 30 Days</option>
            </select>
        </div>
    </div>

    <!-- Unique Visitors Table Card -->
    <div class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-4">Visitor & Contact Identity</th>
                        <th class="py-3.5 px-4">Location & Network</th>
                        <th class="py-3.5 px-4">Device & Display</th>
                        <th class="py-3.5 px-4">Current Page</th>
                        <th class="py-3.5 px-4 text-center">Cart</th>
                        <th class="py-3.5 px-4 text-center">Visits / Views</th>
                        <th class="py-3.5 px-4">Last Active</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($visitors as $visitor)
                        <tr class="hover:bg-slate-50/60 transition duration-150">
                            <!-- Identity & Contact -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-9 w-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-extrabold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                        {{ $visitor->user ? strtoupper(substr($visitor->user->name, 0, 1)) : $visitor->deviceIcon() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900 block truncate">{{ $visitor->displayName() }}</span>
                                            @if($visitor->hasLeadInfo())
                                                <span class="bg-emerald-100 text-emerald-800 text-[9px] font-extrabold px-1.5 py-0.2 rounded">LEAD</span>
                                            @endif
                                        </div>

                                        @if($visitor->displayEmail())
                                            <span class="text-[10px] text-indigo-600 font-medium block truncate">{{ $visitor->displayEmail() }}</span>
                                        @endif

                                        @if($visitor->displayPhone())
                                            <div class="flex items-center gap-1 mt-0.5">
                                                <span class="text-[10px] text-slate-600 font-mono">{{ $visitor->displayPhone() }}</span>
                                                <a 
                                                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $visitor->displayPhone()) }}" 
                                                    target="_blank" 
                                                    class="text-emerald-600 hover:text-emerald-700 text-[10px] font-bold"
                                                    title="Chat on WhatsApp"
                                                >
                                                    💬 WhatsApp
                                                </a>
                                            </div>
                                        @elseif(! $visitor->displayEmail())
                                            <span class="text-[9px] text-slate-400 font-mono block">UUID: {{ substr($visitor->visitor_uuid, 0, 8) }}...</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Location & Network -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5">
                                        @if($visitor->isOnline(15))
                                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span class="text-[10px] font-extrabold uppercase text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200">Online</span>
                                        @else
                                            <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                                            <span class="text-[10px] font-semibold text-slate-400">Offline</span>
                                        @endif
                                    </div>
                                    <span class="text-[11px] font-mono text-slate-700 block font-semibold">{{ $visitor->ip_address }}</span>
                                    <span class="text-[10px] text-slate-500 font-medium">
                                        {{ $visitor->city ? $visitor->city . ', ' : '' }}{{ $visitor->state ? $visitor->state . ', ' : '' }}{{ $visitor->country ?? 'India' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Device & Display Screen -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-0.5">
                                    <span class="font-bold text-slate-800 block">{{ $visitor->browser }} on {{ $visitor->platform }}</span>
                                    @if($visitor->screen_resolution)
                                        <span class="text-[10px] text-indigo-700 font-mono bg-indigo-50 px-1.5 py-0.2 rounded border border-indigo-100 inline-block">
                                            🖥️ {{ $visitor->screen_resolution }}
                                        </span>
                                    @endif
                                    @if($visitor->timezone)
                                        <span class="text-[9px] text-slate-400 block truncate">⏰ {{ $visitor->timezone }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Current Page -->
                            <td class="py-3.5 px-4 max-w-[180px]">
                                <div class="space-y-0.5">
                                    <a href="{{ $visitor->current_page }}" target="_blank" class="text-[11px] font-semibold text-indigo-600 hover:underline block truncate" title="{{ $visitor->current_page }}">
                                        {{ parse_url($visitor->current_page, PHP_URL_PATH) ?: '/' }}
                                    </a>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-slate-100 text-slate-600 truncate">
                                        Via: {{ $visitor->referrer ?: 'Direct' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Live Cart Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($visitor->hasCart())
                                    <div class="inline-flex flex-col items-center p-1.5 rounded-xl bg-amber-50 border border-amber-200">
                                        <span class="text-xs font-black text-amber-800">🛒 {{ $visitor->cart_items_count }} items</span>
                                        <span class="text-[10px] font-extrabold text-amber-700">₹{{ number_format($visitor->cart_total) }}</span>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-300 font-bold">—</span>
                                @endif
                            </td>

                            <!-- Visits & Views -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="space-y-0.5">
                                    <span class="font-extrabold text-slate-900 block text-xs">{{ $visitor->total_visits }} {{ Str::plural('visit', $visitor->total_visits) }}</span>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ $visitor->page_views }} pageviews</span>
                                </div>
                            </td>

                            <!-- Last Active -->
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800 block">{{ $visitor->last_activity_at ? $visitor->last_activity_at->diffForHumans() : 'Just now' }}</span>
                                <span class="text-[9px] text-slate-400 block">First: {{ $visitor->created_at->format('M d, Y') }}</span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button 
                                        type="button"
                                        wire:click="openInspector({{ $visitor->id }})"
                                        class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[10px] transition border border-indigo-200"
                                        title="View Full Visitor Details"
                                    >
                                        🔍 Inspect
                                    </button>

                                    <button 
                                        type="button"
                                        wire:click="deleteVisitor({{ $visitor->id }})"
                                        wire:confirm="Delete this visitor record?"
                                        class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        title="Delete Record"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10">
                                <div class="space-y-2">
                                    <span class="text-3xl">👥</span>
                                    <h4 class="text-xs font-bold text-slate-700">No unique visitor records found</h4>
                                    <p class="text-[11px] text-slate-400">Visitors to your storefront will automatically appear here in real time.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($visitors->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $visitors->links() }}
            </div>
        @endif
    </div>

    <!-- Visitor Inspector Modal -->
    @if($inspectedVisitor)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="$wire.closeInspector()">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 font-extrabold text-xl flex items-center justify-center shadow-xs">
                            {{ $inspectedVisitor->user ? strtoupper(substr($inspectedVisitor->user->name, 0, 1)) : $inspectedVisitor->deviceIcon() }}
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900">{{ $inspectedVisitor->displayName() }}</h3>
                            <p class="text-xs text-slate-500 font-mono">UUID: {{ $inspectedVisitor->visitor_uuid }}</p>
                        </div>
                    </div>

                    <button 
                        type="button" 
                        wire:click="closeInspector"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                    >
                        ✕
                    </button>
                </div>

                <!-- Identity / Lead Card -->
                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Contact & Customer Lead Info</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold block">USER ACCOUNT</span>
                            <span class="font-bold text-slate-800">{{ $inspectedVisitor->user ? $inspectedVisitor->user->name . ' (Registered)' : 'Guest Visitor' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold block">EMAIL ADDRESS</span>
                            <span class="font-bold text-slate-800">{{ $inspectedVisitor->displayEmail() ?: 'Not Provided Yet' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold block">PHONE NUMBER</span>
                            @if($inspectedVisitor->displayPhone())
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $inspectedVisitor->displayPhone()) }}" target="_blank" class="font-bold text-emerald-600 hover:underline">
                                    {{ $inspectedVisitor->displayPhone() }} (WhatsApp 💬)
                                </a>
                            @else
                                <span class="font-bold text-slate-400">Not Provided Yet</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Telemetry & Device Specs Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <!-- Device & Display -->
                    <div class="rounded-2xl border border-slate-200 p-4 space-y-2">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <span>🖥️</span> Device & Hardware
                        </h4>
                        <div class="space-y-1 text-slate-600">
                            <p><strong class="text-slate-800">Device Type:</strong> {{ $inspectedVisitor->device_type }}</p>
                            <p><strong class="text-slate-800">Browser:</strong> {{ $inspectedVisitor->browser }}</p>
                            <p><strong class="text-slate-800">Platform OS:</strong> {{ $inspectedVisitor->platform }}</p>
                            <p><strong class="text-slate-800">Screen Resolution:</strong> {{ $inspectedVisitor->screen_resolution ?: 'Standard View' }}</p>
                            <p><strong class="text-slate-800">Language:</strong> {{ $inspectedVisitor->language ?: 'en' }}</p>
                            <p><strong class="text-slate-800">Timezone:</strong> {{ $inspectedVisitor->timezone ?: 'Asia/Kolkata' }}</p>
                        </div>
                    </div>

                    <!-- Location & Live Shopping -->
                    <div class="rounded-2xl border border-slate-200 p-4 space-y-2">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <span>📍</span> Location & Cart
                        </h4>
                        <div class="space-y-1 text-slate-600">
                            <p><strong class="text-slate-800">IP Address:</strong> <code class="font-mono text-indigo-600">{{ $inspectedVisitor->ip_address }}</code></p>
                            <p><strong class="text-slate-800">City / State:</strong> {{ $inspectedVisitor->city ?: 'Surat' }}, {{ $inspectedVisitor->state ?: 'Gujarat' }}</p>
                            <p><strong class="text-slate-800">Country:</strong> {{ $inspectedVisitor->country ?? 'India' }}</p>
                            <p><strong class="text-slate-800">Traffic Referrer:</strong> {{ $inspectedVisitor->referrer ?: 'Direct' }}</p>
                            <p><strong class="text-slate-800">Total Visits:</strong> {{ $inspectedVisitor->total_visits }} distinct sessions</p>
                            <p><strong class="text-slate-800">Live Cart Value:</strong> 
                                @if($inspectedVisitor->hasCart())
                                    <span class="text-amber-700 font-extrabold">🛒 {{ $inspectedVisitor->cart_items_count }} items (₹{{ number_format($inspectedVisitor->cart_total) }})</span>
                                @else
                                    <span class="text-slate-400">Empty Cart</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Browsing Journey Timeline -->
                <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <span>🧭</span> Recent Browsing Journey (Last 10 Pages)
                    </h4>

                    @if(! empty($inspectedVisitor->pages_history))
                        <div class="space-y-2">
                            @foreach($inspectedVisitor->pages_history as $idx => $step)
                                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="h-5 w-5 rounded-full bg-indigo-100 text-indigo-700 font-extrabold text-[10px] flex items-center justify-center shrink-0">{{ $idx + 1 }}</span>
                                        <a href="{{ $step['url'] }}" target="_blank" class="font-semibold text-indigo-600 hover:underline truncate">
                                            {{ $step['path'] ?: '/' }}
                                        </a>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono shrink-0 ml-2">{{ $step['time'] ?? '' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">No historical journey recorded yet.</p>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <span class="text-xs text-slate-400 font-medium">First seen: {{ $inspectedVisitor->created_at->format('M d, Y H:i:s') }}</span>
                    <button 
                        type="button" 
                        wire:click="closeInspector"
                        class="rounded-xl bg-slate-900 text-white font-bold text-xs px-5 py-2 hover:bg-slate-800 transition"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
