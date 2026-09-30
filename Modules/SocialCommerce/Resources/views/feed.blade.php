@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<style>
    .sc-feed-head h4 { margin: 0; font-size: 1.1rem; font-weight: 600; }
    .sc-feed-url { max-width: 860px; }
    .sc-feed-panel { border: 1px solid #eef0f4; border-radius: 6px; padding: 18px; background: #fbfcff; }
    .sc-feed-panel h5, .sc-feed-main h5 { font-size: .95rem; font-weight: 600; margin-bottom: 14px; }
    .sc-feed-stat { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #eef0f4; }
    .sc-feed-stat:last-child { border-bottom: 0; }
    .sc-feed-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .sc-feed-error { font-size: 12px; word-break: break-word; }
    @media (max-width: 767px) {
        .sc-feed-actions .btn, .sc-feed-url .btn { width: 100%; }
        .sc-feed-url .input-group-append { width: 100%; margin-top: 8px; }
    }
</style>

<section>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center sc-feed-head">
                        <h4>{{ __('db.Meta Product Catalog Feed') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 sc-feed-main">
                                <h5>{{ __('db.Your Feed URL') }}</h5>
                                <div class="input-group mb-4 sc-feed-url">
                                    <input type="text" class="form-control" value="{{ $feedUrl }}" readonly id="feed_url">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" onclick="document.getElementById('feed_url').select(); document.execCommand('copy'); alert(@json(__('db.Copied to clipboard') . '!'));"><i class="ti ti-copy"></i> {{ __('db.Copy URL') }}</button>
                                    </div>
                                </div>

                                <h5>{{ __('db.Instructions for Meta Commerce Manager') }}</h5>
                                <ol class="text-muted">
                                    <li>{{ __('db.Go to your Commerce Manager in Facebook') }}.</li>
                                    <li>{{ __('db.Select your Catalog, then go to Data Sources') }}.</li>
                                    <li>{{ __('db.Choose Data Feed and select Scheduled feed') }}.</li>
                                    <li>{{ __('db.Paste your Feed URL copied from above') }}.</li>
                                    <li>{{ __('db.Leave username/password blank because the URL contains a secure token') }}.</li>
                                    <li>{{ __('db.Set your schedule and trigger the first upload') }}.</li>
                                </ol>

                                <div class="sc-feed-actions mt-4 pt-4 border-top">
                                    <form action="{{ route('socialcommerce.feed.refresh') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary" {{ $settings->feed_pending_refresh ? 'disabled' : '' }}>
                                            <i class="ti ti-reload"></i> {{ __('db.Queue Refresh Now') }}
                                        </button>
                                    </form>
                                    <form action="{{ route('socialcommerce.feed.regenerate') }}" method="POST">
                                    @csrf
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm(@json(__('db.Are you sure you want to regenerate the feed token? Existing connections will break') . '.'))"><i class="ti ti-key"></i> {{ __('db.Regenerate Token') }}</button>
                                    </form>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="sc-feed-panel">
                                    <h5>{{ __('db.Feed Statistics') }}</h5>
                                    <div class="sc-feed-stat">
                                        <span>{{ __('db.Status') }}</span>
                                        @if($settings->is_active)
                                            <span class="badge badge-success">{{ __('db.Active') }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ __('db.Module Disabled') }}</span>
                                        @endif
                                    </div>
                                    <div class="sc-feed-stat">
                                        <span>{{ __('db.Sync Status') }}</span>
                                        @if($settings->feed_pending_refresh)
                                            <span class="badge badge-warning">{{ __('db.Pending Refresh') }}</span>
                                        @else
                                            <span class="badge badge-success">{{ __('db.Synced') }}</span>
                                        @endif
                                    </div>
                                    <div class="sc-feed-stat">
                                        <span>{{ __('db.Last Generated') }}</span>
                                        <strong>{{ $settings->feed_last_generated ? \Carbon\Carbon::parse($settings->feed_last_generated)->diffForHumans() : __('db.Never') }}</strong>
                                    </div>
                                    @if($settings->feed_last_failed)
                                    <div class="sc-feed-stat text-danger">
                                        <span>{{ __('db.Last Failed') }}</span>
                                        <strong>{{ \Carbon\Carbon::parse($settings->feed_last_failed)->diffForHumans() }}</strong>
                                    </div>
                                    @endif
                                    <div class="sc-feed-stat">
                                        <span>{{ __('db.Included Products') }}</span>
                                        <span class="badge badge-primary badge-pill">{{ $includedCount }}</span>
                                    </div>
                                    <div class="sc-feed-stat">
                                        <span>{{ __('db.Excluded Products') }}</span>
                                        <span class="badge badge-secondary badge-pill">{{ $excludedCount }}</span>
                                    </div>
                                    @if($settings->feed_latest_error)
                                    <div class="mt-3 alert alert-danger sc-feed-error">
                                        <strong>{{ __('db.Last Error') }}:</strong><br>
                                        {{ $settings->feed_latest_error }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
