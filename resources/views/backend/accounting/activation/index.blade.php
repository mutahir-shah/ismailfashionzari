@extends('backend.layout.main')
@section('content')

@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

@include('backend.accounting.activation.wizard')

@if(false)
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>Initialize Double-Entry Accounting</h4>
                    </div>
                    <div class="card-body">
                        <p>Set up your accounting foundation. This process only needs to be completed once.</p>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Readiness Checklist</h5>
                                <ul>
                                    @foreach($checklist as $check => $status)
                                        <li>
                                            @if($status)
                                                <i class="fa fa-check text-success"></i> 
                                            @else
                                                <i class="fa fa-times text-danger"></i>
                                            @endif
                                            {{ str_replace('_', ' ', ucfirst($check)) }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Activation Mode: <strong>{{ $mode == 'existing_business' ? 'Existing Business (Opening Balance Required)' : 'New Business (Clean Slate)' }}</strong></h5>
                                @if($mode == 'existing_business')
                                    <p class="text-info">Because you have existing operational data, we will generate an Opening Balance Journal to reflect your current Accounts Receivable, Accounts Payable, Inventory, and Cash balances.</p>
                                    
                                    <div class="table-responsive mt-3">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Account Type</th>
                                                    <th>Calculated Balance</th>
                                                    <th>Action in Journal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Accounts Receivable</td>
                                                    <td>{{ number_format($balances['ar'], 2) }}</td>
                                                    <td>{{ $balances['ar'] >= 0 ? 'Debit' : 'Credit' }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Accounts Payable</td>
                                                    <td>{{ number_format($balances['ap'], 2) }}</td>
                                                    <td>{{ $balances['ap'] >= 0 ? 'Credit' : 'Debit' }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Inventory Value</td>
                                                    <td>{{ number_format($balances['inventory'], 2) }}</td>
                                                    <td>Debit</td>
                                                </tr>
                                                <tr>
                                                    <td>Cash & Bank (Total)</td>
                                                    <td>{{ number_format($balances['cash_bank'], 2) }}</td>
                                                    <td>{{ $balances['cash_bank'] >= 0 ? 'Debit' : 'Credit' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Equity (Balancing Figure)</strong></td>
                                                    <td><strong>{{ number_format($balances['equity'], 2) }}</strong></td>
                                                    <td>Credit</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-info">Since this is a fresh setup with no historical data, accounting will be activated without any opening balances.</p>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('accounting.activation.activate') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary" {{ in_array(false, $checklist, true) ? 'disabled' : '' }}>
                                <i class="fa fa-power-off"></i> Activate Accounting Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

@endsection
