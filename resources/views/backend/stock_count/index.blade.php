@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <button class="btn btn-info" data-toggle="modal" data-target="#createModal"><i class="ti ti-plus"></i> {{__('db.Count Stock')}} </button>
    </div>
    <div class="table-responsive">
        <table id="stock-count-table" class="table stock-count-list">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.date')}}</th>
                    <th>{{__('db.reference')}}</th>
                    <th>{{__('db.Warehouse')}}</th>
                    <th>{{__('db.category')}}</th>
                    <th>{{__('db.Brand')}}</th>
                    <th>{{__('db.Type')}}</th>
                    <th class="not-exported">{{__('db.Initial File')}}</th>
                    <th class="not-exported">{{__('db.Final File')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_stock_count_all as $key => $stock_count)
                <?php
                    $warehouse = DB::table('warehouses')->find($stock_count->warehouse_id);
                    $category_name = [];
                    $brand_name = [];
                    $initial_file = 'stock_count/' . $stock_count->initial_file;
                    $final_file = 'stock_count/' . $stock_count->final_file;
                ?>
                <tr>
                    <td>{{$key}}</td>
                    <td>{{ date(gen_setting()->date_format, strtotime($stock_count->created_at->toDateString())) . ' '. $stock_count->created_at->toTimeString() }}</td>
                    <td>{{ $stock_count->reference_no }}</td>
                    <td>{{ $warehouse->name }}</td>
                    <td>
                        @if($stock_count->category_id)
                            @foreach(explode(",",$stock_count->category_id) as $cat_key=>$category_id)
                            @php
                                $category = \DB::table('categories')->find($category_id);
                                $category_name[] = $category->name;
                            @endphp
                                @if($cat_key)
                                    {{', ' . $category->name}}
                                @else
                                    {{$category->name}}
                                @endif
                            @endforeach
                        @endif
                    </td>
                    <td>
                        @if($stock_count->brand_id)
                            @foreach(explode(",",$stock_count->brand_id) as $brand_key=>$brand_id)
                            @php
                                $brand = \DB::table('brands')->find($brand_id);
                                $brand_name[] = $brand->title;
                            @endphp
                                @if($brand_key)
                                    {{', '.$brand->title}}
                                @else
                                    {{$brand->title}}
                                @endif
                            @endforeach
                        @endif
                    </td>
                    @if($stock_count->type == 'full')
                        @php $type = __('db.Full') @endphp
                        <td><div class="badge badge-primary">{{__('db.Full')}}</div></td>
                    @else
                        @php $type = __('db.Partial') @endphp
                        <td><div class="badge badge-info">{{__('db.Partial')}}</div></td>
                    @endif
                    <td class="text-center">
                        <a download href="{{'stock_count/'.$stock_count->initial_file}}" title="{{__('db.Download')}}"><i class="ti ti-copy"></i></a>
                    </td>
                    <td class="text-center">
                        @if($stock_count->final_file)
                        <a download href="{{'stock_count/'.$stock_count->final_file}}" title="{{__('db.Download')}}"><i class="ti ti-copy"></i></a>
                        @endif
                    </td>
                    <td>
                        @if($stock_count->final_file)
                            <div style="cursor: pointer;" class="badge badge-success final-report" data-stock_count='["{{date(gen_setting()->date_format, strtotime($stock_count->created_at->toDateString()))}}", "{{$stock_count->reference_no}}", "{{$warehouse->name}}", "{{$type}}", "{{implode(", ", $category_name)}}", "{{implode(", ", $brand_name)}}", "{{$initial_file}}", "{{$final_file}}", "{{$stock_count->id}}"]'>{{__('db.Final Report')}}
                            </div>
                        @else
                            <div style="cursor: pointer;" class="badge badge-primary finalize" data-id="{{$stock_count->id}}">{{__('db.Finalize')}}
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tfoot>
        </table>
    </div>
</section>

<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('stock-count.store') }}" method="post" enctype="multipart/form-data">
          @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Count Stock')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>{{__('db.Warehouse')}} *</label>
                    <select required name="warehouse_id" id="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                        @foreach($lims_warehouse_list as $warehouse)
                        <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                        @endforeach
                    </select>
                </div>
                {{-- <div class="col-md-6 form-group">
                    <label>{{__('db.Type')}} *</label>
                    <select class="form-control" name="type">
                        <option value="full">{{__('db.Full')}}</option>
                        <option value="partial">{{__('db.Partial')}}</option>
                    </select>
                </div> --}}
                <input type="hidden" value="partial" name="type">
                <div class="col-md-4 form-group" id="category">
                    <label>{{__('db.category')}}</label>
                    <select name="category_id[]" id="category_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Category..." multiple>
                        @foreach (get_active_categories() as $category)
                        <option value="{{$category->id}}">{{$category->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 form-group" id="brand">
                    <label>{{__('db.Brand')}}</label>
                    <select name="brand_id[]" id="brand_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Brand..." multiple>
                        @foreach($lims_brand_list as $brand)
                        <option value="{{$brand->id}}">{{$brand->title}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
              <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
            </div>
        </div>
        </form>
      </div>
    </div>
</div>

<div id="finalizeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('stock-count.finalize') }}" method="POST" enctype="multipart/form-data">
            @csrf
        <div class="modal-header">
            <h5 id="exampleModalLabel" class="modal-title"> {{__('db.Finalize Stock Count')}}</h5>
            <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
            <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.<strong>{{__('db.You just need to update the Counted column in the initial file')}}</strong> </small></p>
            <div class="form-group">
                <label>{{__('db.Upload File')}} *</label>
                <input required type="file" name="final_file" class="form-control" />
            </div>
            <input type="hidden" name="stock_count_id">
            <div class="form-group">
                <label>{{__('db.note')}}</label>
                <textarea rows="3" name="note" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
              </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="stock-count-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="container mt-3 pb-3">
                <div class="row border-bottom pb-2">
                    <div class="col-md-3">
                        <button id="print-btn" type="button" class="btn btn-default btn-sm d-print-none"><i class="ti ti-printer"></i> {{__('db.print')}}</button>
                    </div>
                    <div class="col-md-6">
                        <h3 id="exampleModalLabel" class="modal-title text-center container-fluid">{{gen_setting()->site_title}}</h3>
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close d-print-none"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="col-md-12 text-center">
                        <i style="font-size: 15px;">{{__('db.Stock Count')}}</i>
                    </div>
                </div>
                <br>
                <div id="stock-count-content">
                </div>
                <br>
                <table class="table table-bordered stockdif-list">
                    <thead>
                        <th>#</th>
                        <th>{{__('db.product')}}</th>
                        <th>{{__('db.Expected')}}</th>
                        <th>{{__('db.Counted')}}</th>
                        <th>{{__('db.Difference')}}</th>
                        <th>{{__('db.Cost')}}</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <div id="stock-count-footer"></div>
            </div>
        </div>
    </div>
</div>



@endsection
@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #stock-count-menu").addClass("active");

    // $("#category, #brand").hide();

    $('select[name=type]').on('change', function(){
        if($(this).val() == 'partial')
            $("#category, #brand").show(500);
        else
            $("#category, #brand").hide(500);
    });

    $(document).on('click', '.finalize', function(){
        $('input[name="stock_count_id"]').val($(this).data('id'));
        $('#finalizeModal').modal('show');
    });

    $(document).on('click', '.final-report', function(){
        var stock_count = $(this).data('stock_count');
        var htmltext = '<strong>{{__("db.date")}}: </strong>'+stock_count[0]+'<br><strong>{{__("db.reference")}}: </strong>'+stock_count[1]+'<br><strong>{{__("db.Warehouse")}}: </strong>'+stock_count[2]+'<br><strong>{{__("db.Type")}}: </strong>'+stock_count[3];
        if(stock_count[4])
            htmltext += '<br><strong>{{__("db.category")}}: </strong>'+stock_count[4];
        if(stock_count[5])
            htmltext += '<br><strong>{{__("db.Brand")}}: </strong>'+stock_count[5];
        htmltext += '<br><span class="d-print-none mt-1"><strong>{{__("db.Files")}}: </strong>&nbsp;&nbsp;<a href="'+stock_count[6]+'" class="btn btn-sm btn-primary"><i class="ti ti-download"></i> {{__("db.Initial File")}}</a>&nbsp;&nbsp;<a href="'+stock_count[7]+'" class="btn btn-sm btn-info"><i class="ti ti-download"></i> {{__("db.Final File")}}</a></span>';
        $.get('stock-count/stockdif/' + stock_count[8], function(data){
            $(".stockdif-list tbody").remove();
            var name_code = data[0];
            var expected = data[1];
            var counted = data[2];
            var dif = data[3];
            var cost = data[4];
            var newBody = $("<tbody>");
            if(name_code){
                $('.stockdif-list').removeClass('d-none')
                $.each(name_code, function(index){
                    var newRow = $("<tr>");
                    var cols = '';
                    cols += '<td><strong>' + (index+1) + '</strong></td>';
                    cols += '<td>' + name_code[index] + '</td>';
                    cols += '<td>' + parseFloat(expected[index]).toFixed({{gen_setting()->decimal}}) + '</td>';
                    cols += '<td>' + parseFloat(counted[index]).toFixed({{gen_setting()->decimal}}) + '</td>';
                    cols += '<td>' + parseFloat(dif[index]).toFixed({{gen_setting()->decimal}}) + '</td>';
                    cols += '<td>' + parseFloat(cost[index]).toFixed({{gen_setting()->decimal}}) + '</td>';
                    newRow.append(cols);
                    newBody.append(newRow);
                });

                if( !parseInt(data[5]) ) {
                    htmlFooter = '<a class="btn btn-primary d-print-none" href="stock-count/'+stock_count[8]+'/qty_adjustment"><i class="ti ti-plus"></i> {{__("db.Add Adjustment")}}</a>';
                    $('#stock-count-footer').html(htmlFooter);
                }
            }
            else{
                $('.stockdif-list').addClass('d-none');
                $('#stock-count-footer').html('');
            }

            /*var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=6><strong>{{__("db.Order Discount")}}:</strong></td>';
            cols += '<td>' + sale[19] + '</td>';
            newRow.append(cols);
            newBody.append(newRow);


            newRow.append(cols);
            newBody.append(newRow);*/

            $("table.stockdif-list").append(newBody);
        });

        $('#stock-count-content').html(htmltext);
        $('#stock-count-details').modal('show');
    });

    $(document).on("click", "#print-btn", function(){
          var divToPrint=document.getElementById('stock-count-details');
          var newWin=window.open('','Print-Window');
          newWin.document.open();
          newWin.document.write('<link rel="stylesheet" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') }}" type="text/css"><style type="text/css">@media print {.modal-dialog { max-width: 1000px;} }</style><body onload="window.print()">'+divToPrint.innerHTML+'</body>');
          newWin.document.close();
          setTimeout(function(){newWin.close();},10);
    });

    $('#stock-count-table').DataTable( {
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 7, 8, 9]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                },
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                },
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                },
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                },
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );

</script>
@endpush
