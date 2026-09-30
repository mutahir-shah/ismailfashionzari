@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<x-success-message key="create_message" />
<x-success-message key="import_message" />
<x-success-message key="success" />
<x-error-message key="not_permitted" />
<x-error-message key="message" />

<section>
    <div class="container-fluid">
        @if(in_array("products-add", $all_permission))
            <a href="{{route('manufacturing.recipes.create')}}" class="btn btn-info add-product-btn"><i class="ti ti-plus"></i> {{__('db.add_recipes')}}</a>

        @endif
    </div>
    <div class="table-responsive">
        <table id="product-data-table" class="table" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Recipe')}}</th>
                    <th>{{__('db.category')}}</th>
                    <th>{{__('db.Unit')}}</th>
                    <th>{{__('db.Cost')}}</th>
                    <th>{{__('db.Price')}}</th>
                    @foreach($custom_fields as $fieldName)
                        <th>{{$fieldName}}</th>
                    @endforeach
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
        </table>
    </div>
</section>

<div id="importProduct" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('product.import') }}" method="post" enctype="multipart/form-data">
          @csrf

        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">Import Product</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
           <p>{{__('db.The correct column order is')}} (image, name*, code*, type*, category*, unit_code*, cost*, price*, product_details, variant_name, item_code, additional_price) {{__('db.and you must follow this')}}.</p>
           <p>{{__('db.To display Image it must be stored in')}} images/product {{__('db.directory')}}. {{__('db.Image name must be same as product name')}}</p>
           <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.Upload CSV File')}} *</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label> {{__('db.Sample File')}}</label>
                        <a href="sample_file/sample_products.csv" class="btn btn-info btn-block btn-md"><i class="ti ti-download"></i>  {{__('db.Download')}}</a>
                    </div>
                </div>
           </div>
            <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
        </div>
        </form>
      </div>
    </div>
</div>

<div id="product-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Product Details')}}</h5>
          <button id="print-btn" type="button" class="btn btn-default btn-sm ml-3"><i class="ti ti-printer"></i> {{__('db.print')}}</button>
          <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-5" id="slider-content"></div>
                <div class="col-md-5 offset-1" id="product-content"></div>

                <div class="col-md-7 mt-2" id="product-variant-section">
                    <h5>{{__('db.Product Variant Information')}}</h5>
                    <table class="table table-bordered table-hover product-variant-list">
                        <thead>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                @if($role_id <= 2)
                <div class="col-md-5 mt-2" id="product-variant-warehouse-section">
                    <h5>{{__('db.Warehouse quantity of product variants')}}</h5>
                    <table class="table table-bordered table-hover product-variant-warehouse-list">
                        <thead>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            <h5 id="recipe-header"></h5>
            <table class="table table-bordered table-hover item-list">
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
      </div>
    </div>
</div>

@endsection
@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script>

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #product-list-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        if(localStorage.getItem("message")) {
            alert(localStorage.getItem("message"));
            localStorage.removeItem("message");
        }

        numberOfProduct = <?php echo json_encode($numberOfProduct)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", $general_setting->package_id)}}',
            success: function(data) {
                if(data['number_of_product'] > 0 && data['number_of_product'] <= numberOfProduct) {
                    $("a.add-product-btn").addClass('d-none');
                }
            }
        });
    @endif

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    var columns = [{"data": "key"},{"data": "name"},{"data": "category"},{"data": "unit"},{"data": "cost"},{"data": "price"}];
    var field_name = <?php echo json_encode($field_name) ?>;
    for(i = 0; i < field_name.length; i++) {
        columns.push({"data": field_name[i]});
    }

    columns.push({"data": "options"});

    var warehouse = [];
    var variant = [];
    var qty = [];
    var htmltext;
    var slidertext;
    var product_id = [];
    var all_permission = <?php echo json_encode($all_permission) ?>;
    var role_id = <?php echo json_encode($role_id) ?>;
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;
    var logoUrl = <?php echo json_encode(url('logo', $general_setting->site_logo)) ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#warehouse_id").val(warehouse_id);

    $( "#select_all" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("tbody input[type='checkbox']").prop('checked', true);
        }
        else {
            $("tbody input[type='checkbox']").prop('checked', false);
        }
    });

    $(document).on("click", "tr.product-link td:not(:first-child, :last-child)", function() {
        productDetails( $(this).parent().data('product'), $(this).parent().data('imagedata') );
    });

    $(document).on("click", ".view", function(){
        var product = $(this).parent().parent().parent().parent().parent().data('product');
        var imagedata = $(this).parent().parent().parent().parent().parent().data('imagedata');
        productDetails(product, imagedata);
    });

    $("#print-btn").on("click", function() {
          var divToPrint=document.getElementById('product-details');
          var newWin=window.open('','Print-Window');
          newWin.document.open();
          newWin.document.write('<link rel="stylesheet" href="<?php echo asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css"><style type="text/css">@media print {.modal-dialog { max-width: 1000px;} }</style><body onload="window.print()">'+divToPrint.innerHTML+'</body>');
          newWin.document.close();
          setTimeout(function(){newWin.close();},10);
    });

    function productDetails(product, imagedata) {
        product[11] = product[11].replace(/@/g, '"');
        htmltext = slidertext = '';

        htmltext = '<p>{{__("db.Type")}}: Recipe'+
            '</p><p>{{__("db.name")}}: '+product[1]+
            '</p><p>{{__("db.Code")}}: '+product[2]+
            '</p><p>{{__("db.category")}}: '+product[4]+
            '</p><p>{{__("db.Unit")}}: '+product[5]+ 
            '</p><p>{{__("db.Product Details")}}: </p>'+product[11];

        if(product[18]) {
            var product_image = product[18].split(",");
            if(product_image.length > 1) {
                slidertext = '<div id="product-img-slider" class="carousel slide" data-ride="carousel"><div class="carousel-inner">';
                for (var i = 0; i < product_image.length; i++) {
                    if(!i)
                        slidertext += '<div class="carousel-item active"><img src="{{url("images/product")}}/'+product_image[i]+'" height="300" width="100%"></div>';
                    else
                        slidertext += '<div class="carousel-item"><img src="{{url("images/product")}}/'+product_image[i]+'" height="300" width="100%"></div>';
                }
                slidertext += '</div><a class="carousel-control-prev" href="#product-img-slider" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a><a class="carousel-control-next" href="#product-img-slider" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a></div>';
            }
            else {
                slidertext = '<img src="{{url("images/product")}}/'+product[18]+'" height="300" width="100%">';
            }
        }
        else {
            slidertext = '<img src="{{url("images/product/zummXD2dvAtI.png")}}" height="300" width="100%">';
        }
        $("#recipe-header").text('');
        $("table.item-list thead").remove();
        $("table.item-list tbody").remove();
        $("table.product-warehouse-list thead").remove();
        $("table.product-warehouse-list tbody").remove();
        $(".product-variant-list thead").remove();
        $(".product-variant-list tbody").remove();
        $(".product-variant-warehouse-list thead").remove();
        $(".product-variant-warehouse-list tbody").remove();
        $("#product-warehouse-section").addClass('d-none');
        $("#product-variant-section").addClass('d-none');
        $("#product-variant-warehouse-section").addClass('d-none');
            $("#recipe-header").text('{{__("db.Recipe")}}');
            product_list = product[13].split(",");
            variant_list = product[14].split(",");
            qty_list = product[15].split(",");
            price_list = product[16].split(",");
            combo_unit = product[20].split(",");
            wastage_percent = product[21].split(",");
            combo_unit_id_list = product[22] ? product[22].split(",") : [];
            $(".item-list thead").remove();
            $(".item-list tbody").remove();
            var newHead = $("<thead>");
            var newBody = $("<tbody>");
            var newRow = $("<tr>");
            newRow.append('<th>{{__("db.product")}}</th><th>{{__("db.Wastage Percent")}}</th><th>{{__("db.Quantity")}}</th><th>{{__("db.Price")}}</th>');
            newHead.append(newRow);
            $(product_list).each(function(i) {
                if(!variant_list[i])
                    variant_list[i] = 0;
                let url = '{{ route("manufacturing.recipes.get-ingredient-data") }}';
                $.get(url, {
                    product_id: product_list[i],
                    variant_id: variant_list[i],
                    qty: qty_list[i],
                    price: price_list[i],
                    combo_unit_id: combo_unit_id_list[i]
                }, function(data) {
                    var newRow = $("<tr>");
                    var cols = '';
                    cols += '<td>' + data.name +' [' + data.code + ']</td>';
                    cols += '<td>' + wastage_percent[i] +'</td>';
                    cols += '<td>' + qty_list[i] + '('+combo_unit[i]+')</td>';
                    cols += '<td>' + parseFloat(data.calculated_price).toFixed(2) + '</td>';

                    newRow.append(cols);
                    newBody.append(newRow);
                });
            });

            $("table.item-list").append(newHead);
            $("table.item-list").append(newBody);
            if(product[19]) {
                $.get('products/variant-data/' + product[12], function(variantData) {
                    var newHead = $("<thead>");
                    var newBody = $("<tbody>");
                    var newRow = $("<tr>");
                    newRow.append('<th>{{__("db.Variant")}}</th><th>{{__("db.Item Code")}}</th><th>{{__("db.Additional Cost")}}</th><th>{{__("db.Additional Price")}}</th><th>{{__("db.qty")}}</th>');
                    newHead.append(newRow);
                    $.each(variantData, function(i) {
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + variantData[i]['name'] + '</td>';
                        cols += '<td>' + variantData[i]['item_code'] + '</td>';
                        if(variantData[i]['additional_cost'])
                            cols += '<td>' + variantData[i]['additional_cost'] + '</td>';
                        else
                            cols += '<td>0</td>';
                        if(variantData[i]['additional_price'])
                            cols += '<td>' + variantData[i]['additional_price'] + '</td>';
                        else
                            cols += '<td>0</td>';
                        cols += '<td>' + variantData[i]['qty'] + '</td>';

                        newRow.append(cols);
                        newBody.append(newRow);
                    });
                    $("table.product-variant-list").append(newHead);
                    $("table.product-variant-list").append(newBody);
                });
                $("#product-variant-section").removeClass('d-none');
            }
            if(role_id <= 2) {
                 const routeTemplate = @json(route('product.warehouse', ['id' => '__ID__']));
                 const url = routeTemplate.replace('__ID__', product[12]);
                $.get(url + product[12], function(data) {
                    if(data.product_warehouse[0].length != 0) {
                        warehouse = data.product_warehouse[0];
                        qty = data.product_warehouse[1];
                        batch = data.product_warehouse[2];
                        expired_date = data.product_warehouse[3];
                        imei_numbers = data.product_warehouse[4];
                        var newHead = $("<thead>");
                        var newBody = $("<tbody>");
                        var newRow = $("<tr>");
                        var productQty = 0;
                        newRow.append('<th>{{__("db.Warehouse")}}</th><th>{{__("db.Batch No")}}</th><th>{{__("db.Expired Date")}}</th><th>{{__("db.Quantity")}}</th><th>{{__("db.IMEI or Serial Numbers")}}</th>');
                        newHead.append(newRow);
                        $.each(warehouse, function(index) {
                            // productQty += qty[index];
                            var newRow = $("<tr>");
                            var cols = '';
                            cols += '<td>' + warehouse[index] + '</td>';
                            cols += '<td>' + batch[index] + '</td>';
                            cols += '<td>' + expired_date[index] + '</td>';
                            cols += '<td>' + qty[index] + '</td>';
                            // console.log(imei_numbers);
                            if (imei_numbers.length <= index) {
                                cols += '<td style="max-height: 100px; overflow-y: auto; word-break: break-word; white-space: normal; display: block; padding-right: 10px;">' + 'N/A' + '</td>';
                            } else {
                                cols += '<td style="max-height: 100px; overflow-y: auto; word-break: break-word; white-space: normal; display: block; padding-right: 10px;">' + imei_numbers[index].split(',').join(",<br/>") + '</td>';
                            }

                            newRow.append(cols);
                            newBody.append(newRow);
                            $("table.product-warehouse-list").append(newHead);
                            $("table.product-warehouse-list").append(newBody);
                        });
                        // console.log(productQty);
                        $("#product-warehouse-section").removeClass('d-none');
                    }
                    if(data.product_variant_warehouse[0].length != 0) {
                        warehouse = data.product_variant_warehouse[0];
                        variant = data.product_variant_warehouse[1];
                        qty = data.product_variant_warehouse[2];
                        var newHead = $("<thead>");
                        var newBody = $("<tbody>");
                        var newRow = $("<tr>");
                        newRow.append('<th>{{__("db.Warehouse")}}</th><th>{{__("db.Variant")}}</th><th>{{__("db.Quantity")}}</th>');
                        newHead.append(newRow);
                        $.each(warehouse, function(index){
                            var newRow = $("<tr>");
                            var cols = '';
                            cols += '<td>' + warehouse[index] + '</td>';
                            cols += '<td>' + variant[index] + '</td>';
                            cols += '<td>' + qty[index] + '</td>';

                            newRow.append(cols);
                            newBody.append(newRow);
                            $("table.product-variant-warehouse-list").append(newHead);
                            $("table.product-variant-warehouse-list").append(newBody);
                        });
                        $("#product-variant-warehouse-section").removeClass('d-none');
                    }
                });
            }

        $('#product-content').html(htmltext);
        $('#slider-content').html(slidertext);
        $('#product-details').modal('show');
        $('#product-img-slider').carousel(0);
    }

    $(document).ready(function() {
        var table = $('#product-data-table').DataTable( {
            responsive: true,
            fixedHeader: {
                header: true,
                footer: true
            },
            "processing": true,
            "serverSide": true,
            "ajax":{
                url:"{{ route('manufacturing.recipes.product-data') }}",
                type: "GET",
                data:{
                    all_permission: all_permission,
                    warehouse_id: warehouse_id,
                    is_recipe: true,

                },
                dataType: "json",
                type:"post"
            },
            "createdRow": function( row, data, dataIndex ) {
                $(row).addClass('product-link');
                $(row).attr('data-product', data['product']);
                $(row).attr('data-imagedata', data['imagedata']);
            },
            "columns": columns,
            'language': {
                /*'searchPlaceholder': "{{__('db.Type Product Name or Code')}}",*/
                'lengthMenu': '_MENU_ {{__("db.records per page")}}',
                 "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                "search":  '{{__("db.Search")}}',
                'paginate': {
                        'previous': '<i class="ti ti-chevron-left"></i>',
                        'next': '<i class="ti ti-chevron-right"></i>'
                }
            },
            order:[['2', 'asc']],
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, 6]
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
            'select': { style: 'multi', selector: 'td:first-child'},
            'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                    customize: function(doc) {
                        for (var i = 1; i < doc.content[1].table.body.length; i++) {
                            if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                                var imagehtml = doc.content[1].table.body[i][0].text;
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                var src = regex.exec(imagehtml)[1];
                                var tempImage = new Image();
                                tempImage.src = src;
                                var canvas = document.createElement("canvas");
                                canvas.width = tempImage.width;
                                canvas.height = tempImage.height;
                                var ctx = canvas.getContext("2d");
                                ctx.drawImage(tempImage, 0, 0);
                                var imagedata = canvas.toDataURL("image/png");
                                delete doc.content[1].table.body[i][0].text;
                                doc.content[1].table.body[i][0].image = imagedata;
                                doc.content[1].table.body[i][0].fit = [30, 30];
                            }
                        }
                    },
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function ( data, row, column, node ) {
                                if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                    var regex = /<img.*?src=['"](.*?)['"]/;
                                    data = regex.exec(data)[1];
                                }
                                return data;
                            }
                        }
                    }
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function ( data, row, column, node ) {
                                if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                    var regex = /<img.*?src=['"](.*?)['"]/;
                                    data = regex.exec(data)[1];
                                }
                                return data;
                            }
                        }
                    }
                },
                {
                    extend: 'print',
                    title: '',
                    text: '<i title="print" class="ti ti-printer"></i>',
                    exportOptions: {
                        columns: ':visible:not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                    repeatingHead: {
                        logo: logoUrl,
                        logoPosition: 'left',
                        logoStyle: '',
                        title: '<h3>Product List</h3>'
                    }
                    /*customize: function ( win ) {
                        $(win.document.body)
                            .prepend(
                                '<img src="http://datatables.net/media/images/logo-fade.png" style="margin:10px;" />'
                            );
                    }*/
                },
                {
                    text: '<i title="delete" class="ti ti-x"></i>',
                    className: 'buttons-delete',
                    action: function ( e, dt, node, config ) {
                        if(user_verified == '1') {
                            product_id.length = 0;
                            $(':checkbox:checked').each(function(i){
                                if(i){
                                    var product_data = $(this).closest('tr').data('product');
                                    if(product_data)
                                        product_id[i-1] = product_data[12];
                                }
                            });
                            if(product_id.length && confirmDelete()) {
                                $.ajax({
                                    type:'POST',
                                    url:'products/deletebyselection',
                                    data:{
                                        productIdArray: product_id
                                    },
                                    success:function(data) {
                                        alert(data);
                                        //dt.rows({ page: 'current', selected: true }).deselect();
                                        dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                    }
                                });
                            }
                            else if(!product_id.length)
                                alert('No product is selected!');
                        }
                        else
                            alert('This feature is disable for demo!');
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="ti ti-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
        } );

    } );

    if(all_permission.indexOf("products-delete") == -1)
        $('.buttons-delete').addClass('d-none');

    $('select').selectpicker();

</script>
@endpush
