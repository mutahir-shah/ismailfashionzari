{{--
    PRINT PARTIAL — includes ALL form fields
    Variables expected: $job, $general_setting
--}}
<style>
.sp-wrap { font-family: sans-serif; font-size: 13px; line-height: 1.55; color: #111; }
.sp-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
.sp-header h2 { margin: 0 0 2px; font-size: 18px; }
.sp-header p  { margin: 0; font-size: 12px; color: #555; }
.sp-section   { margin-bottom: 12px; }
.sp-section-title { font-size: 13px; font-weight: 700; background: #f0f0f0; padding: 4px 8px; border-left: 3px solid #555; margin-bottom: 6px; }
.sp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px; }
.sp-row { display: flex; padding: 3px 0; border-bottom: 1px solid #f0f0f0; }
.sp-label { font-weight: 600; min-width: 150px; flex-shrink: 0; color: #444; }
.sp-val   { color: #111; }
.sp-full  { grid-column: 1 / -1; }
.sp-parts-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 12px; }
.sp-parts-table th { background: #f0f0f0; border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
.sp-parts-table td { border: 1px solid #ccc; padding: 5px 8px; }
.sp-totals    { margin-top: 8px; display: flex; justify-content: flex-end; }
.sp-totals table { font-size: 12px; border-collapse: collapse; min-width: 260px; }
.sp-totals td { padding: 3px 8px; border: 1px solid #ddd; }
.sp-totals .grand { font-weight: 700; background: #333; color: #fff; }
.sp-totals .paid  { background: #d4edda; }
.sp-totals .due   { background: #f8d7da; font-weight: 700; }
.sp-footer { margin-top: 14px; border-top: 1px dashed #aaa; padding-top: 8px; font-size: 11px; color: #666; }
@media print {
    .sp-wrap { font-size: 12px; }
    .sp-parts-table th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<div class="sp-wrap">

    {{-- ── HEADER ── --}}
    <div class="sp-header">
        <h2>{{ $general_setting->site_title }}</h2>
        <p>{{ __('db.service_job_details') }}</p>
    </div>

    {{-- ── BASIC INFO ── --}}
    <div class="sp-section">
        <div class="sp-section-title">📋 {{ __('db.basic_information') }}</div>
        <div class="sp-grid">
            <div class="sp-row"><span class="sp-label">{{ __('db.reference') }}</span><span class="sp-val">{{ $job->reference_no }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.date_created') }}</span><span class="sp-val">{{ date(config('date_format'), strtotime($job->created_at)) }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.Title') }}</span><span class="sp-val">{{ $job->title }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.service_type') }}</span><span class="sp-val">{{ ucfirst($job->service_type) }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.status') }}</span><span class="sp-val">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.priority') }}</span><span class="sp-val">{{ ucfirst($job->priority) }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.expected_delivery') }}</span><span class="sp-val">{{ $job->expected_delivery_date ? $job->expected_delivery_date->format(config('date_format')) : '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.Created By') }}</span><span class="sp-val">{{ optional($job->createdBy)->name ?? '—' }}</span></div>
            @if($job->description)
            <div class="sp-row sp-full"><span class="sp-label">{{ __('db.Description') }}</span><span class="sp-val">{{ $job->description }}</span></div>
            @endif
            @if($job->note)
            <div class="sp-row sp-full"><span class="sp-label">{{ __('db.note_remarks') }}</span><span class="sp-val">{{ $job->note }}</span></div>
            @endif
        </div>
    </div>

    {{-- ── CUSTOMER & ASSIGNMENT ── --}}
    <div class="sp-section">
        <div class="sp-section-title">👤 {{ __('db.customer') }} & {{ __('db.assignment') }}</div>
        <div class="sp-grid">
            <div class="sp-row"><span class="sp-label">{{ __('db.customer') }}</span><span class="sp-val">{{ optional($job->customer)->name ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.Phone') }}</span><span class="sp-val">{{ optional($job->customer)->phone_number ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.Warehouse') }}</span><span class="sp-val">{{ optional($job->warehouse)->name ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.technician') }}</span><span class="sp-val">{{ optional($job->assignedTo)->name ?? __('db.unassigned') }}</span></div>
        </div>
    </div>

    {{-- ── DEVICE DETAILS ── --}}
    @if($job->service_type === 'device' && $job->device)
    <div class="sp-section">
        <div class="sp-section-title">📱 {{ __('db.device_details') }}</div>
        <div class="sp-grid">
            <div class="sp-row"><span class="sp-label">{{ __('db.device_type') }}</span><span class="sp-val">{{ optional($job->device->deviceTypeInfo)->name ?? $job->device->device_type }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.Brand') }}</span><span class="sp-val">{{ $job->device->brand ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.model') }}</span><span class="sp-val">{{ $job->device->model ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.serial_number') }}</span><span class="sp-val">{{ $job->device->serial_number ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.imei') }}</span><span class="sp-val">{{ $job->device->imei ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.password_hint') }}</span><span class="sp-val">{{ $job->device->password_hint ?? '—' }}</span></div>
            <div class="sp-row sp-full"><span class="sp-label">{{ __('db.accessories') }}</span><span class="sp-val">{{ $job->device->accessories ?? '—' }}</span></div>
            @if($job->device->issue_reported)
            <div class="sp-row sp-full"><span class="sp-label">{{ __('db.issue_reported') }}</span><span class="sp-val">{{ $job->device->issue_reported }}</span></div>
            @endif
            @if($job->device->condition_notes)
            <div class="sp-row sp-full"><span class="sp-label">{{ __('db.condition_on_arrival') }}</span><span class="sp-val">{{ $job->device->condition_notes }}</span></div>
            @endif
        </div>
    </div>
    @endif

    {{-- ── VEHICLE DETAILS ── --}}
    @if($job->service_type === 'vehicle' && $job->vehicle)
    <div class="sp-section">
        <div class="sp-section-title">🚗 {{ __('db.vehicle_details') }}</div>
        <div class="sp-grid">
            <div class="sp-row"><span class="sp-label">{{ __('db.vehicle_type') }}</span><span class="sp-val">{{ optional($job->vehicle->vehicleTypeInfo)->name ?? $job->vehicle->vehicle_type }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.Brand') }}</span><span class="sp-val">{{ $job->vehicle->brand ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.model') }}</span><span class="sp-val">{{ $job->vehicle->model ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.year') }}</span><span class="sp-val">{{ $job->vehicle->year ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.registration_no') }}</span><span class="sp-val">{{ $job->vehicle->registration_no ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.engine_no') }}</span><span class="sp-val">{{ $job->vehicle->engine_no ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.chassis_no') }}</span><span class="sp-val">{{ $job->vehicle->chassis_no ?? '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.mileage') }}</span><span class="sp-val">{{ $job->vehicle->mileage ? number_format($job->vehicle->mileage).' km' : '—' }}</span></div>
            <div class="sp-row"><span class="sp-label">{{ __('db.fuel_level') }}</span><span class="sp-val">{{ $job->vehicle->fuel_level ? ucfirst(str_replace('_', '/', $job->vehicle->fuel_level)) : '—' }}</span></div>
            @if($job->vehicle->issue_reported)
            <div class="sp-row sp-full"><span class="sp-label">{{ __('db.issue_reported') }}</span><span class="sp-val">{{ $job->vehicle->issue_reported }}</span></div>
            @endif
            @if($job->vehicle->condition_notes)
            <div class="sp-row sp-full"><span class="sp-label">{{ __('db.condition_on_arrival') }}</span><span class="sp-val">{{ $job->vehicle->condition_notes }}</span></div>
            @endif
        </div>
    </div>
    @endif

    {{-- ── PARTS / ITEMS ── --}}
    <div class="sp-section">
        <div class="sp-section-title">🔧 {{ __('db.parts_items_used') }}</div>
        <table class="sp-parts-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('db.product') }}</th>
                    <th>{{ __('db.qty') }}</th>
                    <th>{{ __('db.Unit Price') }}</th>
                    <th>{{ __('db.Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($job->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ optional($item->product)->name ?? '—' }} @if(optional($item->product)->code) [{{ $item->product->code }}] @endif</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, config('decimal')) }}</td>
                    <td>{{ number_format($item->total, config('decimal')) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#888;">{{ __('db.no_parts_added') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="sp-totals">
            <table>
                <tr><td>{{ __('db.parts_total') }}</td><td>{{ number_format($job->items->sum('total'), config('decimal')) }}</td></tr>
                <tr><td>{{ __('db.service_charge') }}</td><td>{{ number_format($job->service_charge, config('decimal')) }}</td></tr>
                <tr><td>{{ __('db.Discount') }}</td><td>- {{ number_format($job->discount, config('decimal')) }}</td></tr>
                <tr><td>{{ __('db.Tax') }}</td><td>{{ number_format($job->tax, config('decimal')) }}</td></tr>
                <tr class="grand"><td>{{ __('db.grand total') }}</td><td>{{ number_format($job->total_amount, config('decimal')) }}</td></tr>
                <tr class="paid"><td>{{ __('db.Paid Amount') }}</td><td>{{ number_format($job->paid_amount, config('decimal')) }}</td></tr>
                <tr class="due"><td>{{ __('db.Due') }}</td><td>{{ number_format($job->due_amount, config('decimal')) }}</td></tr>
            </table>
        </div>
    </div>

    {{-- ── FOOTER ── --}}
    <div class="sp-footer">
        {{ __('db.reference') }}: <strong>{{ $job->reference_no }}</strong> &nbsp;|&nbsp;
        {{ __('db.Created By') }}: <strong>{{ optional($job->createdBy)->name ?? '' }}</strong> &nbsp;|&nbsp;
        {{ __('db.date_created') }}: <strong>{{ date(config('date_format'), strtotime($job->created_at)) }}</strong>
    </div>

</div>
