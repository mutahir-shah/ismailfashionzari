@extends('backend.layout.main')

@push('css')
<style>
    .accounting-health-hero {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        overflow: hidden;
    }
    .accounting-health-hero.green {
        background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 68%);
        border-left: 8px solid #10b981;
    }
    .accounting-health-hero.yellow {
        background: linear-gradient(135deg, #fffbeb 0%, #ffffff 68%);
        border-left: 8px solid #f59e0b;
    }
    .accounting-health-hero.red {
        background: linear-gradient(135deg, #fef2f2 0%, #ffffff 68%);
        border-left: 8px solid #ef4444;
    }
    .accounting-health-hero.neutral {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 68%);
        border-left: 8px solid #94a3b8;
    }
    .health-group-card,
    .health-issue-card,
    .health-activity-card {
        border: 1px solid #eef2f7;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        height: 100%;
    }
    .health-status-pill {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 12px;
        text-transform: uppercase;
    }
    .health-status-pill.pass {
        background: #dcfce7;
        color: #166534;
    }
    .health-status-pill.warn {
        background: #fef3c7;
        color: #92400e;
    }
    .health-status-pill.fail {
        background: #fee2e2;
        color: #991b1b;
    }
    .health-status-pill.not_run {
        background: #e5e7eb;
        color: #374151;
    }
    .health-muted {
        color: #64748b;
    }
    .health-detail-list {
        background: #f8fafc;
        border-radius: 14px;
        margin-top: 16px;
        padding: 14px;
    }
    .health-detail-list + .health-detail-list {
        margin-top: 10px;
    }
    .health-technical-output {
        background: #0f172a !important;
        border-radius: 14px;
        color: #f8fafc !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        line-height: 1.55;
        max-height: 360px;
        overflow: auto;
        padding: 18px;
        white-space: pre-wrap;
    }
    .health-timeline-item {
        border-left: 3px solid #e2e8f0;
        padding: 0 0 18px 18px;
        position: relative;
    }
    .health-timeline-item:before {
        background: #7c5cc4;
        border-radius: 50%;
        content: "";
        height: 11px;
        left: -7px;
        position: absolute;
        top: 4px;
        width: 11px;
    }
</style>
@endpush

@section('content')
@php
    $overall = $health['overall'];
    $certification = $health['certification'] ?? null;
@endphp

<section>
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">{{ __('db.accounting_health_title') }}</h3>
                <p class="health-muted mb-0">{{ __('db.accounting_health_intro') }}</p>
            </div>
            <form action="{{ route('accounting.reconciliation.run-health-check') }}" method="POST" class="mt-3 mt-md-0">
                @csrf
                <button type="submit" class="btn btn-primary" aria-label="{{ __('db.accounting_health_action_run_check') }}">
                    <i class="ti ti-heart-check"></i> {{ __('db.accounting_health_action_run_check') }}
                </button>
            </form>
        </div>

        <div class="card accounting-health-hero {{ $overall['level'] }} mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <span class="health-status-pill {{ $overall['level'] === 'green' ? 'pass' : ($overall['level'] === 'yellow' ? 'warn' : ($overall['level'] === 'red' ? 'fail' : 'not_run')) }}">
                            {{ __('db.' . $overall['label_key']) }}
                        </span>
                        <h2 class="mt-3 mb-2">{{ __('db.' . $overall['message_key']) }}</h2>
                        <p class="health-muted mb-0">
                            {{ __('db.accounting_health_last_completed_check') }}
                            <strong>{{ $certification['checked_at'] ?? __('db.accounting_health_not_run_yet') }}</strong>
                        </p>
                    </div>
                    <div class="col-lg-4 mt-4 mt-lg-0">
                        <div class="border rounded p-3 bg-white">
                            <div class="health-muted">{{ __('db.accounting_health_issues_count_label') }}</div>
                            <h3 class="mb-0">{{ $overall['issues_count'] ?? count($health['issues']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12 mb-3">
                <h4 class="mb-1">{{ __('db.accounting_health_areas_title') }}</h4>
                <p class="health-muted mb-0">{{ __('db.accounting_health_areas_intro') }}</p>
            </div>

            @foreach($health['groups'] as $group)
                <div class="col-lg-4 mb-3">
                    <div class="card health-group-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="mb-0">{{ __('db.' . $group['title_key']) }}</h5>
                                <span class="health-status-pill {{ $group['tone'] }}">
                                    {{ __('db.' . $group['display_status_key']) }}
                                </span>
                            </div>
                            <p class="health-muted">{{ __('db.' . $group['description_key']) }}</p>
                            <p class="mb-3">{{ __('db.' . $group['summary_key']) }}</p>

                            <div class="d-flex flex-wrap align-items-center">
                                <button class="btn btn-outline-secondary btn-sm mr-2 mb-2" type="button" data-toggle="collapse" data-target="#{{ $group['details_id'] }}" aria-expanded="false" aria-controls="{{ $group['details_id'] }}">
                                    {{ __('db.accounting_health_action_view_details') }}
                                </button>

                                @if($group['action_label_key'] && $group['action_url'])
                                    @if(\Illuminate\Support\Str::startsWith($group['action_url'], '#'))
                                        <a href="{{ $group['action_url'] }}" data-toggle="collapse" data-target="{{ $group['action_url'] }}" aria-expanded="false" class="btn btn-primary btn-sm mb-2">
                                            {{ __('db.' . $group['action_label_key']) }}
                                        </a>
                                    @else
                                        <a href="{{ $group['action_url'] }}" class="btn btn-primary btn-sm mb-2">
                                            {{ __('db.' . $group['action_label_key']) }}
                                        </a>
                                    @endif
                                @endif
                            </div>

                            <div id="{{ $group['details_id'] }}" class="collapse">
                                @foreach($group['checks'] as $check)
                                    <div class="health-detail-list">
                                        <div class="d-flex justify-content-between">
                                            <strong>{{ __('db.' . $check['label_key']) }}</strong>
                                            <span class="health-status-pill {{ $check['tone'] }}">{{ __('db.' . $check['display_status_key']) }}</span>
                                        </div>
                                        <p class="health-muted mb-1">{{ __('db.' . $check['description_key']) }}</p>
                                        <p class="mb-0">{{ __('db.' . $check['detail_key'], $check['detail_params'] ?? []) }}</p>
                                        @if($check['technical_label_key'])
                                            <small class="health-muted">{{ __('db.accounting_health_technical_label_prefix') }} {{ __('db.' . $check['technical_label_key']) }}</small>
                                        @endif
                                        @if($check['label_key'] === 'accounting_health_check_payment_accounts' && $canRepairPaymentMappings && ($paymentMappingRepair['repairable_count'] ?? 0) > 0)
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#payment-account-repair-review">
                                                    {{ __('db.accounting_health_action_repair_payment_mappings') }}
                                                </button>
                                            </div>
                                        @elseif($check['label_key'] === 'accounting_health_check_payment_accounts' && $canRepairPaymentMappings && ($paymentMappingRepair['invalid_count'] ?? 0) > 0)
                                            <p class="text-warning mt-2 mb-0">{{ __('db.accounting_health_payment_mapping_manual_review') }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(!empty($health['issues']))
            <div class="row mb-4">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <div class="card health-issue-card">
                        <div class="card-body">
                            <h4 class="mb-3">{{ __('db.accounting_health_issues_title') }}</h4>

                            @foreach($health['issues'] as $issue)
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                                        <div class="pr-md-3">
                                            <span class="health-status-pill {{ $issue['tone'] }}">{{ __('db.' . ($issue['severity'] === 'fail' ? 'accounting_health_status_action_required' : 'accounting_health_status_needs_review')) }}</span>
                                            <h5 class="mt-2 mb-1">{{ __('db.' . $issue['title_key']) }}</h5>
                                            <p class="health-muted mb-0">{{ __('db.' . $issue['explanation_key'], $issue['explanation_params'] ?? []) }}</p>
                                            @if($issue['technical_label_key'])
                                                <small class="health-muted">{{ __('db.accounting_health_technical_label_prefix') }} {{ __('db.' . $issue['technical_label_key']) }}</small>
                                            @endif
                                        </div>
                                        @if($issue['action_label_key'] && $issue['action_url'] && ($issue['action_url'] !== '#technical-details' || $canViewTechnicalDetails))
                                            @if($issue['action_url'] === '#payment-account-repair-review')
                                                <button type="button" class="btn btn-outline-primary btn-sm mt-3 mt-md-0" data-toggle="modal" data-target="#payment-account-repair-review">
                                                    {{ __('db.' . $issue['action_label_key']) }}
                                                </button>
                                            @elseif(\Illuminate\Support\Str::startsWith($issue['action_url'], '#'))
                                                <a href="{{ $issue['action_url'] }}" data-toggle="collapse" data-target="{{ $issue['action_url'] }}" aria-expanded="false" class="btn btn-outline-primary btn-sm mt-3 mt-md-0">
                                                    {{ __('db.' . $issue['action_label_key']) }}
                                                </a>
                                            @else
                                                <a href="{{ $issue['action_url'] }}" class="btn btn-outline-primary btn-sm mt-3 mt-md-0">
                                                    {{ __('db.' . $issue['action_label_key']) }}
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    @include('backend.accounting.reconciliation.partials.recent_activity', ['health' => $health])
                </div>
            </div>
        @else
            <div class="row mb-4">
                <div class="col-lg-4 ml-auto">
                    @include('backend.accounting.reconciliation.partials.recent_activity', ['health' => $health])
                </div>
            </div>
        @endif

        @if($canRepairPaymentMappings && ($paymentMappingRepair['repairable_count'] ?? 0) > 0)
            <div class="modal fade" id="payment-account-repair-review" tabindex="-1" role="dialog" aria-labelledby="payment-account-repair-title" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="payment-account-repair-title">{{ __('db.accounting_health_payment_mapping_review_title') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('db.Close') }}"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <p>{{ __('db.accounting_health_payment_mapping_review_intro') }}</p>
                            @foreach($paymentMappingRepair['repairable'] as $item)
                                <div class="border rounded p-3 mb-2">
                                    <strong>{{ $item['account_name'] }} (ID {{ $item['account_id'] }})</strong>
                                    <div class="health-muted">
                                        &rarr; {{ __('db.accounting_health_payment_mapping_create') }}
                                        <strong>{{ $item['expected']['code'] }} - {{ $item['expected']['name'] }}</strong>
                                        @if($item['expected']['parent_code'])
                                            &rarr; {{ $item['expected']['parent_code'] }} - {{ $item['expected']['parent_name'] }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @if(!empty($paymentMappingRepair['ambiguous']))
                                <h6 class="mt-3">{{ __('db.accounting_health_payment_mapping_ambiguous_title') }}</h6>
                                @foreach($paymentMappingRepair['ambiguous'] as $item)
                                    <div class="alert alert-warning py-2">{{ $item['account_name'] }} (ID {{ $item['account_id'] }}): {{ $item['reason'] }}</div>
                                @endforeach
                            @endif
                            <p class="mb-0 mt-3"><strong>{{ __('db.accounting_health_payment_mapping_preserve_notice') }}</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                            <form method="POST" action="{{ route('accounting.reconciliation.repair-payment-account-mappings') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">{{ __('db.accounting_health_action_apply_safe_repair') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($canViewTechnicalDetails)
            <div class="card mb-4">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">{{ __('db.accounting_health_technical_details_title') }}</h4>
                        <p class="health-muted mb-0">{{ __('db.accounting_health_technical_details_intro') }}</p>
                    </div>
                    <button class="btn btn-outline-secondary mt-3 mt-md-0" type="button" data-toggle="collapse" data-target="#technical-details" aria-expanded="{{ $technicalExpanded ? 'true' : 'false' }}" aria-controls="technical-details">
                        {{ __('db.accounting_health_action_show_technical_details') }}
                    </button>
                </div>
                <div id="technical-details" class="collapse {{ $technicalExpanded ? 'show' : '' }}">
                    <div class="card-body">
                        @if($certification)
                            <h5>{{ __('db.accounting_health_certification_output_title') }}</h5>
                            <p class="health-muted">
                                {{ __('db.accounting_health_certification_output_intro') }}
                                <code>accounting:certify --skip-regressions</code>
                            </p>
                            @if(filled($certification['output'] ?? null))
                                <pre class="health-technical-output">{{ $certification['output'] }}</pre>
                            @else
                                <div class="alert alert-light border mb-0">
                                    {{ __('db.accounting_health_certification_output_unavailable') }}
                                </div>
                            @endif
                        @else
                            <div class="alert alert-info">
                                {{ __('db.accounting_health_certification_output_empty') }}
                            </div>
                        @endif

                        <hr>

                        <h5 class="mb-3">{{ __('db.accounting_health_reconciliation_queue_title') }}</h5>
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary text-center">
                                    <div class="card-body">
                                        <h6>{{ __('db.accounting_health_total_queued') }}</h6>
                                        <h3>{{ $stats['total'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-success text-center">
                                    <div class="card-body">
                                        <h6>{{ __('db.accounting_health_queue_status_posted') }}</h6>
                                        <h3>{{ $stats['posted'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-warning text-center">
                                    <div class="card-body">
                                        <h6>{{ __('db.accounting_health_queue_status_pending') }}</h6>
                                        <h3>{{ $stats['pending'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card border-danger text-center">
                                    <div class="card-body">
                                        <h6>{{ __('db.accounting_health_queue_status_failed') }}</h6>
                                        <h3>{{ $stats['failed'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="accounting-reconciliation-table" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.accounting_health_table_source') }}</th>
                                        <th>{{ __('db.accounting_health_table_id') }}</th>
                                        <th>{{ __('db.accounting_health_table_status') }}</th>
                                        <th>{{ __('db.accounting_health_table_attempts') }}</th>
                                        <th>{{ __('db.accounting_health_table_last_error') }}</th>
                                        <th>{{ __('db.accounting_health_table_last_attempt') }}</th>
                                        <th>{{ __('db.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($queue as $q)
                                        <tr>
                                            <td>{{ class_basename($q->source_type) }}</td>
                                            <td>{{ $q->source_id }}</td>
                                            <td>
                                                @if($q->status == 'posted')
                                                    <div class="badge badge-success">{{ __('db.accounting_health_queue_status_posted') }}</div>
                                                @elseif($q->status == 'failed')
                                                    <div class="badge badge-danger">{{ __('db.accounting_health_queue_status_failed') }}</div>
                                                @elseif($q->status == 'reversed')
                                                    <div class="badge badge-secondary">{{ __('db.accounting_health_queue_status_reversed') }}</div>
                                                @else
                                                    <div class="badge badge-warning">{{ __('db.accounting_health_queue_status_pending') }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $q->attempts }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($q->last_error, 50) }}</td>
                                            <td>{{ $q->last_attempt_at }}</td>
                                            <td>
                                                @if($q->status == 'failed' || $q->status == 'pending')
                                                    <form action="{{ route('accounting.reconciliation.retry', $q->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary">{{ __('db.accounting_health_action_retry_transaction') }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $queue->links() }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-light border">
                {{ __('db.accounting_health_technical_details_hidden') }}
            </div>
        @endif
    </div>
</section>
@endsection
