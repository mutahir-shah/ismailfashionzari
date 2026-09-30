@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
{{-- resources/views/repair/service/index.blade.php --}}
@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<style>
    .btn-icon i{margin-right:5px}
    .top-fields{margin-top:10px;position:relative}
    .top-fields label{font-size:11px;font-weight:600;margin-left:10px;padding:0 3px;position:absolute;top:-8px;z-index:9}
    .top-fields input,.top-fields select{font-size:13px;height:45px}
    .badge-device{background:#17a2b8;color:#fff}
    .badge-vehicle{background:#ffc107;color:#212529}
    .status-pending{background:#ffc107;color:#212529}
    .status-in_progress{background:#17a2b8;color:#fff}
    .status-completed{background:#28a745;color:#fff}
    .status-delivered{background:#6f42c1;color:#fff}
    .status-cancelled{background:#dc3545;color:#fff}
    .priority-low{background:#28a745}
    .priority-medium{background:#ffc107;color:#212529}
    .priority-high{background:#dc3545}
    .priority-urgent{background:#6f42c1}
</style>

<x-success-message key="message"/>
<x-error-message key="not_permitted"/>

<section>
    <div class="container-fluid">
        @can('repair.service.create')
            <a href="{{route('repair.service.create')}}" class="btn btn-info add-service-btn btn-icon">
                <i class="ti ti-plus"></i> {{__('db.Add Service Job')}}
            </a>&nbsp;
        @endcan

        <button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
            <i class="ti ti-filter"></i> {{__('db.filter_jobs')}}
        </button>

        {{-- Filter Section --}}
        <div class="card mt-3 mb-2">
            <div class="card-body" id="filter-card" style="display:none">
                <form id="filter-form">
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{__('db.date')}}</label>
                                <input type="text" class="daterangepicker-field form-control" value="{{$starting_date}} To {{$ending_date}}" required/>
                                <input type="hidden" name="starting_date" value="{{$starting_date}}"/>
                                <input type="hidden" name="ending_date" value="{{$ending_date}}"/>
                            </div>
                        </div>
                        <div class="col-md-3 @if(\Auth::user()->role_id>2){{'d-none'}}@endif">
                            <div class="form-group top-fields">
                                <label>{{__('db.Warehouse')}}</label>
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true">
                                    <option value="0">{{__('db.All Warehouse')}}</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{__('db.Status')}}</label>
                                <select id="status-filter" class="form-control" name="status">
                                    <option value="">{{__('db.All')}}</option>
                                    <option value="pending">{{__('db.Pending')}}</option>
                                    <option value="in_progress">{{__('db.In Progress')}}</option>
                                    <option value="waiting_parts">{{__('db.waiting_parts')}}</option>
                                    <option value="completed">{{__('db.Completed')}}</option>
                                    <option value="delivered">{{__('db.Delivered')}}</option>
                                    <option value="cancelled">{{__('db.Cancelled')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{__('db.service_type')}}</label>
                                <select id="service-type-filter" class="form-control" name="service_type">
                                    <option value="">{{__('db.All')}}</option>
                                    <option value="device">{{__('db.device_repair')}}</option>
                                    <option value="vehicle">{{__('db.vehicle_repair')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{__('db.Payment Status')}}</label>
                                <select id="payment-status-filter" class="form-control" name="payment_status">
                                    <option value="">{{__('db.All')}}</option>
                                    <option value="pending">{{__('db.Pending')}}</option>
                                    <option value="partial">{{__('db.Partial')}}</option>
                                    <option value="paid">{{__('db.Paid')}}</option>
                                </select>
                            </div>
                        </div>
                        <div id="filter-loading" class="col-12 text-center my-2" style="display:none">
                            <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                            <span>{{__('db.Loading...')}}</span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12 text-right">
                            <button type="button" class="btn btn-secondary btn-sm" id="clear-filter">
                                <i class="ti ti-x"></i> {{__('db.Clear')}}
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="apply-filter">
                                <i class="ti ti-checkmark"></i> {{__('db.apply')}}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="table-responsive">
        <table id="service-table" class="table service-list" style="width:100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th class="not-exported">{{__('db.action')}}</th>
                    <th>{{__('db.date')}}</th>
                    <th>{{__('db.invoice_no')}}</th>
                    <th>{{__('db.customer')}}</th>
                    <th>{{__('db.service_type')}}</th>
                    <th>{{__('db.Title')}}</th>
                    <th>{{__('db.Status')}}</th>
                    <th>{{__('db.priority')}}</th>
                    <th>{{__('db.Warehouse')}}</th>
                    <th>{{__('db.technician')}}</th>
                    <th>{{__('db.parts_total')}}</th>
                    <th>{{__('db.service_charge')}}</th>
                    <th>{{__('db.Discount')}}</th>
                    <th>{{__('db.Tax')}}</th>
                    <th>{{__('db.grand total')}}</th>
                    <th>{{__('db.Paid')}}</th>
                    <th>{{__('db.Due')}}</th>
                    <th>{{__('db.expected_date')}}</th>
                </tr>
            </thead>
            <tfoot class="tfoot active">
                <th></th><th>{{__('db.Total')}}</th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th class="total-grand"></th><th class="total-paid"></th><th class="total-due"></th><th></th>
            </tfoot>
        </table>
    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script>
    $('#toggle-filter').on('click',function(){$('#filter-card').slideToggle('slow')});
    $('#clear-filter').on('click',function(){
        $('#warehouse_id,#status-filter,#service-type-filter,#payment-status-filter').val('').trigger('change');
        $('input[name=starting_date]').val(starting_date);
        $('input[name=ending_date]').val(ending_date);
        $('.daterangepicker-field').val(starting_date+' To '+ending_date);
        serviceTable.ajax.reload();
    });
    $('#apply-filter').on('click',function(){serviceTable.ajax.reload()});

    $("ul#repair").siblings('a').attr('aria-expanded','true');
    $("ul#repair").addClass("show");
    $("ul#repair #service-list-menu").addClass("active");

    var serviceTable = $('#service-table').DataTable({
        "processing":true,"serverSide":true,
        "ajax":{
            url:"{{route('repair.service.data')}}",type:"POST",
            data:function(d){
                d.starting_date=$('input[name=starting_date]').val();
                d.ending_date=$('input[name=ending_date]').val();
                d.warehouse_id=$('#warehouse_id').val();
                d.status=$('#status-filter').val();
                d.service_type=$('#service-type-filter').val();
                d.payment_status=$('#payment-status-filter').val();
            }
        },
        "columns":[
            {"data":"key","className":"not-exported"},
            {"data":"options","className":"not-exported","orderable":false},
            {"data":"date"},{"data":"reference_no"},
            {"data":"customer"},{"data":"service_type"},{"data":"title"},
            {"data":"status"},{"data":"priority"},{"data":"warehouse"},
            {"data":"technician"},{"data":"parts_total"},{"data":"service_charge"},
            {"data":"discount"},{"data":"tax"},{"data":"total_amount"},
            {"data":"paid_amount"},{"data":"due_amount"},{"data":"expected_date"}
        ],
        "order":[[2,'desc']],
        "language":{
            'lengthMenu':'_MENU_ {{__("db.records per page")}}',
            "info":'<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":'{{__("db.Search")}}',
            'paginate':{'previous':'<i class="ti ti-chevron-left"></i>','next':'<i class="ti ti-chevron-right"></i>'}
        },
        "columnDefs":[
            {"orderable":false,"targets":[0,1]},
            {'render':function(data,type,row){if(type==='display')data='<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';return data;},'checkboxes':{'selectRow':true},'targets':[0]}
        ],
        'select':{style:'multi',selector:'td:first-child'},
        'lengthMenu':[[10,25,50,-1],[10,25,50,"All"]],
        dom:'<"row"lfB>rtip',
        buttons:[
            {extend:"pdf",text:'<i class="ti ti-file-type-pdf"></i>',exportOptions:{columns:":visible:Not(.not-exported)"},footer:true},
            {extend:"excel",text:'<i class="ti ti-file-type-xls"></i>',exportOptions:{columns:":visible:Not(.not-exported)"},footer:true},
            {extend:"csv",text:'<i class="ti ti-file-type-csv"></i>',exportOptions:{columns:":visible:Not(.not-exported)"},footer:true},
            {extend:"print",text:'<i class="ti ti-printer"></i>',exportOptions:{columns:":visible:Not(.not-exported)"},footer:true},
            {extend:'colvis',text:'<i class="ti ti-eye"></i>',columns:':gt(0)'}
        ],
        "footerCallback":function(row,data,start,end,display){
            var api=this.api(),grandTotal=0,paidTotal=0,dueTotal=0;
            api.rows({page:'current'}).every(function(){
                var d=this.data();
                grandTotal+=parseFloat(d.total_amount.replace(/,/g,''))||0;
                paidTotal+=parseFloat(d.paid_amount.replace(/,/g,''))||0;
                dueTotal+=parseFloat(d.due_amount.replace(/,/g,''))||0;
            });
            $(api.column(15).footer()).html(grandTotal.toFixed({{config('decimal',2)}}));
            $(api.column(16).footer()).html(paidTotal.toFixed({{config('decimal',2)}}));
            $(api.column(17).footer()).html(dueTotal.toFixed({{config('decimal',2)}}));
        },
        "createdRow":function(row,data){$(row).addClass('service-link').attr('data-job',data['job_data'])}
    });

    $('#warehouse_id,#status-filter,#service-type-filter,#payment-status-filter').on('change',function(){serviceTable.ajax.reload()});

    $('.daterangepicker-field').daterangepicker({locale:{format:'YYYY-MM-DD'},startDate:starting_date,endDate:ending_date},function(start,end){
        $('input[name=starting_date]').val(start.format('YYYY-MM-DD'));
        $('input[name=ending_date]').val(end.format('YYYY-MM-DD'));
        serviceTable.ajax.reload();
    });

    function confirmDelete(){return confirm('{{__("db.are_you_sure")}}')}
</script>
<script src="{{asset($asset_prefix . 'vendor/bootstrap-daterangepicker/daterangepicker.min.js')}}"></script>
<link rel="stylesheet" href="{{asset($asset_prefix . 'vendor/bootstrap-daterangepicker/daterangepicker.css')}}">
@endpush
