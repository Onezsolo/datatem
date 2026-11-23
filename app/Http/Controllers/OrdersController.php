<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrdersController extends Controller
{
    /**
     * Order history: today, yesterday, all (with search). Complaints if table exists.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;
        $search = trim((string) $request->query('search', ''));
        $searchTerm = $search === '' ? '%' : '%' . $search . '%';

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $baseQuery = fn ($date = null) => DB::table('purchases')
            ->where('user_id', $userId)
            ->when($date !== null, fn ($q) => $q->whereDate('created_at', $date))
            ->when($search !== '', fn ($q) => $q->where('beneficiary', 'like', $searchTerm))
            ->orderByDesc('created_at');

        $todayOrders = $baseQuery($today)->get(['id', 'network', 'data_plan', 'beneficiary', 'amount', 'status', 'reference', 'created_at']);
        $yesterdayOrders = $baseQuery($yesterday)->get(['id', 'network', 'data_plan', 'beneficiary', 'amount', 'status', 'reference', 'created_at']);
        $allOrders = $baseQuery()->get(['id', 'network', 'data_plan', 'beneficiary', 'amount', 'status', 'reference', 'created_at']);

        $complaints = [];
        if (\Schema::hasTable('order_complaints')) {
            $complaints = DB::table('order_complaints')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->get(['id', 'ticket', 'reference', 'network', 'amount', 'note', 'status', 'created_at']);
        }

        $mapOrder = fn ($o) => [
            'id' => $o->id,
            'network' => $o->network,
            'data_plan' => $o->data_plan,
            'beneficiary' => $o->beneficiary,
            'amount' => (float) $o->amount,
            'status' => $o->status,
            'reference' => $o->reference,
            'created_at' => $o->created_at,
        ];

        $mapComplaint = fn ($c) => [
            'id' => $c->id,
            'ticket' => $c->ticket,
            'reference' => $c->reference,
            'network' => $c->network,
            'amount' => (float) $c->amount,
            'note' => $c->note,
            'status' => $c->status,
            'created_at' => $c->created_at,
        ];

        $todayOrdersData = $todayOrders->map($mapOrder)->values()->all();
        $yesterdayOrdersData = $yesterdayOrders->map($mapOrder)->values()->all();
        $allOrdersData = $allOrders->map($mapOrder)->values()->all();
        $complaintsData = collect($complaints)->map($mapComplaint)->values()->all();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'search' => $search,
                'today_orders' => $todayOrdersData,
                'yesterday_orders' => $yesterdayOrdersData,
                'all_orders' => $allOrdersData,
                'complaints' => $complaintsData,
            ]);
        }

        return Inertia::render('Orders/Index', [
            'search' => $search,
            'todayOrders' => $todayOrdersData,
            'yesterdayOrders' => $yesterdayOrdersData,
            'allOrders' => $allOrdersData,
            'complaints' => $complaintsData,
        ]);
    }

    /**
     * Store a complaint for an order (purchase).
     */
    public function storeComplaint(Request $request): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $request->validate([
            'purchase_id' => 'required|integer|exists:purchases,id',
            'note' => 'required|string|max:2000',
        ]);

        $user = $request->user();
        $purchase = DB::table('purchases')
            ->where('id', $request->purchase_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$purchase) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Order not found.', 'errors' => ['purchase_id' => ['Order not found.']]], 422);
            }
            return back()->withErrors(['purchase_id' => 'Order not found.']);
        }

        if (!\Schema::hasTable('order_complaints')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Complaints are not available yet.', 'errors' => ['complaint' => ['Complaints are not available yet.']]], 422);
            }
            return back()->withErrors(['complaint' => 'Complaints are not available yet.']);
        }

        $ticket = 'TKT-' . strtoupper(Str::random(8));

        DB::table('order_complaints')->insert([
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'ticket' => $ticket,
            'reference' => $purchase->reference,
            'network' => $purchase->network,
            'data_plan' => $purchase->data_plan,
            'beneficiary' => $purchase->beneficiary,
            'amount' => $purchase->amount,
            'note' => $request->note,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        \App\Services\NotifyService::admins(
            'New complaint',
            $user->name . ' submitted a complaint. Ticket: ' . $ticket . '.',
            'warning',
            url()->route('admin.complaints.index')
        );
        \App\Services\NotifyService::notifyUserComplaintReceived($user, $ticket);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Complaint submitted. Ticket: ' . $ticket,
                'ticket' => $ticket,
            ], 201);
        }

        return redirect()->route('orders.index')->with('success', 'Complaint submitted. Ticket: ' . $ticket);
    }
}
