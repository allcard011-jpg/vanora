<?php

namespace App\Services;

use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const GROUP_DAY = 'day';
    public const GROUP_MONTH = 'month';

    /**
     * Laporan pesanan SELESAI pada rentang tanggal [from, to] (inklusif), menurut
     * tanggal pesanan diselesaikan dalam zona waktu aplikasi (WITA).
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, int>, top: array<int, array<string, mixed>>}
     */
    public function build(CarbonImmutable $from, CarbonImmutable $to, string $group): array
    {
        $isMonth = $group === self::GROUP_MONTH;
        $format = $isMonth ? 'YYYY-MM' : 'YYYY-MM-DD'; // konstanta internal, bukan input pengguna
        $periodSql = "to_char(c.completed_at, '{$format}')";

        $start = $from->startOfDay()->format('Y-m-d H:i:s');
        $end = $to->addDay()->startOfDay()->format('Y-m-d H:i:s'); // batas atas eksklusif

        $orders = DB::table('orders as o')
            ->joinSub($this->completions(), 'c', 'c.order_id', '=', 'o.id')
            ->where('o.status', OrderStatus::Completed->value)
            ->where('c.completed_at', '>=', $start)
            ->where('c.completed_at', '<', $end)
            ->selectRaw("{$periodSql} as period, count(*) as orders, sum(o.subtotal) as subtotal, sum(o.shipping_fee) as shipping, sum(o.total) as revenue")
            ->groupByRaw($periodSql)
            ->orderBy('period')
            ->get();

        // Item dihitung di query terpisah agar baris pesanan tidak berlipat ganda oleh join.
        $units = $this->completedItems($start, $end)
            ->selectRaw("{$periodSql} as period, sum(i.quantity) as units")
            ->groupByRaw($periodSql)
            ->get()
            ->pluck('units', 'period');

        $rows = $orders->map(function ($row) use ($units, $isMonth) {
            $date = CarbonImmutable::createFromFormat($isMonth ? '!Y-m' : '!Y-m-d', $row->period);

            return [
                'period' => $row->period,
                'label' => $date->translatedFormat($isMonth ? 'F Y' : 'l, d F Y'),
                'orders' => (int) $row->orders,
                'units' => (int) ($units[$row->period] ?? 0),
                'subtotal' => (int) $row->subtotal,
                'shipping' => (int) $row->shipping,
                'revenue' => (int) $row->revenue,
            ];
        })->all();

        $rowsCollection = collect($rows);
        $orderCount = (int) $rowsCollection->sum('orders');
        $revenue = (int) $rowsCollection->sum('revenue');

        $totals = [
            'orders' => $orderCount,
            'units' => (int) $rowsCollection->sum('units'),
            'subtotal' => (int) $rowsCollection->sum('subtotal'),
            'shipping' => (int) $rowsCollection->sum('shipping'),
            'revenue' => $revenue,
            'average' => $orderCount > 0 ? intdiv($revenue, $orderCount) : 0,
        ];

        $top = $this->completedItems($start, $end)
            ->selectRaw('i.brand_name, i.product_name, sum(i.quantity) as units, sum(i.line_total) as revenue')
            ->groupBy('i.brand_name', 'i.product_name')
            ->orderByDesc('units')
            ->orderByDesc('revenue')
            ->orderBy('i.product_name')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'brand_name' => $row->brand_name,
                'product_name' => $row->product_name,
                'units' => (int) $row->units,
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        return ['rows' => $rows, 'totals' => $totals, 'top' => $top];
    }

    /** Saat pertama kali tiap pesanan berstatus Selesai (satu baris per pesanan). */
    private function completions(): Builder
    {
        return DB::table('order_status_histories')
            ->where('status', OrderStatus::Completed->value)
            ->selectRaw('order_id, min(created_at) as completed_at')
            ->groupBy('order_id');
    }

    private function completedItems(string $start, string $end): Builder
    {
        return DB::table('order_items as i')
            ->join('orders as o', 'o.id', '=', 'i.order_id')
            ->joinSub($this->completions(), 'c', 'c.order_id', '=', 'o.id')
            ->where('o.status', OrderStatus::Completed->value)
            ->where('c.completed_at', '>=', $start)
            ->where('c.completed_at', '<', $end);
    }
}
