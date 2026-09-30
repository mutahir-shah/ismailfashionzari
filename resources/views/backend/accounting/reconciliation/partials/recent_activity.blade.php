<div class="card health-activity-card">
    <div class="card-body">
        <h4 class="mb-3">{{ __('db.accounting_health_recent_activity_title') }}</h4>

        @forelse($health['recent_activity'] as $activity)
            <div class="health-timeline-item">
                <strong>{{ __('db.' . $activity['label_key'], $activity['label_params'] ?? []) }}</strong>
                <div class="health-muted">{{ __('db.' . $activity['detail_key'], $activity['detail_params'] ?? []) }}</div>
                <small class="health-muted">{{ $activity['time'] ?: __('db.accounting_health_time_unavailable') }}</small>
            </div>
        @empty
            <p class="health-muted mb-0">{{ __('db.accounting_health_recent_activity_empty') }}</p>
        @endforelse
    </div>
</div>
