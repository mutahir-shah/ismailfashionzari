@extends('backend.layout.main') @section('content')
    <x-error-message key="not_permitted" />

    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Add Return') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>
                            <form class="sale-return-form" action="{{ route('return-sale.store') }}" method="post" enctype="multipart/form-data">
                                @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <input type="hidden" name="sale_id" value="{{ $lims_sale_data->id }}">
                                            <h5>{{ __('db.Order Table') }} *</h5>
                                            <div class="table-responsive mt-3">
                                                <table id="myTable" class="table table-hover order-list">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('db.name') }}</th>
                                                            <th>{{ __('db.Code') }}</th>
                                                            <th>{{ __('db.Batch No') }}</th>
                                                            <th>{{ __('db.Quantity') }} <x-info title="Current Return Quantity"/></th>
                                                            <th>{{ __('db.Net Unit Price') }} <x-info title="Product Price - Unit Discount = Net Unit Price"/></th>
                                                            <th>{{ __('db.Discount') }} <x-info title="Total unit discount / Total Qty = Unit Dicount"/> </th>
                                                            <th>{{ __('db.Tax') }}</th>
                                                            <th>{{ __('db.Subtotal') }} <x-info title="Qty * Unit Price = SubTotal"/> </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($lims_product_sale_data as $key => $product_sale)
                                                            <tr>
                                                                <?php
                                                                // Fetch product data
                                                                $product_data = DB::table('products')->find($product_sale->product_id);

                                                                if (!$product_data) {
                                                                    // Skip iteration if product not found
                                                                    continue;
                                                                }

                                                                // Handle variant data if exists
                                                                $product_variant_id = null;
                                                                if ($product_sale->variant_id) {
                                                                    $product_variant_data = \App\Models\ProductVariant::select('id', 'item_code')->FindExactProduct($product_data->id, $product_sale->variant_id)->first();

                                                                    if ($product_variant_data) {
                                                                        $product_variant_id = $product_variant_data->id;
                                                                        $product_data->code = $product_variant_data->item_code;
                                                                    } else {
                                                                        $product_data->code = $product_data->code ?? 'N/A';
                                                                    }
                                                                }

                                                                // Calculate product price based on tax method
                                                                if ($product_data->tax_method == 1) {
                                                                    $product_price = $product_sale->net_unit_price + $product_sale->discount / $product_sale->qty;
                                                                } elseif ($product_data->tax_method == 2) {
                                                                    $product_price = $product_sale->total / $product_sale->qty + $product_sale->discount / $product_sale->qty;
                                                                }

                                                                // Fetch tax data
                                                                $tax = DB::table('taxes')->where('rate', $product_sale->tax_rate)->first();

                                                                // Fetch unit name
                                                                if ($product_data->type == 'standard') {
                                                                    $unit = DB::table('units')->select('unit_name')->find($product_sale->sale_unit_id);
                                                                    $unit_name = $unit->unit_name ?? 'N/A';
                                                                } else {
                                                                    $unit_name = 'n/a';
                                                                }

                                                                // Fetch batch data
                                                                $product_batch_data = \App\Models\ProductBatch::select('batch_no')->find($product_sale->product_batch_id);
                                                                ?>

                                                                <td>{{ $product_data->name ?? 'N/A' }}</td>
                                                                <td>{{ $product_data->code ?? 'N/A' }}</td>

                                                                @if ($product_batch_data)
                                                                    <td>
                                                                        <input type="hidden" class="product-batch-id"
                                                                            name="product_batch_id[]"
                                                                            value="{{ $product_sale->product_batch_id }}">
                                                                        {{ $product_batch_data->batch_no }}
                                                                    </td>
                                                                @else
                                                                    <td>
                                                                        <input type="hidden" class="product-batch-id"
                                                                            name="product_batch_id[]">
                                                                        N/A
                                                                    </td>
                                                                @endif

                                                                <td>
                                                                    <input type="hidden" name="actual_qty[]"
                                                                        class="actual-qty"
                                                                        value="{{ $product_sale->qty - $product_sale->return_qty }}">
                                                                    <input type="number" class="form-control qty"
                                                                        name="qty[]"
                                                                        value="{{ $product_sale->qty - $product_sale->return_qty }}"
                                                                        required step="any"
                                                                        min="0.000001"
                                                                        max="{{ $product_sale->qty - $product_sale->return_qty }}" />
                                                                </td>

                                                                <td class="net_unit_price">
                                                                    {{ number_format((float) $product_sale->net_unit_price, gen_setting()->decimal, '.', '') }}
                                                                </td>
                                                                <td class="discount"
                                                                    data-unit_discount="{{ $product_sale->discount / $product_sale->qty }}">
                                                                    {{ number_format((float) $product_sale->discount, gen_setting()->decimal, '.', '') }}
                                                                </td>
                                                                <td class="tax">
                                                                    {{ number_format((float) $product_sale->tax, gen_setting()->decimal, '.', '') }}
                                                                </td>
                                                                <td class="sub-total">
                                                                    {{ number_format((float) $product_sale->total, gen_setting()->decimal, '.', '') }}
                                                                </td>

                                                                <input type="hidden" class="product-code"
                                                                    name="product_code[]"
                                                                    value="{{ $product_data->code ?? 'N/A' }}" />
                                                                <input type="hidden" name="product_sale_id[]"
                                                                    value="{{ $product_sale->id }}" />
                                                                <input type="hidden" name="product_id[]" class="product-id"
                                                                    value="{{ $product_data->id }}" />
                                                                <input type="hidden" class="unit-price"
                                                                    value="{{ $product_sale->total / $product_sale->qty }}">
                                                                <input type="hidden" name="product_variant_id[]"
                                                                    value="{{ $product_variant_id }}" />
                                                                <input type="hidden" class="product-price"
                                                                    name="product_price[]"
                                                                    value="{{ $product_price ?? 0 }}" />
                                                                <input type="hidden" class="sale-unit" name="sale_unit[]"
                                                                    value="{{ $unit_name }}" />
                                                                <input type="hidden" class="net_unit_price"
                                                                    name="net_unit_price[]"
                                                                    value="{{ $product_sale->net_unit_price }}" />
                                                                <input type="hidden" class="discount-value"
                                                                    name="discount[]"
                                                                    value="{{ $product_sale->discount }}" />
                                                                <input type="hidden" class="tax-rate" name="tax_rate[]"
                                                                    value="{{ $product_sale->tax_rate }}" />
                                                                <input type="hidden" class="tax-name"
                                                                    value="{{ $tax->name ?? 'No Tax' }}" />
                                                                <input type="hidden" class="tax-method"
                                                                    value="{{ $product_data->tax_method ?? 1 }}" />
                                                                <input type="hidden" class="unit-tax-value"
                                                                    value="{{ $product_sale->tax / $product_sale->qty }}" />
                                                                <input type="hidden" class="tax-value" name="tax[]"
                                                                    value="{{ $product_sale->tax }}" />
                                                                <input type="hidden" class="subtotal-value"
                                                                    name="subtotal[]" value="{{ $product_sale->total }}" />
                                                                <input type="hidden" class="imei-number"
                                                                    value="{{ $product_sale->imei_number }}" />
                                                                <input type="hidden" class="return-imei-number"
                                                                    name="imei_number[]" value="" />
                                                            </tr>
                                                        @endforeach
                                                    </tbody>

                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_qty" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_discount" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_tax" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="total_price" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="item" />
                                                <input type="hidden" name="order_tax" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <input type="hidden" name="grand_total" />
                                                <input type="hidden" name="change_sale_status" value="0">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ __('db.Order Tax') }}</label>
                                                <select class="form-control" name="order_tax_rate">
                                                    <option value="0">No Tax</option>
                                                    @foreach ($lims_tax_list as $tax)
                                                        <option value="{{ $tax->rate }}">{{ $tax->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ __('db.Attach Document') }}</label>
                                                <i class="ti ti-info-circle" data-toggle="tooltip"
                                                    title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                                <input type="file" name="document" class="form-control" />
                                                @if ($errors->has('extension'))
                                                    <span>
                                                        <strong>{{ $errors->first('extension') }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ __('db.Return Discount') }}<x-info title="Current Return Discount"/></label>
                                                <input type="number" name="total_sale_discount" id="discount_value"
                                                    class="form-control"
                                                    value="{{ $lims_sale_data->order_discount ?? 0 }}" />
                                                @if ($errors->has('extension'))
                                                    <span>
                                                        <strong>{{ $errors->first('extension') }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @if($lims_sale_data->paid_amount > 0) 
                                    {{-- only for paid or partial paid sale --}}
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <div class="form-check d-inline-block ml-1 mt-4">
                                                    <input class="form-check-input" type="checkbox" name="refund" id="refund" checked>
                                                    <label style="color:rgb(136, 136, 136);" class="form-check-label" for="refund">
                                                        {{ __('db.issue_refund') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{__('db.Account')}}</label>
                                                <select class="form-control" name="account_id">
                                                    @foreach($lims_account_list as $account)
                                                    <option value="{{$account->id}}" {{ $account->is_default ? 'selected' : ''}}>{{$account->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{__('db.Paid By')}}</label>
                                                <select class="form-control" name="payment_method">
                                                    <option value="Cash">Cash</option>
                                                    <option value="Bank">Bank</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ __('db.refund_amount') }}</label>
                                                <input type="number" name="refund_amount" id="refund_amount" class="form-control" value="{{ $lims_sale_data->paid_amount ?? 0 }}" max="{{ $lims_sale_data->paid_amount ?? 0 }}" step="0.001"/>
                                                @if ($errors->has('extension'))
                                                    <span>
                                                        <strong>{{ $errors->first('extension') }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>{{ __('db.Return Note') }}</label>
                                                <textarea rows="5" class="form-control" name="return_note"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>{{ __('db.Staff Note') }}</label>
                                                <textarea rows="5" class="form-control" name="staff_note"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary"
                                            id="submit-button">
                                    </div>
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <table class="table table-bordered table-condensed totals">
                <td><strong>{{ __('db.Items') }}</strong>
                    <span class="pull-right"
                        id="item">{{ number_format(0, gen_setting()->decimal, '.', '') }}</span>
                </td>
                <td><strong>{{ __('db.Total') }}</strong>
                    <span class="pull-right"
                        id="subtotal">{{ number_format(0, gen_setting()->decimal, '.', '') }}</span>
                </td>
                <td><strong>{{ __('db.Order Tax') }}</strong>
                    <span class="pull-right"
                        id="order_tax">{{ number_format(0, gen_setting()->decimal, '.', '') }} <x-info title="(Subtotal + tax) - Return DIscount"/></span>
                </td>

                <td><strong>{{ __('db.Return Discount') }} <x-info title="Current Return Discount"/></strong>
                    <span class="pull-right" id="order_discount"
                        data-total_discount="{{ $lims_sale_data->order_discount ?? 0 }}">{{ number_format($lims_sale_data->order_discount, gen_setting()->decimal, '.', '') }}</span>
                </td>

                <td><strong>{{ __('db.grand total') }} <x-info title="(Sutotal + tax) - Return Discount = Grand Total"/></strong>
                    <span class="pull-right"
                        id="grand_total">{{ number_format(0, gen_setting()->decimal, '.', '') }} </span>
                </td>
            </table>
        </div>

        <!-- add cash register modal -->
        <div id="cash-register-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('cashRegister.store') }}" method="post">
                        @csrf
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('db.Add Cash Register') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>
                        <div class="row">
                            <div class="col-md-6 form-group warehouse-section">
                                <label>{{ __('db.Warehouse') }} *</strong> </label>
                                <select required name="warehouse_id" class="selectpicker form-control"
                                    data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                    @foreach ($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.Cash in Hand') }} *</strong> </label>
                                <input type="number" name="cash_in_hand" required class="form-control">
                            </div>
                            <div class="col-md-12 form-group">
                                <button type="submit" class="btn btn-primary">{{ __('db.submit') }}</button>
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script type="text/javascript">

        // array data
        var product_price = [];
        var product_discount = [];
        var tax_rate = [];
        var tax_name = [];
        var tax_method = [];
        var unit_name = [];
        var unit_operator = [];
        var unit_operation_value = [];
        var is_imei = [];

        // temporary array
        var temp_unit_name = [];
        var temp_unit_operator = [];
        var temp_unit_operation_value = [];

        var rowindex;
        var customer_group_rate;
        var row_product_price;
        var role_id = <?php echo json_encode(Auth::user()->role_id); ?>;
        var currency = <?php echo json_encode($currency); ?>;
        var changeSaleStatus;

        @include('backend.return.partials.quantity_validation')

        $('.selectpicker').selectpicker({
            style: 'btn-link',
        });
        $('[data-toggle="tooltip"]').tooltip();

        //Change quantity
        $("#myTable").on('input', '.qty', function() {
            rowindex = $(this).closest('tr').index();
            // if($(this).val() < 1 && $(this).val() != '') {
            // $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(0);
            // alert("Quantity can't be less than 1");
            // }
            calculateTotal();
        });

        //Discount or order tax change
        $('select[name="order_tax_rate"], #discount_value').on("keyup change", function() {
            calculateGrandTotal();
        });

        //Calculate totals for all rows
        function calculateTotal() {
            var total_qty = 0;
            var total_discount = 0;
            var total_tax = 0;
            var total = 0;
            var item = 0;
            changeSaleStatus = 1;

            $(".qty").each(function(i) {
                var actual_qty = parseFloat($('table.order-list tbody tr:nth-child(' + (i + 1) + ') .actual-qty')
                    .val());
                var qty = parseFloat($(this).val());

                if (qty != actual_qty) {
                    changeSaleStatus = 0;
                }
                if (qty > actual_qty) {
                    alert('Quantity can not be bigger than the actual quantity!');
                    qty = actual_qty;
                    $(this).val(actual_qty);
                }

                var discount = qty * $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .discount').data(
                    'unit_discount');
                $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .discount').text(discount.toFixed(
                    {{ gen_setting()->decimal }}));

                var tax = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .unit-tax-value').val() * qty;
                var tax_method = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .tax-method').val();
                var unit_price = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .unit-price').val();

                total_qty += parseFloat(qty);
                total_discount += parseFloat(discount);
                total_tax += parseFloat(tax);
                total += parseFloat(unit_price * qty);

                if(tax_method == 1) {
                    var row_sub_total = (unit_price * qty) + tax;
                }
                else if(tax_method == 2) {
                    var row_sub_total = (unit_price * qty);
                }

                //var row_sub_total = (unit_price * qty) + tax;
                $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .discount-value').val(total_discount);
                $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .subtotal-value').val(unit_price * qty);
                $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .sub-total').text(parseFloat(row_sub_total)
                    .toFixed({{ gen_setting()->decimal }}));
                $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .tax-value').val(parseFloat(tax).toFixed(
                    {{ gen_setting()->decimal }}));
                $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .tax').text(parseFloat(tax).toFixed(
                    {{ gen_setting()->decimal }}));
                item++;
            });

            if (changeSaleStatus)
                $('input[name="change_sale_status"]').val(changeSaleStatus);

            $('input[name="total_qty"]').val(total_qty);
            $('input[name="total_tax"]').val(total_tax.toFixed({{ gen_setting()->decimal }}));
            $('input[name="total_price"]').val(total.toFixed({{ gen_setting()->decimal }}));
            $('input[name="item"]').val(item);

            item += '(' + total_qty + ')';
            $('#item').text(item);

            calculateGrandTotal();
        }

        //Grand total
        function calculateGrandTotal() {
            var subtotal = parseFloat($('input[name="total_price"]').val());
            var order_tax_rate = parseFloat($('select[name="order_tax_rate"]').val());
            var order_discount = parseFloat($('#discount_value').val()) || 0;

            var order_tax = subtotal * (order_tax_rate / 100);
            var sale_discount = $('input[name="total_sale_discount"]').val() || 0;
            var grand_total = (subtotal + order_tax) - sale_discount;

            $('#subtotal').text(subtotal.toFixed({{ gen_setting()->decimal }}));
            $('#order_tax').text(order_tax.toFixed({{ gen_setting()->decimal }}));
            $('input[name="order_tax"]').val(order_tax.toFixed({{ gen_setting()->decimal }}));
            $('#grand_total').text(grand_total.toFixed({{ gen_setting()->decimal }}));
            $('input[name="grand_total"]').val(grand_total.toFixed({{ gen_setting()->decimal }}));

            $('#refund_amount').val(grand_total.toFixed({{ gen_setting()->decimal }}));
            $('#refund_amount').attr('max', grand_total.toFixed({{ gen_setting()->decimal }}));
        }

        //Enter key navigation
        $(window).keydown(function(e) {
            if (e.which == 13) {
                var $targ = $(e.target);
                if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
                    var focusNext = false;
                    $(this).find(":input:visible:not([disabled],[readonly]), a").each(function() {
                        if (this === e.target) {
                            focusNext = true;
                        } else if (focusNext) {
                            $(this).focus();
                            return false;
                        }
                    });
                    return false;
                }
            }
        });

        //Prevent empty order table submission and validate quantities
        $('.sale-return-form').on('submit', function(e) {
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to order table!");
                e.preventDefault();
                return false;
            }

            if (!validateSaleReturnQuantities($(this))) {
                e.preventDefault();
                return false;
            }
        });

        //Initial calculation
        calculateTotal();
    </script>
@endpush
