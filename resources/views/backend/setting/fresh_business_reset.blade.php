@extends('backend.layout.main')
@section('content')
<section class="container-fluid">
    <div class="card mt-3"><div class="card-body">
        <h3>{{ __('db.fresh_reset_title') }}</h3>
        <div class="alert alert-danger"><strong>{{ __('db.fresh_reset_warning_title') }}</strong><br>{{ __('db.fresh_reset_warning') }}</div>
        <div class="alert alert-warning">
            <strong>{{ __('db.Warnings') }}:</strong> {{ __('db.fresh_reset_backup_warning') }}
        </div>
    </div></div>
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('setting.freshBusinessReset.store') }}" id="resetBusinessForm">@csrf
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('db.fresh_reset_password_label') }}</label>
                <input type="password" class="form-control" name="password" autocomplete="off" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">{!! __('db.fresh_reset_phrase_label') !!}</label>
                <input type="text" class="form-control" name="confirmation" autocomplete="off" required placeholder="{{ __('db.fresh_reset_phrase_placeholder') }}">
            </div>
            <button type="submit" class="btn btn-danger" onclick="return confirm('{{ __('db.fresh_reset_execute_confirmation') }}')">{{ __('db.fresh_reset_execute') }}</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><h4>{{ __('db.fresh_reset_manifest_history') }}</h4><table class="table"><tbody>
        @foreach($manifests as $manifest)<tr><td>{{ $manifest->reset_at }}</td><td>{{ __('db.fresh_reset_manifest_operator') }}: {{ $manifest->user_id }}</td><td>{{ __('db.fresh_reset_manifest_tables') }}: {{ count($manifest->affected_tables ?? []) }}</td></tr>@endforeach
    </tbody></table></div></div>
</section>
@endsection
