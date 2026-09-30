<?php

namespace Modules\Repair\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Repair\Entities\ServiceJob;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        // ── Date range ─────────────────────────────────────────────────────
        $starting_date = $request->input('starting_date', date('Y-m-01'));   // 1st of current month
        $ending_date   = $request->input('ending_date',   date('Y-m-d'));

        // ── Base query with access control ──────────────────────────────────
        $base = ServiceJob::whereDate('created_at', '>=', $starting_date)
                          ->whereDate('created_at', '<=', $ending_date);

        if (Auth::user()->role_id > 2) {
            if (config('staff_access') == 'own')
                $base->where('created_by', Auth::id());
            elseif (config('staff_access') == 'warehouse')
                $base->where('warehouse_id', Auth::user()->warehouse_id);
        }

        // ── KPI Cards ───────────────────────────────────────────────────────
        $total_jobs     = (clone $base)->count();
        $pending        = (clone $base)->where('status', 'pending')->count();
        $in_progress    = (clone $base)->where('status', 'in_progress')->count();
        $completed      = (clone $base)->where('status', 'completed')->count();
        $delivered      = (clone $base)->where('status', 'delivered')->count();
        $cancelled      = (clone $base)->where('status', 'cancelled')->count();
        $diagnosed      = (clone $base)->where('status', 'diagnosed')->count();

        $device_jobs    = (clone $base)->where('service_type', 'device')->count();
        $vehicle_jobs   = (clone $base)->where('service_type', 'vehicle')->count();

        // ── Revenue ────────────────────────────────────────────────────────
        $total_revenue  = (clone $base)->sum('total_amount');
        $total_collected= (clone $base)->sum('paid_amount');
        $total_due      = (clone $base)->sum('due_amount');

        // ── High priority jobs ─────────────────────────────────────────────
        $high_priority  = (clone $base)->where('priority', 'high')
                                       ->whereNotIn('status', ['completed','delivered','cancelled'])
                                       ->count();

        // ── Status breakdown for donut chart ───────────────────────────────
        $status_chart = (clone $base)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // ── Monthly trend (last 6 months) ───────────────────────────────────
        $monthly_trend = ServiceJob::select(
                DB::raw('YEAR(created_at) as yr'),
                DB::raw('MONTH(created_at) as mo'),
                DB::raw('count(*) as jobs'),
                DB::raw('sum(total_amount) as revenue'),
                DB::raw('sum(paid_amount) as collected')
            )
            ->whereDate('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->when(Auth::user()->role_id > 2 && config('staff_access') == 'own',
                fn($q) => $q->where('created_by', Auth::id()))
            ->groupBy('yr', 'mo')
            ->orderBy('yr')->orderBy('mo')
            ->get()
            ->map(function ($row) {
                $row->label    = date('M Y', mktime(0, 0, 0, $row->mo, 1, $row->yr));
                $row->revenue  = round($row->revenue,  2);
                $row->collected= round($row->collected, 2);
                return $row;
            });

        // ── Recent jobs (latest 8) ──────────────────────────────────────────
        $recent_jobs = ServiceJob::with('customer', 'warehouse', 'assignedTo')
            ->when(Auth::user()->role_id > 2 && config('staff_access') == 'own',
                fn($q) => $q->where('created_by', Auth::id()))
            ->latest()
            ->limit(8)
            ->get();

        // ── Top technicians (by completed jobs in range) ────────────────────
        $top_technicians = (clone $base)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereNotNull('assigned_to')
            ->select('assigned_to', DB::raw('count(*) as done'), DB::raw('sum(paid_amount) as earned'))
            ->groupBy('assigned_to')
            ->orderByDesc('done')
            ->limit(5)
            ->with('assignedTo')
            ->get();

        // ── Overdue (expected delivery passed, not completed) ──────────────
        $overdue = ServiceJob::whereNotNull('expected_delivery_date')
            ->whereDate('expected_delivery_date', '<', now())
            ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->when(Auth::user()->role_id > 2 && config('staff_access') == 'own',
                fn($q) => $q->where('created_by', Auth::id()))
            ->count();

        // ── Jobs created today ─────────────────────────────────────────────
        $today_jobs = ServiceJob::whereDate('created_at', today())
            ->when(Auth::user()->role_id > 2 && config('staff_access') == 'own',
                fn($q) => $q->where('created_by', Auth::id()))
            ->count();

        // ── Payment method breakdown ───────────────────────────────────────
        $payment_methods = Payment::whereNotNull('service_job_id')
            ->whereDate('created_at', '>=', $starting_date)
            ->whereDate('created_at', '<=', $ending_date)
            ->select('paying_method', DB::raw('count(*) as cnt'), DB::raw('sum(amount) as total'))
            ->groupBy('paying_method')
            ->orderByDesc('total')
            ->get();

        return view('repair::dashboard', compact(
            'starting_date', 'ending_date',
            'total_jobs', 'pending', 'in_progress', 'completed', 'delivered',
            'cancelled', 'diagnosed', 'device_jobs', 'vehicle_jobs',
            'total_revenue', 'total_collected', 'total_due',
            'high_priority', 'overdue', 'today_jobs',
            'status_chart', 'monthly_trend',
            'recent_jobs', 'top_technicians', 'payment_methods'
        ));
    }
}
