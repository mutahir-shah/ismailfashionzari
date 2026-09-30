@extends('backend.layout.main')
@push('css')
<style type="text/css">
    .top-fields { margin-top:10px; position: relative; }
    .top-fields label { font-size:11px; font-weight:600; margin-left:10px; padding:0 3px; position:absolute; top:-8px; z-index:9; background-color: white; }
    .top-fields input, .top-fields select { font-size:13px; height:36px}
</style>
@endpush
@section('content')
<section>
    <div class="container-fluid">
        <form id="filter-form">
            <div class="card mt-3 mb-2" id="filter-card">
                <div class="card-body">
                    <h3 class="text-center mt-2">{{__('db.General Ledger')}}</h3>
                    <button class="btn btn-primary btn-icon" onclick="window.print();" type="button" style="position: absolute; top: 15px; right: 15px"><i class="ti ti-printer"></i></button>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="form-group top-fields">
                                <label>{{__('db.Account')}}</label>
                                <select name="account_id" class="selectpicker form-control" data-live-search="true" title="Select Account...">
                                    @foreach($accounts as $account)
                                        <option value="{{$account->id}}" {{$selectedAccount && $selectedAccount->id == $account->id ? 'selected' : ''}}>
                                            {{$account->code}} - {{$account->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Warehouse') }}</label>
                                <select name="warehouse_id" class="form-control selectpicker" data-live-search="true">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{ $warehouseId == $warehouse->id ? 'selected' : '' }}>{{$warehouse->name}}</option>
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

        <div id="report-content">
            <div class="table-responsive mb-5 pb-5">
                <div class="dataTables_wrapper">
                    <table id="general-ledger-table" class="table table-hover dataTable">
                        <thead>
                            <tr>
                                <th>{{__('db.Date')}}</th>
                                <th>{{__('db.Reference')}}</th>
                                <th>Event Type</th>
                                <th>{{__('db.Description')}}</th>
                                <th>{{__('db.Debit')}}</th>
                                <th>{{__('db.Credit')}}</th>
                                <th>{{__('db.Balance')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-right"><strong>Opening Balance</strong></td>
                                <td>
                                    <strong>{{number_format((float)$openingBalance, 2, '.', '')}}</strong>
                                </td>
                            </tr>
                            @foreach($lines as $line)
                            <tr>
                                <td>{{ date('Y-m-d', strtotime($line->date)) }}</td>
                                <td>{{$line->reference_no}}</td>
                                <td>{{ucwords(str_replace('_', ' ', $line->event_type))}}</td>
                                <td>{{$line->description}}</td>
                                <td>{{number_format((float)$line->debit, 2, '.', '')}}</td>
                                <td>{{number_format((float)$line->credit, 2, '.', '')}}</td>
                                <td>{{number_format((float)$line->running_balance, 2, '.', '')}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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

        $('select[name="account_id"], select[name="warehouse_id"]').on('change', function() {
            loadReport();
        });

        $('.daterangepicker-field').on('apply.daterangepicker', function (ev, picker) {
            $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
            $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));
            loadReport();
        });
    });
</script>
@endpush
@endsection
