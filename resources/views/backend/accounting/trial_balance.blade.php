@extends('backend.layout.main')
@push('css')
<style type="text/css">
    .top-fields { margin-top:10px; position: relative; }
    .top-fields label { font-size:11px; font-weight:600; margin-left:10px; padding:0 3px; position:absolute; top:-8px; z-index:9; background-color: white; }
    .top-fields input { font-size:13px; height:36px; }
</style>
@endpush
@section('content')
<section>
    <div class="container-fluid">
        <form id="filter-form">
            <div class="card mt-3 mb-2" id="filter-card">
                <div class="card-body">
                    <h3 class="text-center mt-2">{{__('db.Trial Balance')}}</h3>
                    <button class="btn btn-primary btn-icon" onclick="window.print();" type="button" style="position: absolute; top: 15px; right: 15px"><i class="ti ti-printer"></i></button>
                    <div class="row mt-5">
                        <div class="col-md-4 offset-md-2">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Warehouse') }}</label>
                                <select name="warehouse_id" class="form-control selectpicker" data-live-search="true">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{ isset($warehouse_id) && $warehouse_id == $warehouse->id ? 'selected' : '' }}>{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group top-fields">
                                <label>{{__('db.date')}}</label>
                                <input type="text" class="daterangepicker-field form-control" value="{{$startDate}} To {{$endDate}}" required />
                                <input type="hidden" name="start_date" value="{{$startDate}}" />
                                <input type="hidden" name="end_date" value="{{$endDate}}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div id="report-content" class="table-responsive mb-5 pb-5">
            <div class="dataTables_wrapper">
                
                <table id="trial-balance-table" class="table table-hover dataTable">
                    <thead>
                        <tr>
                            <th>{{__('db.Account No')}}</th>
                            <th>{{__('db.Account Name')}}</th>
                            <th>{{__('db.Type')}}</th>
                            <th>{{__('db.Total Debit')}}</th>
                            <th>{{__('db.Total Credit')}}</th>
                            <th>{{__('db.Balance')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($balances as $balance)
                        <tr>
                            <td>{{$balance->account_no}}</td>
                            <td>{{$balance->name}}</td>
                            <td>{{ucwords($balance->type)}}</td>
                            <td>{{number_format((float)$balance->total_debit, 2, '.', '')}}</td>
                            <td>{{number_format((float)$balance->total_credit, 2, '.', '')}}</td>
                            <td>
                                @if($balance->net_balance < 0)
                                    <span class="text-danger">{{number_format((float)$balance->net_balance, 2, '.', '')}}</span>
                                @else
                                    {{number_format((float)$balance->net_balance, 2, '.', '')}}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="tfoot active">
                        <tr>
                            <th colspan="3" class="text-center">{{__('db.Total')}}</th>
                            <th>{{number_format((float)$totalDebits, 2, '.', '')}}</th>
                            <th>{{number_format((float)$totalCredits, 2, '.', '')}}</th>
                            <th>
                                @if($difference != 0)
                                    <span class="text-danger">Out of Balance: {{number_format((float)$difference, 2, '.', '')}}</span>
                                @else
                                    <span class="text-success">Balanced (0.00)</span>
                                @endif
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {

        function loadReport() {
            $.ajax({
                url: window.location.pathname,
                type: "GET",
                data: $('#filter-form').serialize(),
                success: function(response) {
                    var newContent = $(response).find('#report-content').html();
                    $('#report-content').html(newContent);
                }
            });
        }

        $('.daterangepicker-field').on('apply.daterangepicker', function (ev, picker) {
            $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
            $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));
            loadReport();
        });

        $('select[name="warehouse_id"]').on('change', function() {
            loadReport();
        });
    });
</script>
@endpush
@endsection
