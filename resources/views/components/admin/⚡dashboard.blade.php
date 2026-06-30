<?php

use Livewire\Component;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $totalRevenue = 0;
    public $activeOrders = 0;
    public $lowStockAlerts = 0;
    public $totalCustomers = 0;
    public $recentOrders = [];
    public $chartLabels = [];
    public $chartValues = [];
    public $chartMax = 100;
    public $chartPath = '';
    public $chartAreaPath = '';
    public $chartPoints = [];

    public function mount()
    {
        $this->totalRevenue = Order::where('status', 'delivered')->sum('grand_total');
        $this->activeOrders = Order::whereIn('status', ['pending', 'processing', 'shipped'])->count();
        $this->lowStockAlerts = Product::where('stock', '<', 5)->count();
        $this->totalCustomers = User::where('is_admin', false)->count();
        $this->recentOrders = Order::with('user')
            ->latest()
            ->limit(5)
            ->get();

        $this->generateChart();
    }

    private function generateChart()
    {
        // Fetch delivered order sales for the last 6 months
        $sales = Order::select(
                DB::raw('SUM(grand_total) as total'),
                DB::raw("DATE_FORMAT(created_at, '%b') as month"),
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_year")
            )
            ->where('status', 'delivered')
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), DB::raw("DATE_FORMAT(created_at, '%b')"))
            ->orderBy('month_year', 'asc')
            ->get();

        $labels = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthLabel = $date->format('M');
            $yearMonth = $date->format('Y-m');

            $monthSale = $sales->firstWhere('month_year', $yearMonth);
            $labels[] = $monthLabel;
            $values[] = $monthSale ? (float) $monthSale->total : 0.0;
        }

        $this->chartLabels = $labels;
        $this->chartValues = $values;

        // Calculate SVG paths
        $width = 600;
        $height = 200;
        $padding = 20;
        $chartWidth = $width - ($padding * 2);
        $chartHeight = $height - ($padding * 2);

        $maxVal = max($values);
        if ($maxVal <= 0) {
            $maxVal = 100;
        }
        $this->chartMax = $maxVal;

        $points = [];
        $count = count($values);

        foreach ($values as $index => $val) {
            $x = $padding + ($index * ($chartWidth / ($count - 1)));
            // Invert Y coordinate since SVG (0,0) is top-left
            $y = $height - $padding - (($val / $maxVal) * $chartHeight);
            $points[] = ['x' => $x, 'y' => $y, 'val' => $val];
        }

        $this->chartPoints = $points;

        if (count($points) > 0) {
            // Build Line Path
            $linePath = "M {$points[0]['x']} {$points[0]['y']}";
            for ($i = 1; $i < count($points); $i++) {
                $linePath .= " L {$points[$i]['x']} {$points[$i]['y']}";
            }
            $this->chartPath = $linePath;

            // Build Area Path (closed polygon at bottom)
            $bottomY = $height - $padding;
            $areaPath = $linePath;
            $areaPath .= " L {$points[count($points)-1]['x']} {$bottomY}";
            $areaPath .= " L {$points[0]['x']} {$bottomY} Z";
            $this->chartAreaPath = $areaPath;
        }
    }
};
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Dashboard</h1>
            <p class="text-xs text-slate-500 mt-1">Real-time statistics and overview of your storefront.</p>
        </div>
        <div class="text-xs text-slate-400 font-semibold">
            Last updated: {{ now()->format('h:i A') }}
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Revenue Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:border-indigo-300 transition duration-300 shadow-sm">
            <div class="absolute top-0 right-0 h-16 w-16 bg-indigo-50 rounded-bl-full pointer-events-none group-hover:scale-110 transition duration-300"></div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Revenue</span>
            <div class="text-2xl font-black text-slate-900 mt-2">₹{{ number_format($totalRevenue) }}</div>
            <p class="text-[10px] text-emerald-700 font-bold mt-2 flex items-center gap-1">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                From delivered orders
            </p>
        </div>

        <!-- Active Orders Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:border-indigo-300 transition duration-300 shadow-sm">
            <div class="absolute top-0 right-0 h-16 w-16 bg-purple-50 rounded-bl-full pointer-events-none group-hover:scale-110 transition duration-300"></div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Orders</span>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $activeOrders }}</div>
            <p class="text-[10px] text-slate-500 font-medium mt-2">Pending/Processing/Shipped</p>
        </div>

        <!-- Low Stock Alerts Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:border-indigo-300 transition duration-300 shadow-sm">
            <div class="absolute top-0 right-0 h-16 w-16 bg-rose-50 rounded-bl-full pointer-events-none group-hover:scale-110 transition duration-300"></div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Low Stock Products</span>
            <div class="text-2xl font-black @if($lowStockAlerts > 0) text-rose-700 @else text-slate-900 @endif mt-2">{{ $lowStockAlerts }}</div>
            <p class="text-[10px] @if($lowStockAlerts > 0) text-rose-700 font-bold @else text-slate-500 @endif mt-2">
                @if($lowStockAlerts > 0) Needs immediate restock @else All clear @endif
            </p>
        </div>

        <!-- Customers Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden group hover:border-indigo-300 transition duration-300 shadow-sm">
            <div class="absolute top-0 right-0 h-16 w-16 bg-pink-50 rounded-bl-full pointer-events-none group-hover:scale-110 transition duration-300"></div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Customers</span>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $totalCustomers }}</div>
            <p class="text-[10px] text-slate-500 font-medium mt-2">Registered buyer accounts</p>
        </div>
    </div>

    <!-- Chart & Recent Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- SVG Chart -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
            <div>
                <h3 class="text-base font-bold text-slate-900">Revenue Performance</h3>
                <p class="text-[10px] text-slate-550">Delivered monthly sales timeline (last 6 months).</p>
            </div>
            
            <div class="relative pt-4">
                @if(max($chartValues) > 0)
                    <!-- Pure Responsive SVG Chart -->
                    <svg viewBox="0 0 600 200" class="w-full h-auto overflow-visible">
                        <defs>
                            <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6366f1" stop-opacity="0.3"/>
                                <stop offset="100%" stop-color="#6366f1" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>

                        <!-- Grid Lines -->
                        <line x1="20" y1="20" x2="580" y2="20" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>
                        <line x1="20" y1="100" x2="580" y2="100" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>
                        <line x1="20" y1="180" x2="580" y2="180" stroke="#cbd5e1" stroke-width="1"/>

                        <!-- Area Fill -->
                        @if($chartAreaPath)
                            <path d="{{ $chartAreaPath }}" fill="url(#chartGrad)"/>
                        @endif

                        <!-- Line Path -->
                        @if($chartPath)
                            <path d="{{ $chartPath }}" fill="none" stroke="#6366f1" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        @endif

                        <!-- Data Points -->
                        @foreach($chartPoints as $pt)
                            <g class="group/point cursor-pointer">
                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="5" fill="#ffffff" stroke="#4f46e5" stroke-width="2" class="hover:r-7 transition-all duration-150"/>
                                <text x="{{ $pt['x'] }}" y="{{ $pt['y'] - 12 }}" text-anchor="middle" fill="#4f46e5" font-size="10" font-weight="bold" class="opacity-0 group-hover/point:opacity-100 transition-opacity bg-white p-1 pointer-events-none">
                                    ₹{{ number_format($pt['val']) }}
                                </text>
                            </g>
                        @endforeach

                        <!-- X Axis Labels -->
                        @foreach($chartLabels as $idx => $label)
                            @php
                                $x = 20 + ($idx * ((600 - 40) / (count($chartLabels) - 1)));
                            @endphp
                            <text x="{{ $x }}" y="196" text-anchor="middle" fill="#475569" font-size="10" font-weight="bold">{{ $label }}</text>
                        @endforeach
                    </svg>
                @else
                    <div class="h-48 flex items-center justify-center border border-dashed border-slate-200 rounded-xl">
                        <p class="text-xs text-slate-400 font-medium">No sales records available yet.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 flex flex-col justify-between shadow-sm">
            <div>
                <h3 class="text-base font-bold text-slate-900">Recent Activity</h3>
                <p class="text-[10px] text-slate-550">Latest 5 storefront customer orders.</p>
            </div>

            <div class="flex-1 mt-4 space-y-4">
                @forelse($recentOrders as $order)
                    <a href="{{ route('admin.orders.detail', ['id' => $order->id]) }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-indigo-200 hover:shadow-sm transition duration-150">
                        <div class="space-y-1">
                            <div class="text-xs font-bold text-slate-800">#{{ $order->id }} - {{ $order->user->name }}</div>
                            <div class="text-[10px] text-slate-450 font-semibold">{{ $order->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="text-right space-y-1">
                            <div class="text-xs font-black text-slate-900">₹{{ number_format($order->grand_total) }}</div>
                            
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-50 border border-amber-200 text-amber-700',
                                    'processing' => 'bg-blue-50 border border-blue-200 text-blue-700',
                                    'shipped' => 'bg-indigo-50 border border-indigo-200 text-indigo-700',
                                    'delivered' => 'bg-emerald-50 border border-emerald-200 text-emerald-700',
                                    'cancelled' => 'bg-rose-50 border border-rose-200 text-rose-700',
                                ];
                                $color = $statusColors[$order->status] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <span class="inline-block px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase {{ $color }}">
                                {{ $order->status }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="h-full flex items-center justify-center text-center py-8">
                        <p class="text-xs text-slate-400 font-medium">No order activity recorded.</p>
                    </div>
                @endforelse
            </div>
            
            <a href="{{ route('admin.orders') }}" class="block w-full text-center rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition mt-4">
                View All Orders &rarr;
            </a>
        </div>
    </div>
</div>
