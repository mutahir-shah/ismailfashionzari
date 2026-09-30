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
                    <h3 class="text-center mt-2">{{__('db.Cash Flow Statement')}}</h3>
                    <button class="btn btn-primary btn-icon" onclick="window.print();" type="button" style="position: absolute; top: 15px; right: 15px"><i class="ti ti-printer"></i></button>
                    <div class="row mt-4">
                        <div class="col-md-4 offset-md-2">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Warehouse') }}</label>
                                <select name="warehouse_id" class="form-control selectpicker" data-live-search="true">
                                    <option value="">{{ __('db.All Warehouse') }}</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{ isset($warehouse_id) && $warehouse_id == $warehouse->id ? 'selected' : '' }}>{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group top-fields">
                                <label>{{__('db.date')}}</label>
                                <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                <input type="hidden" name="start_date" value="{{$start_date}}" />
                                <input type="hidden" name="end_date" value="{{$end_date}}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="report-content" class="table-responsive px-4 mb-5 pb-5">
        @if($warehouse_scope_warning)
            <div class="alert alert-warning">{{ __('db.cash_flow_company_scope_warning') }}</div>
        @endif
        @if(count($classification_fallbacks) > 0)
            <div class="alert alert-warning">{{ __('db.cash_flow_classification_fallback_warning') }}</div>
        @endif
        @if(count($invalid_cash_accounts) > 0)
            <div class="alert alert-danger">{{ __('db.cash_flow_invalid_cash_scope_warning') }}</div>
        @endif
        <table class="table table-bordered table-striped dataTable">
            <tbody>
                <tr class="bg-light">
                    <th><strong>{{__('db.Opening Cash')}}</strong></th>
                    <th class="text-right"><strong>{{ number_format($opening_cash, 2) }}</strong></th>
                </tr>
                
                <tr>
                    <th colspan="2" class="bg-primary text-white"><strong>{{__('db.Operating Activities')}}</strong></th>
                </tr>
                @if(count($operating) > 0)
                    @foreach($operating as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="text-center">{{__('db.No data available')}}</td>
                    </tr>
                @endif
                <tr class="bg-light">
                    <td><strong>{{__('db.Net Cash from Operating Activities')}}</strong></td>
                    <td class="text-right"><strong>{{ number_format($net_operating_cash, 2) }}</strong></td>
                </tr>

                <tr>
                    <th colspan="2" class="bg-info text-white"><strong>{{__('db.Investing Activities')}}</strong></th>
                </tr>
                @if(count($investing) > 0)
                    @foreach($investing as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="text-center">{{__('db.No data available')}}</td>
                    </tr>
                @endif
                <tr class="bg-light">
                    <td><strong>{{__('db.Net Cash from Investing Activities')}}</strong></td>
                    <td class="text-right"><strong>{{ number_format($net_investing_cash, 2) }}</strong></td>
                </tr>

                <tr>
                    <th colspan="2" class="bg-warning text-white"><strong>{{__('db.Financing Activities')}}</strong></th>
                </tr>
                @if(count($financing) > 0)
                    @foreach($financing as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="text-center">{{__('db.No data available')}}</td>
                    </tr>
                @endif
                <tr class="bg-light">
                    <td><strong>{{__('db.Net Cash from Financing Activities')}}</strong></td>
                    <td class="text-right"><strong>{{ number_format($net_financing_cash, 2) }}</strong></td>
                </tr>

                <tr class="bg-secondary text-white">
                    <th><strong>{{__('db.Net Change in Cash')}}</strong></th>
                    <th class="text-right"><strong>{{ number_format($net_change_cash, 2) }}</strong></th>
                </tr>
                <tr class="bg-success text-white">
                    <th><strong>{{__('db.Closing Cash')}}</strong></th>
                    <th class="text-right"><strong>{{ number_format($closing_cash, 2) }}</strong></th>
                </tr>
            </tbody>
        </table>

        <table class="table table-bordered mt-4">
            <thead>
                <tr class="bg-light">
                    <th colspan="2">{{ __('db.cash_flow_cash_equivalents_reconciliation') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cash_accounts as $account)
                    <tr>
                        <td>{{ $account->name }} [{{ $account->code }}]</td>
                        <td class="text-right">{{ number_format($account->balance, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-light">
                    <th>{{ __('db.Closing Cash') }}</th>
                    <th class="text-right">{{ number_format($closing_cash, 2) }}</th>
                </tr>
                <tr>
                    <td>{{ __('db.cash_flow_reconciliation_variance') }}</td>
                    <td class="text-right">{{ number_format($reconciliation_variance, 2) }}</td>
                </tr>
            </tbody>
        </table>
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
