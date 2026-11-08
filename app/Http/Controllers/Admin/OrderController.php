<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderStatusService $statuses) {}

    public function index(Request $request): View
    {
        $q = Str::limit(trim((string) $request->query('q')), 100, '');
        $status = OrderStatus::tryFrom((string) $request->query('status'));
        $type = FulfillmentType::tryFrom((string) $request->query('type'));

        $orders = Order::query()
            ->withCount('items')
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->when($type, fn ($query) => $query->where('fulfillment_type', $type->value))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $digits = preg_replace('/\D+/', '', $q);

                if (str_starts_with($digits, '0')) {
                    $digits = '62'.substr($digits, 1);
                }

                $query->where(function ($w) use ($like, $digits) {
                    $w->where('order_number', 'ilike', $like)
                        ->orWhere('customer_name', 'ilike', $like);

                    if (strlen($digits) >= 5) {
                        $w->orWhere('whatsapp', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $counts = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.orders.index', [
            'orders' => $orders,
            'counts' => $counts,
            'filters' => ['q' => $q, 'status' => $status?->value, 'type' => $type?->value],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'histories.user']);

        return view('admin.orders.show', [
            'order' => $order,
            'next' => $this->statuses->nextStatus($order),
        ]);
    }

    public function advance(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'current_status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->statuses->advance($order->id, $request->user(), $data['current_status'], $data['note'] ?? null);

        return redirect()->route('admin.orders.show', $order)
            ->with('status', "Status diubah menjadi {$updated->status->label()}.");
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'current_status' => ['required', Rule::enum(OrderStatus::class)],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->statuses->cancel($order->id, $request->user(), $data['current_status'], trim($data['reason']));

        return redirect()->route('admin.orders.show', $order)
            ->with('status', 'Pesanan dibatalkan dan stok dikembalikan.');
    }

    public function deliveryNotes(Request $request, Order $order): RedirectResponse
    {
        if ($order->fulfillment_type !== FulfillmentType::Delivery) {
            throw ValidationException::withMessages([
                'delivery_notes' => 'Catatan pengantaran hanya untuk pesanan antar.',
            ]);
        }

        $data = $request->validate([
            'delivery_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order->update(['delivery_notes' => $data['delivery_notes'] ?: null]);

        return redirect()->route('admin.orders.show', $order)->with('status', 'Catatan pengantaran disimpan.');
    }
}
