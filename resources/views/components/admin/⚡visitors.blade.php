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

    public function updatingSearch() { $this->resetPage(); }
    public function updatingDeviceFilter() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingDateFilter() { $this->resetPage(); }

    public function deleteVisitor(int $id)
    {
        Visitor::destroy($id);
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
        $totalVisitors = Visitor::count();
        $todayVisitors = Visitor::whereDate('last_activity_at', now()->today())->count();
        $activeNow = Visitor::where('last_activity_at', '>=', now()->subMinutes(15))->count();
        $totalPageviews = (int) Visitor::sum('page_views');

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
        } elseif ($this->statusFilter === 'user') {
            $query->whereNotNull('user_id');
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

        return view('components.admin.⚡visitors', [
            'visitors' => $visitors,
            'totalVisitors' => $totalVisitors,
            'todayVisitors' => $todayVisitors,
            'activeNow' => $activeNow,
            'totalPageviews' => $totalPageviews,
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
                Visitors & Traffic Analytics
            </h1>
            <p class="text-xs text-slate-500 mt-1">Real-time tracking of guest visitors, logged-in buyers, devices, and traffic sources.</p>
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                <span>🟢</span> Live in last 15 minutes
            </p>
        </div>

        <!-- Today's Visitors -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Today's Visitors</span>
                <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 text-sm">📅</span>
            </div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($todayVisitors) }}</div>
            <p class="text-[11px] text-slate-400 font-medium">Unique store visits today</p>
        </div>

        <!-- Total All-Time Visitors -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Visitors</span>
                <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600 text-sm">👥</span>
            </div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($totalVisitors) }}</div>
            <p class="text-[11px] text-slate-400 font-medium">Recorded guest & user visits</p>
        </div>

        <!-- Total Pageviews -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pageviews</span>
                <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600 text-sm">👀</span>
            </div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($totalPageviews) }}</div>
            <p class="text-[11px] text-slate-400 font-medium">
                Avg. {{ $totalVisitors > 0 ? round($totalPageviews / $totalVisitors, 1) : 1 }} pages/visitor
            </p>
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
                placeholder="Search by IP, URL, Browser, OS, or User..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-9 pr-3 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 transition"
            />
        </div>

        <!-- Filter Selects -->
        <div class="flex items-center flex-wrap gap-2">
            <!-- Device -->
            <select wire:model.live="deviceFilter" class="bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 font-semibold focus:outline-none">
                <option value="all">All Devices</option>
                <option value="Mobile">Mobile Only</option>
                <option value="Desktop">Desktop Only</option>
                <option value="Tablet">Tablet Only</option>
            </select>

            <!-- Status -->
            <select wire:model.live="statusFilter" class="bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs text-slate-700 font-semibold focus:outline-none">
                <option value="all">All Visitors</option>
                <option value="online">🟢 Online Now</option>
                <option value="user">👤 Logged In Users</option>
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

    <!-- Visitors Table Card -->
    <div class="bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-4">Visitor / Identity</th>
                        <th class="py-3.5 px-4">Status & Location</th>
                        <th class="py-3.5 px-4">Device & Browser</th>
                        <th class="py-3.5 px-4">Current Page</th>
                        <th class="py-3.5 px-4">Source</th>
                        <th class="py-3.5 px-4 text-center">Views</th>
                        <th class="py-3.5 px-4">Last Seen</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($visitors as $visitor)
                        <tr class="hover:bg-slate-50/60 transition duration-150">
                            <!-- Identity -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ $visitor->user ? strtoupper(substr($visitor->user->name, 0, 1)) : $visitor->deviceIcon() }}
                                    </div>
                                    <div class="min-w-0">
                                        @if($visitor->user)
                                            <span class="font-bold text-slate-900 block truncate">{{ $visitor->user->name }}</span>
                                            <span class="text-[10px] text-indigo-600 font-medium block truncate">{{ $visitor->user->email }}</span>
                                        @else
                                            <span class="font-bold text-slate-800 block">Guest Visitor</span>
                                            <span class="text-[9px] text-slate-400 font-mono block">#{{ substr($visitor->visitor_uuid, 0, 8) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Status & Location -->
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
                                    <span class="text-[11px] font-mono text-slate-600 block">{{ $visitor->ip_address }}</span>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ $visitor->country ?? 'India' }}</span>
                                </div>
                            </td>

                            <!-- Device & Browser -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-0.5">
                                    <span class="font-bold text-slate-800 block">{{ $visitor->device_type }}</span>
                                    <span class="text-[11px] text-slate-500 block">{{ $visitor->browser }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $visitor->platform }}</span>
                                </div>
                            </td>

                            <!-- Current Page -->
                            <td class="py-3.5 px-4 max-w-[200px]">
                                <div class="space-y-0.5">
                                    <a href="{{ $visitor->current_page }}" target="_blank" class="text-[11px] font-medium text-indigo-600 hover:underline block truncate" title="{{ $visitor->current_page }}">
                                        {{ parse_url($visitor->current_page, PHP_URL_PATH) ?: '/' }}
                                    </a>
                                    @if($visitor->landing_page && $visitor->landing_page !== $visitor->current_page)
                                        <span class="text-[9px] text-slate-400 block truncate" title="Entry: {{ $visitor->landing_page }}">
                                            Entry: {{ parse_url($visitor->landing_page, PHP_URL_PATH) ?: '/' }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Referrer -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $visitor->referrer ?: 'Direct' }}
                                </span>
                            </td>

                            <!-- Pageviews -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-black text-slate-900">{{ $visitor->page_views }}</span>
                            </td>

                            <!-- Last Seen -->
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800 block">{{ $visitor->last_activity_at ? $visitor->last_activity_at->diffForHumans() : 'Just now' }}</span>
                                <span class="text-[9px] text-slate-400 block">{{ $visitor->created_at->format('M d, H:i') }}</span>
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 text-right">
                                <button 
                                    type="button"
                                    wire:click="deleteVisitor({{ $visitor->id }})"
                                    wire:confirm="Delete this visitor record?"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                    title="Delete Record"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10">
                                <div class="space-y-2">
                                    <span class="text-3xl">👥</span>
                                    <h4 class="text-xs font-bold text-slate-700">No visitor records found</h4>
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
</div>
