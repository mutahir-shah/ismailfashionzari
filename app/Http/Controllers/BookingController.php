<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\MailSetting;
use App\Mail\SaleDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class BookingController extends Controller
{
    // ── Index (Calendar + List tabs) ─────────────────────────────
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if (! $role->hasPermissionTo('booking')) {
            return redirect()->route('booking.index')->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $lims_warehouse_list   = Warehouse::where('is_active', true)->get();
        $lims_customer_list    = Customer::where('is_active', true)->get();
        $lims_user_list        = User::where('is_active', true)->get();
        $lims_service_products = Product::where('is_active', true)
                                         ->where('type', 'service')
                                         ->select('id', 'name', 'price')
                                         ->get();
        $general_setting = cache()->get('general_setting') ?? \App\Models\GeneralSetting::latest()->first();

        // ── List tab data ─────────────────────────────────────────
        $warehouse_id  = $request->warehouse_id ?? 0;
        $status        = $request->status ?? 0;
        $starting_date = $request->starting_date ?? date('Y-m-d', strtotime('-1 year'));
        $ending_date   = $request->ending_date ?? date('Y-m-d');

        $query = Booking::with(['warehouse', 'customer', 'employee'])
            ->whereNull('deleted_at');

        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $query->where('created_by', Auth::id());
        } elseif (Auth::user()->role_id > 2 && config('staff_access') == 'warehouse') {
            $query->where('warehouse_id', Auth::user()->warehouse_id);
        }

        if ($warehouse_id) {
            $query->where('warehouse_id', $warehouse_id);
        }

        if ($status && $status != 0) {
            $query->where('status', $status);
        }

        $query->whereDate('start_date', '>=', $starting_date)
              ->whereDate('start_date', '<=', $ending_date);

        $lims_booking_all = $query->orderByDesc('start_date')->get();

        return view('backend.booking.index', compact(
            'lims_warehouse_list',
            'lims_customer_list',
            'lims_user_list',
            'lims_service_products',
            'general_setting',
            'lims_booking_all',
            'warehouse_id',
            'status',
            'starting_date',
            'ending_date'
        ));
    }

    // ── Calendar Events (AJAX for FullCalendar) ───────────────────
    public function getEvents(Request $request)
    {
        $query = Booking::with(['warehouse', 'customer', 'employee'])
            ->whereNull('deleted_at');

        // Access control
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $query->where('created_by', Auth::id());
        } elseif (Auth::user()->role_id > 2 && config('staff_access') == 'warehouse') {
            $query->where('warehouse_id', Auth::user()->warehouse_id);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // FullCalendar date range
        if ($request->filled('start')) {
            $query->where('start_date', '>=', $request->start);
        }
        if ($request->filled('end')) {
            $query->where('end_date', '<=', $request->end);
        }

        $bookings = $query->get();

        $events = $bookings->map(function ($booking) {
            return [
                'id'              => $booking->id,
                'title'           => ($booking->customer->name ?? 'Customer') . ' ' .
                                     ($booking->customer->phone_number ? '+' . $booking->customer->phone_number : ''),
                'start'           => $booking->start_date->toDateTimeString(),
                'end'             => $booking->end_date->toDateTimeString(),
                'backgroundColor' => $booking->calendar_color,
                'borderColor'     => $booking->calendar_color,
                'textColor'       => '#fff',
                'extendedProps'   => [
                    'status'        => $booking->status,
                    'warehouse'     => $booking->warehouse->name ?? '',
                    'customer'      => $booking->customer->name ?? '',
                    'customer_phone'=> $booking->customer->phone_number ?? '',
                    'employee'      => $booking->employee->name ?? '',
                    'note'          => $booking->note,
                    'warehouse_id'  => $booking->warehouse_id,
                    'customer_id'   => $booking->customer_id,
                    'user_id'       => $booking->user_id,
                    'product_id'    => $booking->product_id,
                    'price'         => $booking->price,
                ],
            ];
        });

        return response()->json($events);
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_id'  => 'required|exists:customers,id',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|in:Booked,Waiting,Completed,Cancelled',
        ]);

        $booking = Booking::create([
            'warehouse_id' => $request->warehouse_id,
            'customer_id'  => $request->customer_id,
            'user_id'      => $request->user_id ?: null,
            'created_by'   => Auth::id(),
            'product_id'   => $request->product_id ?: null,
            'price'        => $request->price ?: 0,
            'status'       => $request->status,
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
            'note'         => $request->note,
        ]);
        // Send email if requested
        if ($request->send_email) {
            $this->sendBookingEmail($booking);
        }
         if ($request->ajax()) {
           return response()->json([
            'success' => true,
            'booking' => $booking->load(['warehouse', 'customer', 'employee']),
            'event'   => [
                'id'              => $booking->id,
                'title'           => ($booking->customer->name ?? '') . ' ' . ($booking->customer->phone_number ?? ''),
                'start'           => $booking->start_date->toDateTimeString(),
                'end'             => $booking->end_date->toDateTimeString(),
                'backgroundColor' => $booking->calendar_color,
                'borderColor'     => $booking->calendar_color,
                'textColor'       => '#fff',
                'extendedProps'   => [
                    'status'       => $booking->status,
                    'warehouse'    => $booking->warehouse->name ?? '',
                    'customer'     => $booking->customer->name ?? '',
                    'employee'     => $booking->employee->name ?? '',
                    'note'         => $booking->note,
                    'warehouse_id' => $booking->warehouse_id,
                    'customer_id'  => $booking->customer_id,
                    'user_id'      => $booking->user_id,
                ],
            ],
        ]);
        }else{
            return redirect()->route('booking.index')->with('message','Successfully Booked');
        }

    }

    // ── Show single booking ───────────────────────────────────────
    public function show($id)
    {
        $booking = Booking::with(['warehouse', 'customer', 'employee'])->findOrFail($id);
        return response()->json($booking);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_id'  => 'required|exists:customers,id',
            'status'       => 'required|in:Booked,Completed,Cancelled,Waiting',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update([
            'warehouse_id' => $request->warehouse_id,
            'customer_id'  => $request->customer_id,
            'user_id'      => $request->user_id ?: null,
            'product_id'   => $request->product_id ?: null,
            'price'        => $request->price ?: 0,
            'status'       => $request->status,
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
            'note'         => $request->note,
        ]);

        $booking->load(['warehouse', 'customer', 'employee']);

        if ($request->send_email) {
            $this->sendBookingEmail($booking);
        }
        if($request->ajax()){
            return response()->json([
            'success' => true,
            'booking' => $booking,
            'event'   => [
                'id'              => $booking->id,
                'title'           => ($booking->customer->name ?? '') . ' ' . ($booking->customer->phone_number ?? ''),
                'start'           => $booking->start_date->toDateTimeString(),
                'end'             => $booking->end_date->toDateTimeString(),
                'backgroundColor' => $booking->calendar_color,
                'borderColor'     => $booking->calendar_color,
                'textColor'       => '#fff',
                'extendedProps'   => [
                    'status'       => $booking->status,
                    'warehouse'    => $booking->warehouse->name ?? '',
                    'customer'     => $booking->customer->name ?? '',
                    'employee'     => $booking->employee->name ?? '',
                    'note'         => $booking->note,
                    'warehouse_id' => $booking->warehouse_id,
                    'customer_id'  => $booking->customer_id,
                    'user_id'      => $booking->user_id,
                ],
            ],
        ]);
        }else{
            return redirect()->route('booking.index')->with('message','Successfully booking update');
        }

    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['success' => true]);
    }

    // ── All Bookings list (DataTable page) ────────────────────────
    public function list(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if (! $role->hasPermissionTo('booking')) {
            return redirect()->route('booking.index')->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $lims_warehouse_list = Warehouse::where('is_active', true)->get();

        $query = Booking::with(['warehouse', 'customer', 'employee'])
            ->whereNull('deleted_at');

        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $query->where('created_by', Auth::id());
        } elseif (Auth::user()->role_id > 2 && config('staff_access') == 'warehouse') {
            $query->where('warehouse_id', Auth::user()->warehouse_id);
        }

        if ($request->filled('warehouse_id') && $request->warehouse_id != 0) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('status') && $request->status != 0) {
            $query->where('status', $request->status);
        }

        if ($request->filled('starting_date')) {
            $query->whereDate('start_date', '>=', $request->starting_date);
        }

        if ($request->filled('ending_date')) {
            $query->whereDate('start_date', '<=', $request->ending_date);
        }

        $lims_booking_all = $query->orderByDesc('start_date')->get();

        $warehouse_id  = $request->warehouse_id ?? 0;
        $status        = $request->status ?? 0;
        $starting_date = $request->starting_date ?? date('Y-m-d', strtotime('-1 year'));
        $ending_date   = $request->ending_date ?? date('Y-m-d');

        return view('backend.booking.list', compact(
            'lims_booking_all',
            'lims_warehouse_list',
            'warehouse_id',
            'status',
            'starting_date',
            'ending_date'
        ));
    }

    // ── Send booking email helper ─────────────────────────────────
    private function sendBookingEmail(Booking $booking)
    {
        try {
            $mail_setting = MailSetting::latest()->first();
            if ($mail_setting && $booking->customer && $booking->customer->email) {
                // Simple mail — customize as needed
                // Mail::to($booking->customer->email)->send(new BookingConfirmation($booking));
            }
        } catch (\Exception $e) {
            // silently fail
        }
    }
}
