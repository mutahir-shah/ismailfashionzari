@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section>
    <div class="container-fluid">

        <div class="booking-page-header d-flex align-items-center justify-content-between mb-0">
            <div>
                <h4 class="mb-0 font-weight-bold" style="color:#566a7f;">
                    <i class="dripicons-calendar mr-2" style="color:#696cff;"></i>{{ __('db.Booking') }}
                </h4>
                <small class="text-muted">{{ __('db.manage_bookings_and_appointments') }}</small>
            </div>

            <div class="d-flex align-items-center" style="gap:10px;">
                {{-- View switcher --}}
                <div class="view-switcher-group" role="group">
                    <a class="view-btn active" id="calendar-tab" data-toggle="tab" href="#tab-calendar" role="tab">
                        <i class="dripicons-calendar"></i> {{ __('db.Calendar View') }}
                    </a>
                    <a class="view-btn" id="list-tab" data-toggle="tab" href="#tab-list" role="tab">
                        <i class="dripicons-list"></i> {{ __('db.List View') }}
                    </a>
                </div>

                {{-- Add booking --}}
                <button class="btn btn-primary px-4" data-toggle="modal" data-target="#booking-modal" id="addBookingBtnHeader">
                    <i class="dripicons-plus mr-1"></i> {{ __('db.Add Booking') }}
                </button>
            </div>
        </div>

        <div class="tab-content">

            {{-- TAB 1: CALENDAR --}}
            <div class="tab-pane fade show active" id="tab-calendar" role="tabpanel">
                <div class="booking-calendar-wrapper">
                    <div class="row no-gutters">

                        {{-- Sidebar --}}
                        <div class="col-auto booking-sidebar">

                            {{-- Mini Stats --}}
                            <div class="sidebar-stats-grid p-3 border-bottom">
                                <div class="stat-chip stat-booked">
                                    <span class="stat-dot"></span>
                                    <div>
                                        <div class="stat-label">{{ __('db.Booked') }}</div>
                                        <div class="stat-count" id="stat-count-booked">—</div>
                                    </div>
                                </div>
                                <div class="stat-chip stat-waiting">
                                    <span class="stat-dot"></span>
                                    <div>
                                        <div class="stat-label">{{ __('db.Waiting') }}</div>
                                        <div class="stat-count" id="stat-count-waiting">—</div>
                                    </div>
                                </div>
                                <div class="stat-chip stat-completed">
                                    <span class="stat-dot"></span>
                                    <div>
                                        <div class="stat-label">{{ __('db.Completed') }}</div>
                                        <div class="stat-count" id="stat-count-completed">—</div>
                                    </div>
                                </div>
                                <div class="stat-chip stat-cancelled">
                                    <span class="stat-dot"></span>
                                    <div>
                                        <div class="stat-label">{{ __('db.Cancelled') }}</div>
                                        <div class="stat-count" id="stat-count-cancelled">—</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Filters --}}
                            <div class="p-3">
                                <p class="sidebar-section-label">{{ __("db.filters") }}</p>

                                @if(Auth::user()->role_id <= 2)
                                <div class="mb-3">
                                    <label class="sidebar-field-label"><i class="dripicons-store mr-1"></i>{{ __('db.Warehouse') }}</label>
                                    <select id="warehouseFilter" class="selectpicker form-control form-control-sm"
                                            data-live-search="true" title="{{ __('db.All Warehouse') }}">
                                        <option value="">{{ __('db.All Warehouse') }}</option>
                                        @foreach($lims_warehouse_list as $wh)
                                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <p class="sidebar-section-label mt-2">{{ __('db.Status') }}</p>

                                <div class="filter-pill" onclick="toggleFilter('Booked')">
                                    <span class="filter-pill-dot" style="background:#696cff;"></span>
                                    <span class="filter-pill-label">{{ __('db.Booked') }}</span>
                                    <input type="checkbox" class="cal-filter d-none" id="filter-booked" data-value="Booked" checked>
                                    <i class="dripicons-checkmark filter-pill-check filter-icon-booked" style="color:#696cff;"></i>
                                </div>
                                <div class="filter-pill" onclick="toggleFilter('Waiting')">
                                    <span class="filter-pill-dot" style="background:#ffab00;"></span>
                                    <span class="filter-pill-label">{{ __('db.Waiting') }}</span>
                                    <input type="checkbox" class="cal-filter d-none" id="filter-waiting" data-value="Waiting" checked>
                                    <i class="dripicons-checkmark filter-pill-check filter-icon-waiting" style="color:#ffab00;"></i>
                                </div>
                                <div class="filter-pill" onclick="toggleFilter('Completed')">
                                    <span class="filter-pill-dot" style="background:#28c76f;"></span>
                                    <span class="filter-pill-label">{{ __('db.Completed') }}</span>
                                    <input type="checkbox" class="cal-filter d-none" id="filter-completed" data-value="Completed" checked>
                                    <i class="dripicons-checkmark filter-pill-check filter-icon-completed" style="color:#28c76f;"></i>
                                </div>
                                <div class="filter-pill" onclick="toggleFilter('Cancelled')">
                                    <span class="filter-pill-dot" style="background:#ea5455;"></span>
                                    <span class="filter-pill-label">{{ __('db.Cancelled') }}</span>
                                    <input type="checkbox" class="cal-filter d-none" id="filter-cancelled" data-value="Cancelled" checked>
                                    <i class="dripicons-checkmark filter-pill-check filter-icon-cancelled" style="color:#ea5455;"></i>
                                </div>

                                <hr class="my-3">
                                <div class="sidebar-today-box">
                                    <div class="sidebar-today-date" id="sidebar-today-date"></div>
                                    <div class="sidebar-today-label">{{ __('db.Today') }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Calendar Area --}}
                        <div class="col position-relative">
                            {{-- Loader overlay --}}
                            <div id="calendarLoader" class="calendar-loader-overlay">
                                <div class="text-center">
                                    <div class="calendar-loader-spinner">
                                        <div class="spinner-inner"></div>
                                    </div>
                                    <p class="mt-3 text-muted" style="font-size:13px;font-weight:500;">Loading calendar...</p>
                                </div>
                            </div>
                            <div id="calendar" class="p-3"></div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- TAB 2: LIST --}}
            <div class="tab-pane fade" id="tab-list" role="tabpanel">
                <div class="card border-top-0">
                    <div class="card-header mt-2">
                        <h3 class="text-center">{{ __('db.Booking List') }}</h3>
                    </div>
                    <form method="GET" action="{{ route('booking.index') }}" id="listFilterForm">
                        <input type="hidden" name="tab" value="list">
                        <div class="row mb-3 px-3">
                            <div class="col-md-4 mt-3">
                                <div class="d-flex align-items-center">
                                    <label class="mb-0 mr-2 text-nowrap">{{ __('db.date') }}</label>
                                    <div class="input-group">
                                        <input type="text" class="daterangepicker-field form-control" value="{{ $starting_date }} To {{ $ending_date }}" />
                                        <input type="hidden" name="starting_date" value="{{ $starting_date }}" />
                                        <input type="hidden" name="ending_date" value="{{ $ending_date }}" />
                                    </div>
                                </div>
                            </div>
                            @if(Auth::user()->role_id <= 2)
                            <div class="col-md-3 mt-3">
                                <div class="d-flex align-items-center">
                                    <label class="mb-0 mr-2 text-nowrap">{{ __('db.Warehouse') }}</label>
                                    <select name="warehouse_id" class="selectpicker form-control" data-live-search="true">
                                        <option value="0">{{ __('db.All') }}</option>
                                        @foreach($lims_warehouse_list as $wh)
                                            <option value="{{ $wh->id }}" @if($wh->id == $warehouse_id) selected @endif>{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-2 mt-3">
                                <div class="d-flex align-items-center">
                                    <label class="mb-0 mr-2">{{ __('db.Status') }}</label>
                                    <select name="status" class="selectpicker form-control">
                                        <option value="0">{{ __('db.All') }}</option>
                                        <option value="Booked"    @if($status=='Booked')    selected @endif>{{ __('db.Booked') }}</option>
                                        <option value="Waiting"   @if($status=='Waiting')   selected @endif>{{ __('db.Waiting') }}</option>
                                        <option value="Completed" @if($status=='Completed') selected @endif>{{ __('db.Completed') }}</option>
                                        <option value="Cancelled" @if($status=='Cancelled') selected @endif>{{ __('db.Cancelled') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-1 mt-3">
                                <button class="btn btn-primary btn-sm" type="submit">{{ __('db.submit') }}</button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table id="booking-table" class="table booking-list">
                            <thead>
                                <tr>
                                    <th class="not-exported"></th>
                                    <th>{{ __('db.date') }}</th>
                                    <th>{{ __('db.Warehouse') }}</th>
                                    <th>{{ __('db.customer') }}</th>
                                    <th>{{ __('db.Employee') }}</th>
                                    <th>Product / Service</th>
                                    <th>Price</th>
                                    <th>{{ __('db.start') }}</th>
                                    <th>{{ __('db.end') }}</th>
                                    <th>{{ __('db.Status') }}</th>
                                    <th>{{ __('db.note') }}</th>
                                    <th class="not-exported">{{ __('db.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lims_booking_all as $key => $booking)
                                <tr data-id="{{ $booking->id }}">
                                    <td>{{ $key }}</td>
                                    <td>{{ date('d-m-Y', strtotime($booking->created_at->toDateString())) }}</td>
                                    <td>{{ $booking->warehouse->name ?? '—' }}</td>
                                    <td>{{ $booking->customer->name ?? '—' }}<br><small class="text-muted">{{ $booking->customer->phone_number ?? '' }}</small></td>
                                    <td>{{ $booking->employee->name ?? '—' }}</td>
                                    <td>{{ $booking->product->name ?? '—' }}</td>
                                    <td>{{ $booking->price ?? '—' }}</td>
                                    <td>{{ date('d-m-Y H:i', strtotime($booking->start_date)) }}</td>
                                    <td>{{ date('d-m-Y H:i', strtotime($booking->end_date)) }}</td>
                                    <td>
                                        @if($booking->status == 'Booked') <span class="badge badge-primary">{{ __('db.Booked') }}</span>
                                        @elseif($booking->status == 'Waiting') <span class="badge badge-warning">{{ __('db.Waiting') }}</span>
                                        @elseif($booking->status == 'Completed') <span class="badge badge-success">{{ __('db.Completed') }}</span>
                                        @elseif($booking->status == 'Cancelled') <span class="badge badge-danger">{{ __('db.Cancelled') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $booking->note }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                {{ __('db.action') }}<span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default">
                                                <li>
                                                    <button type="button" class="btn btn-link edit-booking-btn" data-id="{{ $booking->id }}">
                                                        <i class="dripicons-document-edit"></i> {{ __('db.edit') }}
                                                    </button>
                                                </li>
                                                <li class="divider"></li>
                                                <form action="{{ route('bookings.destroy', $booking->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <li>
                                                        <button type="submit" class="btn btn-link" onclick="return confirmDelete()">
                                                            <i class="dripicons-trash"></i> {{ __('db.delete') }}
                                                        </button>
                                                    </li>
                                                </form>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- Booking Modal --}}
<div id="booking-modal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="bookingModalLabel" class="modal-title">{{ __("db.Add Booking") }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                    <span aria-hidden="true"><i class="ti ti-x"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>
                <form id="bookingForm" method="POST" action="{{ route('bookings.store') }}">
                    @csrf
                    <input type="hidden" id="formMethod" name="_method" value="POST">
                    <div class="row">

                        {{-- Warehouse --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Warehouse') }} *</label>
                            <select name="warehouse_id" id="b_warehouse_id" class="selectpicker form-control" required
                                    data-live-search="true" title="Select Warehouse...">
                                @foreach($lims_warehouse_list as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Customer --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.customer') }} *</label>
                            <select name="customer_id" id="b_customer_id" class="selectpicker form-control" required
                                    data-live-search="true" title="Select Customer...">
                                @foreach($lims_customer_list as $customer)
                                    <option value="{{ $customer->id }}" data-phone="{{ $customer->phone_number }}">
                                        {{ $customer->name }} ({{ $customer->phone_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Employee --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Employee') }}</label>
                            <select name="user_id" id="b_user_id" class="selectpicker form-control"
                                    data-live-search="true" title="Select Employee...">
                                <option value="">— No Employee —</option>
                                @foreach($lims_user_list as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} @if($user->phone)({{ $user->phone }})@endif</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.status') }} *</label>
                            <select name="status" id="b_status" class="selectpicker form-control" required>
                                <option value="Booked" selected>{{ __('db.Booked') }}</option>
                                <option value="Waiting">{{ __('db.Waiting') }}</option>
                                <option value="Completed">{{ __('db.Completed') }}</option>
                                <option value="Cancelled">{{ __('db.Cancelled') }}</option>
                            </select>
                        </div>

                        {{-- Product (service type only) --}}
                        <div class="col-md-6 form-group">
                            <label>Product / Service</label>
                            <select name="product_id" id="b_product_id" class="selectpicker form-control"
                                    data-live-search="true" title="Select Service...">
                                <option value="">— No Product —</option>
                                @foreach($lims_service_products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Price (auto-fill from product) --}}
                        <div class="col-md-6 form-group">
                            <label>Price</label>
                            <input type="number" name="price" id="b_price"
                                   class="form-control" placeholder="0.00" step="any" min="0">
                        </div>

                        {{-- Start Date & Time --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Start Date') }} *</label>
                            <div class="d-flex">
                                <input type="text" id="visible_start_date" class="form-control" style="width: 55%; border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;" required>
                                <input type="time" id="visible_start_time" class="form-control" style="width: 45%; border-top-left-radius: 0; border-bottom-left-radius: 0;" required>
                            </div>
                            <input type="hidden" name="start_date" id="b_start_date">
                        </div>

                        {{-- End Date & Time --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.End Date') }} *</label>
                            <div class="d-flex">
                                <input type="text" id="visible_end_date" class="form-control" style="width: 55%; border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;" required>
                                <input type="time" id="visible_end_time" class="form-control" style="width: 45%; border-top-left-radius: 0; border-bottom-left-radius: 0;" required>
                            </div>
                            <input type="hidden" name="end_date" id="b_end_date">
                        </div>

                    </div>

                    <div class="form-group">
                        <label>{{ __('db.note') }}</label>
                        <textarea name="note" id="b_note" rows="3" class="form-control" placeholder="Enter note..."></textarea>
                    </div>
                    {{-- <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="b_send_email" name="send_email" value="1">
                            <label class="form-check-label" for="b_send_email">{{ __('db.Send Email') }}</label>
                        </div>
                    </div> --}}
                    <div class="form-group d-flex justify-content-between">
                        <div>
                            <button type="submit" id="bookingSubmitBtn" class="btn btn-primary">{{ __('db.submit') }}</button>
                            <button type="button" class="btn btn-secondary ml-2" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                        </div>
                        <button type="button" id="bookingDeleteBtn" class="btn btn-danger d-none">
                            <i class="dripicons-trash"></i> {{ __('db.delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
    @include('backend.layout.partials.datatable_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
<style>

/* ── Page Header ───────────────────────────── */
.booking-page-header { padding: 18px 4px 14px; }

/* ── View Switcher (in header) ─────────────── */
.view-switcher-group {
    display: inline-flex;
    border-radius: .375rem;
    overflow: hidden;
    border: 1px solid #696cff;
    box-shadow: 0 2px 6px rgba(105,108,255,.20);
}
.view-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: .44rem 1rem;
    font-size: .9375rem;
    font-weight: 500;
    line-height: 1.53;
    color: #696cff;
    background: #fff;
    cursor: pointer;
    text-decoration: none !important;
    transition: background .18s, color .18s;
    border: none;
    outline: none;
    white-space: nowrap;
}
.view-btn + .view-btn { border-left: 1px solid #696cff; }
.view-btn:hover:not(.active) { background: #eaebff; color: #696cff; text-decoration: none; }
.view-btn.active {
    background: #696cff;
    color: #fff !important;
    font-weight: 600;
    text-decoration: none;
}

/* ── Tab content box ───────────────────────── */
.tab-content > .tab-pane {
    border: 1px solid #dee2e6;
    border-radius: 0 0 12px 12px;
}

/* ── Calendar Wrapper ──────────────────────── */
.booking-calendar-wrapper {
    background: #fff;
    border-radius: 0 0 12px 12px;
    overflow: hidden;
}


/* ── Sidebar ───────────────────────────────── */
.booking-sidebar {
    width: 240px;
    min-height: 680px;
    background: #f8f7fa;
    border-right: 1px solid #e8e8ee;
    display: flex;
    flex-direction: column;
}

/* ── Mini Stat chips ───────────────────────── */
.sidebar-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px;
    margin-top: 2px;
}
.stat-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border-radius: 8px;
    padding: 8px 10px;
    border: 1px solid #e8e8ee;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.stat-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.stat-booked   .stat-dot { background: #696cff; }
.stat-waiting  .stat-dot { background: #ffab00; }
.stat-completed .stat-dot { background: #28c76f; }
.stat-cancelled .stat-dot { background: #ea5455; }
.stat-label { font-size: 10px; color: #a1acb8; font-weight: 500; line-height: 1; margin-bottom: 2px; text-transform: uppercase; letter-spacing: .5px; }
.stat-count { font-size: 17px; font-weight: 700; color: #566a7f; line-height: 1; }

/* ── Sidebar labels ────────────────────────── */
.sidebar-section-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #a1acb8;
    margin-bottom: 8px;
}
.sidebar-field-label {
    font-size: 11px;
    font-weight: 600;
    color: #697a8d;
    display: block;
    margin-bottom: 5px;
}

/* ── Filter Pills ──────────────────────────── */
.filter-pill {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background .15s, transform .1s;
    margin-bottom: 4px;
    background: #fff;
    border: 1px solid #e8e8ee;
    user-select: none;
}
.filter-pill:hover { background: #eaebff; border-color: #c5c7ff; transform: translateX(2px); }
.filter-pill.inactive { opacity: .45; }
.filter-pill-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-right: 9px; }
.filter-pill-label { font-size: 13px; color: #566a7f; font-weight: 500; flex: 1; }
.filter-pill-check { font-size: 12px; margin-left: auto; }

/* ── Today box ─────────────────────────────── */
.sidebar-today-box {
    background: linear-gradient(135deg, #696cff 0%, #9155fd 100%);
    border-radius: 10px;
    padding: 14px;
    text-align: center;
    color: #fff;
    box-shadow: 0 4px 12px rgba(105,108,255,.35);
}
.sidebar-today-date { font-size: 18px; font-weight: 700; line-height: 1.2; }
.sidebar-today-label { font-size: 11px; opacity: .85; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px; }

/* ── Calendar Loader ───────────────────────── */
.calendar-loader-overlay {
    position: absolute; top:0; left:0; right:0; bottom:0;
    background: rgba(255,255,255,.9);
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
}
.calendar-loader-spinner {
    width: 48px; height: 48px;
    border-radius: 50%;
    border: 4px solid #eaebff;
    border-top-color: #696cff;
    animation: spin .8s linear infinite;
    margin: 0 auto;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── FullCalendar overrides ────────────────── */
#calendar { min-height: 640px; }

.fc .fc-toolbar.fc-header-toolbar { margin-bottom: 1em; }
.fc .fc-toolbar-title { font-size: 1.1rem !important; font-weight: 700 !important; color: #566a7f; }

.fc .fc-button {
    border-radius: 0 !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    padding: 5px 13px !important;
    text-transform: capitalize !important;
    letter-spacing: .3px !important;
    box-shadow: none !important;
    outline: none !important;
}
/* Stand-alone buttons (prev, next, today) */
.fc .fc-prev-button,
.fc .fc-next-button { border-radius: 6px !important; }
.fc .fc-today-button { border-radius: 6px !important; }

/* Button group — fix border-radius for first/last child */
.fc .fc-button-group { border-radius: 8px !important; overflow: hidden; box-shadow: 0 1px 4px rgba(105,108,255,.20); }
.fc .fc-button-group .fc-button:first-child { border-radius: 8px 0 0 8px !important; }
.fc .fc-button-group .fc-button:last-child  { border-radius: 0 8px 8px 0 !important; }

/* Separator between group buttons */
.fc .fc-button-group .fc-button + .fc-button { border-left: 1px solid rgba(255,255,255,.25) !important; }

.fc .fc-button-primary {
    background-color: #696cff !important;
    border-color: #696cff !important;
}
.fc .fc-button-primary:hover {
    background-color: #5a5ce8 !important;
    border-color: #5a5ce8 !important;
}
.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active {
    background-color: #4a4cd0 !important;
    border-color: #4a4cd0 !important;
    box-shadow: inset 0 2px 5px rgba(0,0,0,.18) !important;
}

.fc-theme-standard th { background: #f4f5f8 !important; }
.fc-theme-standard td, .fc-theme-standard th { border-color: #eceef1 !important; }
.fc .fc-daygrid-day-number { font-size: 12px; font-weight: 600; color: #697a8d; }
.fc .fc-daygrid-day.fc-day-today {
    background: linear-gradient(180deg, #eaebff 0%, #f4f5f8 100%) !important;
}
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number { color: #696cff; }

.fc-event {
    border-radius: 5px !important;
    font-size: 11.5px !important;
    font-weight: 500 !important;
    border: none !important;
    box-shadow: 0 1px 4px rgba(0,0,0,.12) !important;
    cursor: pointer !important;
    transition: transform .15s, box-shadow .15s !important;
}
.fc-daygrid-event {
    padding: 2px 7px !important;
}
.fc-timegrid-event .fc-event-main {
    padding: 2px 4px !important;
}
.fc-timegrid-event .fc-event-main-frame {
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 100%;
}
.fc-timegrid-event-short .fc-event-main {
    padding: 0 4px !important;
}
.fc-timegrid-event-short .fc-event-main-frame {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 4px;
    height: 100%;
    overflow: hidden;
}
.fc-timegrid-event-short .fc-event-time {
    font-weight: 700;
    white-space: nowrap;
}
.fc-timegrid-event-short .fc-event-title-container {
    flex-grow: 1;
    min-width: 0;
}
.fc-timegrid-event-short .fc-event-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fc-event:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(0,0,0,.18) !important;
}

.fc-col-header-cell-cushion {
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: .8px !important;
    color: #697a8d !important;
    padding: 8px 4px !important;
}

.fc .fc-daygrid-more-link {
    font-size: 11px;
    color: #696cff;
    font-weight: 600;
}

/* ── Tooltip ───────────────────────────────── */
.tooltip-inner { max-width: 220px; text-align: left; font-size: 12px; }

</style>
@endpush

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script>
$(document).ready(function () {

    $("ul#booking").siblings('a').attr('aria-expanded', 'true');
    $("ul#booking").addClass("show");
    $("ul#booking #booking-menu").addClass("active");

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.selectpicker').selectpicker({ style: 'btn-link' });

    var date_format = '{{ gen_setting()->date_format }}';
    var moment_format = date_format.replace('d', 'DD').replace('m', 'MM').replace('Y', 'YYYY') + ' hh:mm A';
    var moment_format_date = date_format.replace('d', 'DD').replace('m', 'MM').replace('Y', 'YYYY');

    function initDateTimePicker(dateId, timeId, hiddenId) {
        $(dateId).daterangepicker({
            singleDatePicker: true,
            timePicker: false,
            autoApply: true,
            locale: { format: moment_format_date }
        });

        function updateHidden() {
            var d = $(dateId).val();
            var t = $(timeId).val();
            if (d && t) {
                var dt = moment(d + ' ' + t, moment_format_date + ' HH:mm');
                if(dt.isValid()) {
                    $(hiddenId).val(dt.format('YYYY-MM-DD HH:mm:ss'));
                }
            }

            // Auto-adjust End Date if it's before Start Date
            setTimeout(function() {
                var sD = $('#visible_start_date').val(), sT = $('#visible_start_time').val();
                var eD = $('#visible_end_date').val(), eT = $('#visible_end_time').val();
                if(sD && sT && eD && eT) {
                    var startM = moment(sD + ' ' + sT, moment_format_date + ' HH:mm');
                    var endM   = moment(eD + ' ' + eT, moment_format_date + ' HH:mm');
                    if(startM.isValid() && endM.isValid() && endM.isBefore(startM)) {
                        $('#visible_end_date').data('daterangepicker').setStartDate(startM);
                        $('#visible_end_date').data('daterangepicker').setEndDate(startM);
                        $('#visible_end_date').val(startM.format(moment_format_date));
                        $('#visible_end_time').val(startM.format('HH:mm'));
                        $('#b_end_date').val(startM.format('YYYY-MM-DD HH:mm:ss'));
                    }
                }
            }, 50);
        }
        $(dateId).on('apply.daterangepicker change', updateHidden);
        $(timeId).on('change input', updateHidden);
    }

    initDateTimePicker('#visible_start_date', '#visible_start_time', '#b_start_date');
    initDateTimePicker('#visible_end_date', '#visible_end_time', '#b_end_date');

    // set default for hidden inputs
    $('#b_start_date').val(moment().format('YYYY-MM-DD HH:mm:ss'));
    $('#b_end_date').val(moment().format('YYYY-MM-DD HH:mm:ss'));

    function confirmDelete() { return confirm("Are you sure want to delete?"); }

    // ── DataTable ─────────────────────────────────────────────────
    var booking_id    = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $('#booking-table').DataTable({
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{ __("db.records per page") }}',
            "info": '<small>{{ __("db.Showing") }} _START_ - _END_ (_TOTAL_)</small>',
            "search": '{{ __("db.Search") }}',
            'paginate': { 'previous': '<i class="dripicons-chevron-left"></i>', 'next': '<i class="dripicons-chevron-right"></i>' }
        },
        'columnDefs': [
            { "orderable": false, 'targets': [0, 11] },
            { 'checkboxes': { 'selectRow': true }, 'targets': 0 }
        ],
        'select': { style: 'multi', selector: 'td:first-child' },
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            { extend: 'pdf',    text: '<i class="fa fa-file-pdf-o"></i>',       exportOptions: { columns: ':visible:Not(.not-exported)' }, footer: true },
            { extend: 'excel',  text: '<i class="dripicons-document-new"></i>',  exportOptions: { columns: ':visible:Not(.not-exported)' }, footer: true },
            { extend: 'csv',    text: '<i class="fa fa-file-text-o"></i>',       exportOptions: { columns: ':visible:Not(.not-exported)' }, footer: true },
            { extend: 'print',  text: '<i class="fa fa-print"></i>',             exportOptions: { columns: ':visible:Not(.not-exported)' }, footer: true },
            {
                text: '<i class="ti ti-x"></i>', className: 'buttons-delete',
                action: function (e, dt) {
                    if (user_verified == '1') {
                        booking_id.length = 0;
                        $(':checkbox:checked').each(function (i) { if (i) booking_id[i-1] = $(this).closest('tr').data('id'); });
                        if (booking_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({ type:'POST', url:'{{ url("bookings/deletebyselection") }}', data:{ bookingIdArray: booking_id },
                                success: function(data) { alert(data); dt.rows({ page:'current', selected:true }).remove().draw(false); }
                            });
                        } else if (!booking_id.length) alert('Nothing is selected!');
                    } else alert('This feature is disabled for demo!');
                }
            },
            { extend: 'colvis', text: '<i class="fa fa-eye"></i>', columns: ':gt(0)' },
        ],
    });

    // Edit from list
    $(document).on('click', '.edit-booking-btn', function () {
        $.get('{{ url("bookings") }}/' + $(this).data('id'), function (b) { openEditFromData(b); });
    });

    // ── Product → auto-fill price ─────────────────────────────────
    $('#b_product_id').on('change', function () {
        var selected = $(this).find('option:selected');
        var price    = selected.data('price');
        if (price) {
            $('#b_price').val(price);
        } else {
            $('#b_price').val('');
        }
        $('.selectpicker').selectpicker('refresh');
    });

    // ── FullCalendar v5 ───────────────────────────────────────────
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        height:       'auto',
        editable:     false,
        selectable:   true,
        navLinks:     true,
        dayMaxEvents: true,
        nowIndicator: true,

        loading: function (isLoading) {
            if (isLoading) {
                $('#calendarLoader').fadeIn(200);
            } else {
                $('#calendarLoader').fadeOut(300);
            }
        },

        events: function (info, successCb, failureCb) {
            var statuses = [];
            $('.cal-filter:checked').each(function () { statuses.push($(this).data('value')); });
            $.get('{{ route("booking.events") }}', {
                start: info.startStr, end: info.endStr,
                status: statuses.join(','),
                warehouse_id: $('#warehouseFilter').val() || ''
            }, function(data) {
                successCb(data);
                // Update stat counts
                var counts = { Booked: 0, Waiting: 0, Completed: 0, Cancelled: 0 };
                $.each(data, function(i, ev) {
                    if (counts[ev.extendedProps.status] !== undefined) counts[ev.extendedProps.status]++;
                });
                $('#stat-count-booked').text(counts.Booked);
                $('#stat-count-waiting').text(counts.Waiting);
                $('#stat-count-completed').text(counts.Completed);
                $('#stat-count-cancelled').text(counts.Cancelled);
            }).fail(failureCb);
        },

        dateClick:  function (info)  { openAddForm(info.dateStr); },
        eventClick: function (info)  { openEditForm(info.event); },

        eventDidMount: function (info) {
            var p = info.event.extendedProps;
            var startFmt = moment(info.event.start).format(moment_format);
            var endFmt = info.event.end ? moment(info.event.end).format(moment_format) : startFmt;

            var tip = 'Time: ' + startFmt + ' to ' + endFmt +
                      '\nCustomer: ' + (p.customer || '') +
                      '\nStatus: '   + (p.status   || '') +
                      '\nEmployee: ' + (p.employee  || '');
            if (p.note) tip += '\nNote: ' + p.note;
            $(info.el).attr('title', tip).tooltip({ container: 'body', placement: 'top' });
        },
    });

    // ── Tab switch → re-render calendar + sync view-btn active ───
    $('#calendar-tab').on('shown.bs.tab', function () {
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        calendar.render();
        calendar.updateSize();
    });

    $('#list-tab').on('shown.bs.tab', function () {
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
    });

    // ── Default: Calendar active, List only when ?tab=list ────────
    if ('{{ request("tab") }}' === 'list') {
        $('#list-tab').tab('show');
    } else {
        setTimeout(function () {
            calendar.render();
            calendar.updateSize();
        }, 200);
    }

    $(document).on('change', '.cal-filter, #warehouseFilter', function () {
        calendar.refetchEvents();
    });

    // ── Today date in sidebar ─────────────────────────────────────
    $('#sidebar-today-date').text(moment().format('D MMM, YYYY'));

    // ── Toggle filter ─────────────────────────────────────────────
    window.toggleFilter = function (status) {
        var cb  = $('#filter-' + status.toLowerCase());
        var row = cb.closest('.filter-pill');
        cb.prop('checked', !cb.prop('checked'));
        row.toggleClass('inactive', !cb.prop('checked'));
        calendar.refetchEvents();
    };

    // ── Reset modal ───────────────────────────────────────────────
    function resetModal() {
        $('#bookingForm')[0].reset();
        $('#formMethod').val('POST');
        $('#bookingForm').attr('action', '{{ route("bookings.store") }}');
        $('#b_price').val('');
        $('#bookingDeleteBtn').addClass('d-none');
        $('#bookingSubmitBtn').prop('disabled', false).text('{{ __("db.submit") }}');
        $('#bookingModalLabel').text('Add Booking');
        $('.selectpicker').selectpicker('refresh');

        var now = moment();
        $('#b_start_date').val(now.format('YYYY-MM-DD HH:mm:ss'));
        $('#b_end_date').val(now.format('YYYY-MM-DD HH:mm:ss'));

        $('#visible_start_time').val(now.format('HH:mm'));
        $('#visible_end_time').val(now.format('HH:mm'));

        $('#visible_start_date').data('daterangepicker').setStartDate(now);
        $('#visible_start_date').data('daterangepicker').setEndDate(now);
        $('#visible_end_date').data('daterangepicker').setStartDate(now);
        $('#visible_end_date').data('daterangepicker').setEndDate(now);
    }

    function openAddForm(dateStr) {
        resetModal();
        if (dateStr) {
            var defaultTimeStr = ' 09:00:00';
            var formattedDate = dateStr.includes('T') ? dateStr.replace('T', ' ').slice(0, 19) : dateStr + defaultTimeStr;
            var mDt = moment(formattedDate, 'YYYY-MM-DD HH:mm:ss');

            $('#b_start_date').val(formattedDate);
            $('#b_end_date').val(formattedDate);

            $('#visible_start_time').val(mDt.format('HH:mm'));
            $('#visible_end_time').val(mDt.format('HH:mm'));

            $('#visible_start_date').data('daterangepicker').setStartDate(mDt);
            $('#visible_start_date').data('daterangepicker').setEndDate(mDt);
            $('#visible_end_date').data('daterangepicker').setStartDate(mDt);
            $('#visible_end_date').data('daterangepicker').setEndDate(mDt);
        }
        $('#booking-modal').modal('show');
    }

    function openEditForm(event) {
        resetModal();
        $('#bookingModalLabel').text('Edit Booking');
        $('#formMethod').val('PUT');
        $('#bookingForm').attr('action', '{{ url("bookings") }}/' + event.id);
        $('#bookingSubmitBtn').text('{{ __("db.update") }}');
        $('#bookingDeleteBtn').removeClass('d-none');

        var p = event.extendedProps;
        $('#b_warehouse_id').val(p.warehouse_id);
        $('#b_customer_id').val(p.customer_id);
        $('#b_user_id').val(p.user_id || '');
        $('#b_status').val(p.status);
        $('#b_product_id').val(p.product_id || '');
        $('#b_price').val(p.price || '');
        $('#b_note').val(p.note || '');
        $('.selectpicker').selectpicker('refresh');

        var startStr = event.start ? moment(event.start).format('YYYY-MM-DD HH:mm:ss') : '';
        var endStr   = event.end ? moment(event.end).format('YYYY-MM-DD HH:mm:ss') : '';

        $('#b_start_date').val(startStr);
        $('#b_end_date').val(endStr);
        if(startStr) {
            var sM = moment(startStr, 'YYYY-MM-DD HH:mm:ss');
            $('#visible_start_time').val(sM.format('HH:mm'));
            $('#visible_start_date').data('daterangepicker').setStartDate(sM);
            $('#visible_start_date').data('daterangepicker').setEndDate(sM);
        }
        if(endStr) {
            var eM = moment(endStr, 'YYYY-MM-DD HH:mm:ss');
            $('#visible_end_time').val(eM.format('HH:mm'));
            $('#visible_end_date').data('daterangepicker').setStartDate(eM);
            $('#visible_end_date').data('daterangepicker').setEndDate(eM);
        }
        $('#booking-modal').modal('show');
    }

    function openEditFromData(b) {
        resetModal();
        $('#bookingModalLabel').text('Edit Booking');
        $('#formMethod').val('PUT');
        $('#bookingForm').attr('action', '{{ url("bookings") }}/' + b.id);
        $('#bookingSubmitBtn').text('{{ __("db.update") }}');
        $('#bookingDeleteBtn').removeClass('d-none');

        $('#b_warehouse_id').val(b.warehouse_id);
        $('#b_customer_id').val(b.customer_id);
        $('#b_user_id').val(b.user_id || '');
        $('#b_status').val(b.status);
        $('#b_product_id').val(b.product_id || '');
        $('#b_price').val(b.price || '');
        $('#b_note').val(b.note || '');
        $('.selectpicker').selectpicker('refresh');

        var startStr = b.start_date ? b.start_date : '';
        var endStr   = b.end_date ? b.end_date : '';

        $('#b_start_date').val(startStr);
        $('#b_end_date').val(endStr);
        if(startStr) {
            var sM = moment(startStr, 'YYYY-MM-DD HH:mm:ss');
            $('#visible_start_time').val(sM.format('HH:mm'));
            $('#visible_start_date').data('daterangepicker').setStartDate(sM);
            $('#visible_start_date').data('daterangepicker').setEndDate(sM);
        }
        if(endStr) {
            var eM = moment(endStr, 'YYYY-MM-DD HH:mm:ss');
            $('#visible_end_time').val(eM.format('HH:mm'));
            $('#visible_end_date').data('daterangepicker').setStartDate(eM);
            $('#visible_end_date').data('daterangepicker').setEndDate(eM);
        }
        $('#booking-modal').modal('show');
    }

    $('#addBookingBtn').on('click', function () { openAddForm(null); });

    // ── Submit + loading ──────────────────────────────────────────
    $('#bookingForm').on('submit', function (e) {
        if (!$('#b_warehouse_id').val()) { e.preventDefault(); alert('Please select Warehouse!'); return; }
        if (!$('#b_customer_id').val())  { e.preventDefault(); alert('Please select Customer!');  return; }
        if (!$('#b_start_date').val())   { e.preventDefault(); alert('Please select Start Date!'); return; }
        if (!$('#b_end_date').val())     { e.preventDefault(); alert('Please select End Date!');   return; }

        $('#bookingSubmitBtn')
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm mr-1" role="status"></span> Saving...');
    });

    // ── Delete ────────────────────────────────────────────────────
    $('#bookingDeleteBtn').on('click', function () {
        var id = $('#bookingForm').attr('action').split('/').pop();
        if (!id || !confirm('Are you sure want to delete?')) return;
        $.ajax({
            type: 'POST',
            url:  '{{ url("bookings") }}/' + id,
            data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
            success: function (data) {
                if (data.success) { $('#booking-modal').modal('hide'); location.href = '{{ route("booking.index") }}?tab=list'; }
            }
        });
    });

    $('#booking-modal').on('hidden.bs.modal', function () { resetModal(); });

});
</script>
@endpush
