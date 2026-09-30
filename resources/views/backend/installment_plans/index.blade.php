@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<section>
    <div class="container-fluid">
        <h4 class="mb-3">Instalment List</h4>
        <div class="table-responsive">
            <table id="installment-table" class="table table-hover">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>Plan Name</th>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Next Due Date</th>
                        <th class="not-exported">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($plans as $plan)
                    @php
                        $paid_count = $plan->installments->where('status', 'completed')->count();
                        $total_count = $plan->installments->count();
                        $next_installment = $plan->installments->where('status', 'pending')->sortBy('payment_date')->first();
                        $next_date = $next_installment ? date(config('date_format'), strtotime($next_installment->payment_date)) : 'N/A';
                    @endphp
                    <tr>
                        <td></td>
                        <td>{{$plan->name}}</td>
                        <td>{{$plan->reference->reference_no ?? 'N/A'}}</td>
                        <td>{{$plan->reference->customer->name ?? 'N/A'}}</td>
                        <td>{{number_format($plan->total_amount, config('decimal'))}}</td>
                        <td>{{$paid_count}} / {{$total_count}}</td>
                        <td>{{$total_count - $paid_count}}</td>
                        <td>{{$next_date}}</td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action
                                    <span class="caret"></span>
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                    <li>
                                        <a href="{{route('installmentplan.show', $plan->id)}}" class="btn btn-link"><i class="ti ti-list"></i> View Details</a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $('#installment-table').DataTable({
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
