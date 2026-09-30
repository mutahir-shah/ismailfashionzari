@extends('backend.layout.main')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h4>Delivery Status</h4>
        </div>
        <div class="card-body">
            <h5>Status Code: {{ $status }}</h5>
            <p><strong>Status Label:</strong> {{ $delivery_label }}</p>
        </div>
    </div>
</div>
@endsection
