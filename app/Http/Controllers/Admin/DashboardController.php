<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $completed = OrderStatus::Completed->value;

        $stats = [
            'products' => Product::count(),
            'orders' => Order::count(),
            'pending' => Order::where('status', OrderStatus::Pending->value)->count(),
            'completed' => Order::where('status', $completed)->count(),
            'revenue' => (int) Order::where('status', $completed)->sum('total'),
        ];

        // Omzet dicatat pada tanggal pesanan SELESAI (bukan tanggal dibuat).
        $today = now()->startOfDay();
        $from = $today->copy()->subDays(6);

        $daily = DB::table('order_status_histories as h')
            ->join('orders as o', 'o.id', '=', 'h.order_id')
            ->where('h.status', $completed)
            ->where('o.status', $completed)
            ->where('h.created_at', '>=', $from)
            ->selectRaw('DATE(h.created_at) as day, COUNT(*) as orders, SUM(o.total) as revenue')
            ->groupBy(DB::raw('DATE(h.created_at)'))
            ->get()
            ->keyBy('day');

        $week = collect(range(6, 0))->map(function (int $i) use ($today, $daily) {
            $date = $today->copy()->subDays($i);
            $row = $daily->get($date->toDateString());

            return [
                'label' => $date->translatedFormat('D, d M'),
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => (int) ($row->revenue ?? 0),
            ];
        });

        $maxRevenue = max(1, (int) $week->max('revenue'));
        $recentOrders = Order::latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'week', 'maxRevenue', 'recentOrders'));
    }
}
