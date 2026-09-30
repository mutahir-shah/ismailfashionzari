@extends('backend.layout.main') @section('content')

@push('css')
    <style>
        .sale-section {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 20px 20px 10px 20px;
            margin-bottom: 20px;
            background: #fff;
        }
        body.dark-mode .sale-section {
            background: #283046;
            border-color: #3b4253;
            box-shadow: none;
        }
    </style>
@endpush

<x-error-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Import Sale')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('sale.import') }}" method="POST" enctype="multipart/form-data" class="payment-form">
                            @csrf

                            <div class="sale-section">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.customer')}} *</label>
                                            <select required name="customer_id" class="selectpicker form-control" data-live-search="true" id="customer-id" data-live-search-style="begins" title="Select customer...">
                                                @foreach($lims_customer_list as $customer)
                                                <option value="{{$customer->id}}">{{$customer->name . ' (' . $customer->phone_number . ')'}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Warehouse')}} *</label>
                                            <select required name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Biller')}} *</label>
                                            <select required name="biller_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Biller...">
                                                @foreach($lims_biller_list as $biller)
                                                <option value="{{$biller->id}}">{{$biller->name . ' (' . $biller->company_name . ')'}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Currency')}} *</label>
                                            <select name="currency_id" id="currency" class="form-control selectpicker" data-toggle="tooltip" title="" data-original-title="Sale currency">
                                                @foreach($currency_list as $currency_data)
                                                <option value="{{$currency_data->id}}" data-rate="{{$currency_data->exchange_rate}}">{{$currency_data->code}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Exchange Rate')}} *</label>
                                            <input class="form-control" type="text" id="exchange_rate" name="exchange_rate" value="{{$currency->exchange_rate}}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="sale-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{__('db.Upload CSV File')}} *</label>
                                            <input type="file" name="file" class="form-control" required />
                                            <p>{{__('db.The correct column order is')}} (product_code, quantity, sale_unit_code, price, discount_per_unit, tax_name) {{__('db.and you must follow this')}}. {{__('db.digital_product_unit_not_applicable')}}. {{__('db.All columns are required')}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label></label><br>
                                            <a download href="../sample_file/sample_sale_products.csv" class="btn btn-primary btn-block btn-lg"><i class="ti ti-download"></i> {{__('db.Download Sample File')}}</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_qty" value="0" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_discount" value="0"/>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_tax" value="0" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_price" value="0" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="item" value="0" />
                                            <input type="hidden" name="order_tax" value="0" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="grand_total" value="0" />
                                            <input type="hidden" name="paid_amount" value="{{number_format(0, gen_setting()->decimal, '.', '')}}" />
                                            <input type="hidden" name="payment_status" value="2" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="sale-section">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Order Tax')}}</label>
                                            <select class="form-control" name="order_tax_rate">
                                                <option value="0">No Tax</option>
                                                @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                <strong>{{__('db.Order Discount')}}</strong>
                                            </label>
                                            <input type="number" name="order_discount" class="form-control" step="any" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                <strong>{{__('db.Shipping Cost')}}</strong>
                                            </label>
                                            <input type="number" name="shipping_cost" class="form-control" step="any" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Attach Document')}}</label>
                                            <i class="ti ti-info-circle" data-toggle="tooltip" title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                            <input type="file" name="document" class="form-control" />
                                            @if($errors->has('extension'))
                                                <span>
                                                    <strong>{{ $errors->first('extension') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Sale Status')}} *</label>
                                            <select name="sale_status" class="form-control">
                                                <option value="1">{{__('db.Completed')}}</option>
                                                <option value="2">{{__('db.Pending')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="sale-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{__('db.Sale Note')}}</label>
                                            <textarea rows="5" class="form-control" name="sale_note"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{__('db.Staff Note')}}</label>
                                            <textarea rows="5" class="form-control" name="staff_note"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary" id="submit-button">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
    <script type="text/javascript">

        $("ul#sale").siblings('a').attr('aria-expanded','true');
        $("ul#sale").addClass("show");
        $("ul#sale #sale-import-menu").addClass("active");

        @if(config('database.connections.saleprosaas_landlord'))
            numberOfInvoice = <?php echo json_encode($numberOfInvoice)?>;
            $.ajax({
                type: 'GET',
                async: false,
                url: '{{route("package.fetchData", gen_setting()->package_id)}}',
                success: function(data) {
                    if(data['number_of_invoice'] > 0 && data['number_of_invoice'] <= numberOfInvoice) {
                        localStorage.setItem("message", "You don't have permission to create another invoice as you already exceed the limit! Subscribe to another package if you wants more!");
                        location.href = "{{route('sales.index')}}";
                    }
                }
            });
        @endif

        $('.selectpicker').selectpicker({
            style: 'btn-link',
        });

        $('[data-toggle="tooltip"]').tooltip();

        $('#currency').on('change', function() {
            var rate = $(this).find(':selected').data('rate');
            $('#exchange_rate').val(rate);
        });
    </script>
@endpush
