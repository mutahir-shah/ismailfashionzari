@extends('backend.layout.main') @section('content')

<x-error-message key="not_permitted" />

<style>
@media print {
    * {
        font-size:12px;
        line-height: 20px;
    }
    td,th {padding: 5px 0;}
    .hidden-print {
        display: none !important;
    }
    @page { size: landscape; margin: 0 !important; }
    .barcodelist {
        max-width: 378px;
    }
    .barcodelist img {
        max-width: 150px;
    }
}
</style>
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.print_barcode')}}</h4>
                    </div>

	                <form id = 'preview_setting_form' onsubmit = 'return false'>
                        @csrf
                        <div class="card-body">
                            <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>{{__('db.add_product')}} *</label>
                                            <div class="search-box input-group">
                                                <button type="button" class="btn btn-secondary btn-lg"><i class="ti ti-barcode"></i></button>
                                                <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="{{__('db.Please type product code and select')}}" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3 mb-5">
                                        <div class="col-md-12">
                                            <div class="table-responsive mt-3">
                                                <table id="myTable" class="table table-hover order-list">
                                                    <thead>
                                                        <tr>
                                                            <th>{{__('db.name')}}</th>
                                                            <th>{{__('db.Code')}}</th>
                                                            <th>{{__('db.Quantity')}}</th>
                                                            <th>{{__('db.Warehouse')}} / {{__('db.Price')}}</th>
                                                            <th><i class="ti ti-trash"></i></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($preLoadedproducts as $key=>$preLoadedproduct)
                                                        <tr data-imagedata="{{$preLoadedproduct[3]}}" data-price="{{$preLoadedproduct[2]}}" data-promo-price="{{$preLoadedproduct[4]}}" data-currency="{{$preLoadedproduct[5]}}" data-currency-position="{{$preLoadedproduct[6]}}">
                                                            <td>{{$preLoadedproduct[0]}}</td>
                                                            <td class="product-code">{{$preLoadedproduct[1]}}</td>
                                                            <td><input type="number" class="form-control qty" name="products[{{$key}}][quantity]" value="1" /></td>
                                                            <td>
                                                                <select name="products[{{$key}}][warehouse_id]" class="form-control selectpicker" required>
                                                                    <option value="">{{__('db.Choose Warehouse')}}</option>
                                                                    @foreach($warehouses as $id => $warehouse)
                                                                        <option value="{{ $id }}">{{ $warehouse }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td><button type="button" class="ibtnDel btn btn-md btn-danger"><i class="ti ti-trash"></i></button></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_id]" value="{{$preLoadedproduct[8]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_name]" value="{{$preLoadedproduct[0]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][sub_sku]" value="{{$preLoadedproduct[1]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_price]" value="{{$preLoadedproduct[2]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_promo_price]" value="{{$preLoadedproduct[4]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][currency]" value="{{$preLoadedproduct[5]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][currency_position]" value="{{$preLoadedproduct[6]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][brand_name]" value="{{$preLoadedproduct['11']}}"></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    {{-- CUT --}}
                                    <label style="font-size:16px;">{{__('db.Information on Label')}} * <x-info title="You can check/uncheck items to show/hide them on the label. You can also increase/decrease the size of the text thereby customize the label design." type="info" /> </label>

                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <input type="checkbox" name="print[name]" checked value="1" /> {{__('db.Product Name')}}&nbsp;
                                            <div class="d-flex justify-content-start mt-3">
                                                <div>Size:</div>&nbsp;
                                                <div><input class="form-control" type="number" name="print[name_size]" value="15"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="checkbox" name="print[price]" checked value="1" /> {{__('db.Price')}}&nbsp;
                                            <div class="d-flex justify-content-start mt-3">
                                                <div>Size:</div>&nbsp;
                                                <div><input class="form-control" type="number" name="print[price_size]" value="15"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="checkbox" name="print[promo_price]" checked value="1" /> {{__('db.Promotional Price')}}
                                            <div class="d-flex justify-content-start mt-3">
                                                <div>Size:</div>&nbsp;
                                                <div><input class="form-control" type="number" name="print[promo_price_size]" value="15"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-md-4">
                                            <input type="checkbox" name="print[business_name]" checked value="1" /> {{__('db.Business Name')}}
                                            <div class="d-flex justify-content-start mt-3">
                                                <div>Size:</div>&nbsp;
                                                <div><input class="form-control" type="number" name="print[business_name_size]" value="15"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="checkbox" name="print[brand_name]" checked value="1" /> {{__('db.Brand')}}
                                            <div class="d-flex justify-content-start mt-3">
                                                <div>Size:</div>&nbsp;
                                                <div><input class="form-control" type="number" name="print[brand_name_size]" value="15"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="print[variations]" value="1">
                                    <input type="hidden" name="print[variations_size]" value="17">
                                    <input type="hidden" name="print[packing_date]" value="1">
                                    <input type="hidden" name="print[packing_date_size	]" value="12">
                                    <hr>
                                    <div class="row mt-4">
                                        <div class="col-md-8">
                                            <label style="font-size:16px;">{{__('db.paper_size')}} *</label>
                                            <select name="barcode_setting" class="form-control">
                                                @foreach($barcode_settings as $key => $value)
                                                    <option value="{{ $key }}" {{ !empty($default) && $default->id == $key ? 'selected' : '' }}>{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group mt-3">
				                                <button type="button" id="labels_preview" class="btn btn-primary btn-big">{{ __('db.submit') }}</button>

                                                <a href="{{route('barcodes.create')}}" class="btn btn-info add-sale-btn"><i class="ti ti-plus"></i> {{__('db.Add New Setting')}}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {{-- </form> --}}
	                </form>

                </div>
            </div>
        </div>
    </div>

    <div id="print-barcode" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                  <h5 id="modal_header" class="modal-title">{{__('db.Barcode')}}</h5>&nbsp;&nbsp;
                  <button id="print-btn" type="button" class="btn btn-default btn-sm"><i class="ti ti-printer"></i> {{__('db.print')}}</button>
                  <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <div id="label-content">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #printBarcode-menu").addClass("active");
    <?php $productArray = []; ?>
    var lims_product_code = [
    @foreach($lims_product_list_without_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->code . ' (' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)) . ')');
        ?>
    @endforeach
    @foreach($lims_product_list_with_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->item_code . ' (' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)) . ')');
        ?>
    @endforeach
    <?php
        echo  '"'.implode('","', $productArray).'"';
    ?>
    ];

    var lims_productcodeSearch = $('#lims_productcodeSearch');
    var key = 1;
    lims_productcodeSearch.autocomplete({
        source: function(request, response) {
            var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
            response($.grep(lims_product_code, function(item) {
                // console.log(matcher.test(item));
                return matcher.test(item);
            }));
        },
        select: function(event, ui) {
            var data = ui.item.value;
            $.ajax({
                type: 'GET',
                url: 'lims_product_search',
                data: {
                    data: data,
                    warehouse_id: $('#warehouse_id').val(),
                    barcode: true
                },
                success: function(responseData) {
                    data = responseData[0];
                    var flag = 1;

                    //console.log(data[11]);

                    $(".product-code").each(function() {
                        if ($(this).text() == data[1]) {
                            alert('Duplicate input is not allowed!')
                            flag = 0;
                        }
                    });
                    $("input[name='product_code_name']").val('');
                    var warehouses = @json($warehouses); // PHP array কে JS object এ convert করল

                if(flag){
                    var newRow = $('<tr data-imagedata="'+data[3]+'" data-price="'+data[2]+'" data-promo-price="'+data[4]+'" data-currency="'+data[5]+'" data-currency-position="'+data[6]+'">');
                    var cols = '';
                    cols += '<td>' + data[0] + '</td>';
                    cols += '<td class="product-code">' + data[1] + '</td>';
                    cols += '<td><input type="number" class="form-control qty" name="products['+ key +'][quantity]" value="1" /></td>';

                    // 🟢 Warehouse select column
                    cols += '<td>';
                    if(data[14] == true){
                            cols += '<select name="products['+ key +'][product_price]" class="form-control" required>';
                            cols += '<option value="">{{__("db.Choose Warehouse")}}</option>';
                            for(var i = 0; i < data[15].length; i++){
                                cols += '<option value="'+data[15][i].price+'">'+data[15][i].warehouse_name+' | Price: '+data[15][i].price+'</option>';
                            }
                            cols += '</select>';
                        }else{
                            cols += '<input type="text" class="form-control" readonly name="products['+ key +'][product_price]" value="'+data[2]+'">';
                    }
                    cols += '</td>';

                    cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger"><i class="ti ti-trash"></i></button></td>';
                    cols += '<td><input type="hidden" name="products['+ key +'][product_id]" value="'+data[8]+'"></td>';
                    cols += '<td><input type="hidden" name="products['+ key +'][product_name]" value="'+data[0]+'"></td>';
                    cols += '<td><input type="hidden" name="products['+ key +'][sub_sku]" value="'+data[1]+'"></td>';
                    cols += '<td><input type="hidden" class="form-control" name="products['+ key +'][default_price]" value="'+data[2]+'"></td>';
                    cols += '<td><input type="hidden" name="products['+ key +'][product_promo_price]" value="'+data[4]+'"></td>';
                    cols += '<td><input type="hidden" name="products['+ key +'][currency]" value="'+data[5]+'"></td>';
                    cols += '<td><input type="hidden" name="products['+ key +'][currency_position]" value="'+data[6]+'"></td>';
                    cols += '<td><input type="hidden" name="products['+ key +'][brand_name]" value="'+data[11]+'"></td>';

                    newRow.append(cols);
                    $("table.order-list tbody").append(newRow);
                    key++;
                }


                }
            });
        }
    });

    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        rowindex = $(this).closest('tr').index();
        $(this).closest("tr").remove();
    });
    $('#labels_preview').click(function() {
            var url = "{{route('print.label')}}" + "?" + $('form#preview_setting_form').serialize();
            window.open(url, 'newwindow');
    });


    // When warehouse changes
    $('#warehouse_id').on('change', function() {
        $('#lims_productcodeSearch').val('');
        $('table.order-list tbody').empty();
        key = 1;
    });

</script>
@endpush
