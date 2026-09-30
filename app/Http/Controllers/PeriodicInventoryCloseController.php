<?php

namespace App\Http\Controllers;

use App\Models\PeriodicInventoryClose;
use App\Services\PeriodicInventoryCloseService;
use Illuminate\Http\Request;

class PeriodicInventoryCloseController extends Controller
{
    public function __construct(private PeriodicInventoryCloseService $service) {}

    public function index(Request $request)
    {
        $start = $request->input('period_start', now()->startOfMonth()->toDateString());
        $end = $request->input('period_end', now()->toDateString());
        $preview = $this->service->preview($start, $end);
        $history = PeriodicInventoryClose::latest()->limit(20)->get();
        return view('backend.accounting.periodic_inventory_close', compact('preview', 'history'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['period_start' => 'required|date', 'period_end' => 'required|date|after_or_equal:period_start']);
        try {
            $this->service->post($data['period_start'], $data['period_end'], auth()->id());
            return back()->with('message', __('db.inventory_close_posted'));
        } catch (\Throwable $e) {
            return back()->with('not_permitted', __('db.inventory_close_failed'));
        }
    }

    public function reverse(PeriodicInventoryClose $close)
    {
        try {
            $this->service->reverse($close);
            return back()->with('message', __('db.inventory_close_reversed'));
        } catch (\Throwable $e) {
            return back()->with('not_permitted', __('db.inventory_close_failed'));
        }
    }

    public function recalculate(PeriodicInventoryClose $close)
    {
        try {
            $this->service->recalculate($close, auth()->id());
            return back()->with('message', __('db.inventory_close_recalculated'));
        } catch (\Throwable $e) {
            return back()->with('not_permitted', __('db.inventory_close_failed'));
        }
    }
}
