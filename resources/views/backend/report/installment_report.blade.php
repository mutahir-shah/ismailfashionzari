@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<section>
    <div class="container-fluid">
        <h4 class="mb-3">Instalment Report</h4>
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{route('report.installment')}}" method="GET" id="report-filter">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control selectpicker">
                                    <option value="">All Plans</option>
                                    <option value="pending" @if($status == 'pending') selected @endif>Pending Payments</option>
                                    <option value="overdue" @if($status == 'overdue') selected @endif>Missing (Overdue) Payments</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="installment-report-table" class="table table-hover">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Total Amount</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Overdue Amount</th>
                    <th>Next Due Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                @php
                    $installments = $plan->installments;
                    $paid_count = $installments->where('status', 'paid')->count();
                    $total_count = $installments->count();
                    $overdue_amount = $installments->where('status', 'pending')->where('payment_date', '<', date('Y-m-d'))->sum('amount');
                    $next_installment = $installments->where('status', 'pending')->sortBy('payment_date')->first();
                    $next_date = $next_installment ? date(config('date_format'), strtotime($next_installment->payment_date)) : 'N/A';
                @endphp
                <tr>
                    <td></td>
                    <td>{{$plan->reference->reference_no ?? 'N/A'}}</td>
                    <td>{{$plan->reference->customer->name ?? 'N/A'}}</td>
                    <td>{{number_format($plan->total_amount, config('decimal'))}}</td>
                    <td>{{$paid_count}} / {{$total_count}}</td>
                    <td>{{$total_count - $paid_count}}</td>
                    <td class="@if($overdue_amount > 0) text-danger font-weight-bold @endif">{{number_format($overdue_amount, config('decimal'))}}</td>
                    <td>{{$next_date}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $('#installment-report-table').DataTable({
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    });
</script>
@endpush

@endsection
