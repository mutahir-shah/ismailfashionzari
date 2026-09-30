@extends('backend.layout.main')

@push('css')
<style>
    .timeline { border-left: 3px solid #6777ef; margin-left: 10px; padding-left: 20px; }
    .timeline-item { position: relative; margin-bottom: 20px; }
    .timeline-item::before { content: ''; width: 13px; height: 13px; border-radius: 50%; background: #6777ef; position: absolute; left: -27px; top: 4px; border: 2px solid #fff; box-shadow: 0 0 0 2px #6777ef; }
    .info-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
    /* Hide the print-specific site header on screen view */
    #service-details-content .sp-header { display: none; }
</style>
@endpush

@section('content')

@if(session()->has('message'))
    <div class="alert alert-success alert-dismissible text-center">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('message') }}
    </div>
@endif

<section class="forms">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-md-8">
                <h4 class="mb-0">
                    <i class="fa {{ $job->service_type === 'device' ? 'fa-mobile' : 'fa-car' }} mr-2"></i>
                    {{ $job->title }}
                    <small class="text-muted ml-2">{{ $job->reference_no }}</small>
                </h4>
            </div>
            <div class="col-md-4 text-right">
                <a href="{{ route('repair.service.edit', $job->id) }}" class="btn btn-warning btn-sm">
                    <i class="ti ti-edit"></i> {{ __('db.edit') }}
                </a>
                <a href="{{ route('repair.service.parts', $job->id) }}" class="btn btn-info btn-sm ml-1">
                    <i class="ti ti-settings"></i> Parts & Billing
                </a>
                <a href="{{ route('repair.service.index') }}" class="btn btn-secondary btn-sm ml-1">
                    <i class="ti ti-arrow-left"></i> {{ __('db.Back') }}
                </a>
                <button onclick="printJob()" class="btn btn-default btn-sm ml-1">
                    <i class="ti ti-printer"></i> {{ __('db.print') }}
                </button>
            </div>
        </div>

        <div class="row" id="printable-area">

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('db.job_info') }}</h5>
                        <div>
                            {!! $job->status_badge !!}
                            {!! $job->priority_badge !!}
                        </div>
                    </div>
                    <div class="card-body" id="service-details-content">
                        @include('repair::service.partials.print_content', ['job' => $job, 'general_setting' => $general_setting])
                    </div>
                </div>
            </div>

            <div class="col-md-4">

                <div class="card mb-3 d-print-none">
                    <div class="card-header"><h5 class="mb-0">⚡ {{ __('db.quick_status_update') }}</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ __('db.new_status') }}</label>
                            <select id="quick_status" class="form-control">
                                <option value="pending"     {{ $job->status === 'pending'     ? 'selected' : '' }}>{{ __('db.Pending') }}</option>
                                <option value="diagnosed"   {{ $job->status === 'diagnosed'   ? 'selected' : '' }}>{{ __('db.diagnosed') }}</option>
                                <option value="in_progress" {{ $job->status === 'in_progress' ? 'selected' : '' }}>{{ __('db.in_progress') }}</option>
                                <option value="completed"   {{ $job->status === 'completed'   ? 'selected' : '' }}>{{ __('db.Completed') }}</option>
                                <option value="delivered"   {{ $job->status === 'delivered'   ? 'selected' : '' }}>{{ __('db.Delivered') }}</option>
                                <option value="cancelled"   {{ $job->status === 'cancelled'   ? 'selected' : '' }}>{{ __('db.Cancel') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.priority') }}</label>
                            <select id="quick_priority" class="form-control">
                                <option value="low" {{ $job->priority === 'low' ? 'selected' : '' }}>🟢 {{ __('db.low') }}</option>
                                <option value="medium" {{ $job->priority === 'medium' ? 'selected' : '' }}>🟡 {{ __('db.medium') }}</option>
                                <option value="high" {{ $job->priority === 'high' ? 'selected' : '' }}>🔴 {{ __('db.high') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.note') }}</label>
                            <textarea id="quick_note" class="form-control" rows="2" placeholder="{{ __('db.optional_note') }}"></textarea>
                        </div>
                        <button type="button" id="quick-update-btn" class="btn btn-primary btn-sm btn-block">
                            <i class="ti ti-checkmark"></i> {{ __('db.update_status') }}
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-list"></i> {{ __('db.activity_timeline') }}</h5></div>
                    <div class="card-body">
                        @if($job->updates->count())
                            <div class="timeline">
                                @foreach($job->updates as $update)
                                    <div class="timeline-item">
                                        <small class="text-muted">{{ $update->created_at->format('d M Y, h:i A') }}</small>
                                        <br>
                                        <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $update->status)) }}</span>
                                        by <strong>{{ optional($update->updatedBy)->name }}</strong>
                                        @if($update->note)
                                            <br><small class="text-muted">{{ $update->note }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">{{ __('db.no_updates_yet') }}</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>


@endsection

@push('scripts')
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    $('#quick-update-btn').on('click', function () {
        $.post('{{ route("repair.service.add-update", $job->id) }}', {
            status: $('#quick_status').val(),
            priority: $('#quick_priority').val(),
            note:   $('#quick_note').val()
        }, function (res) {
            if (res.success) { location.reload(); }
        });
    });



    function printJob() {
        var content = document.getElementById('service-details-content').innerHTML;
        var a = window.open('');
        a.document.write('<html><head><title>{{ addslashes($general_setting->site_title) }}</title></head><body style="margin:20px;">');
        a.document.write(content);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function () { a.print(); a.close(); }, 600);
    }
    
    $("ul#repair").siblings('a').attr('aria-expanded', 'true');
    $("ul#repair").addClass("show");
    $("ul#repair #service-list-menu").addClass("active");
</script>
@endpush
