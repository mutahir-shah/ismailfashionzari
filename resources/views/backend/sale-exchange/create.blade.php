@extends('backend.layout.main')
@section('content')
    @push('css')
        <style>
            @media print {
                .hidden-print {
                    display: none !important;
                }
            }

            #product-results-container {
                background: #f5f6f7;
                position: absolute;
                overflow: hidden;
                max-height: 300px;
                overflow-y: auto;
                padding-top: 10px;
                top: 40px;
                width: 100%;
                z-index: 999
            }

            #sale_product-results-container {
                background: #f5f6f7;
                position: absolute;
                overflow: hidden;
                max-height: 300px;
                overflow-y: auto;
                padding-top: 10px;
                top: 40px;
                width: 100%;
                z-index: 999
            }

            #product-results-container .product-img,
            #sale_product-results-container .product-img {
                border-radius: 3px;
                color: #7c5cc4;
                font-size: 13px;
                padding-top: 7px;
                padding-bottom: 7px;
                text-align: left
            }

            #product-results-container .product-img:hover,
            #sale_product-results-container .product-img:hover {
                background-color: #7c5cc4;
                color: #FFF
            }

            /* ===== RETURN PRODUCT ROW STYLING ===== */
            .return-product-row {
                background-color: #fff3cd !important;
                border-left: 3px solid #ff9800;
            }

            .exchange-checkbox {
                width: 20px;
                height: 20px;
                cursor: pointer;
            }
        </style>
    @endpush

    <x-error-message key="not_permitted" />
    <x-error-message key="error" />

    <?php $authUser = Auth::user()->role_id; ?>

    <section id="pos-layout" class="forms hidden-print">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Sale Exchange') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                            </p>
                            <form action="{{ route('exchange.store', @$lims_sale_data->id ?? null) }}" method="POST"
                                enctype="multipart/form-data" id="payment-form">
                                @csrf

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.date') }}</label>
                                                    @can('change_sale_date')
                                                        <input type="text" name="created_at" class="form-control date"
                                                            value="{{ date(gen_setting()->date_format, strtotime(@$lims_sale_data->created_at?->toDateString())) }}" />
                                                    @else
                                                        <input type="text" name="created_at" class="form-control date"
                                                            value="{{ date(gen_setting()->date_format, strtotime(@$lims_sale_data->created_at?->toDateString())) }}"
                                                            readonly />
                                                    @endcan
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.reference') }}</label>
                                                    <p><strong>{{ @$lims_sale_data->reference_no ?? 'N/A' }}</strong></p>
                                                    <input type="hidden" name="reference_no"
                                                        value="{{ @$lims_sale_data->reference_no ?? 'N/A' }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.customer') }} *</label>
                                                    <input type="hidden" name="customer_id_hidden"
                                                        value="{{ @$lims_sale_data->customer_id }}" />
                                                    <select required name="customer_id" class="selectpicker form-control"
                                                        data-live-search="true" id="customer_id" title="Select customer...">
                                                        @foreach ($lims_customer_list as $customer)
                                                            <option value="{{ $customer->id }}">
                                                                {{ $customer->name . ' (' . $customer->phone_number . ')' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <x-validation-error fieldName="customer_id" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Warehouse') }} *</label>
                                                    <input type="hidden" name="warehouse_id_hidden"
                                                        value="{{ @$lims_sale_data->warehouse_id }}" />
                                                    <select required id="warehouse_id" name="warehouse_id"
                                                        class="selectpicker form-control" data-live-search="true"
                                                        data-live-search-style="begins" title="Select warehouse...">
                                                        @foreach ($lims_warehouse_list as $warehouse)
                                                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <x-validation-error fieldName="warehouse_id" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Biller') }} *</label>
                                                    <input type="hidden" name="biller_id_hidden"
                                                        value="{{ @$lims_sale_data->biller_id }}" />
                                                    <select required name="biller_id" class="selectpicker form-control"
                                                        data-live-search="true" data-live-search-style="begins"
                                                        title="Select Biller...">
                                                        @foreach ($lims_biller_list as $biller)
                                                            <option value="{{ $biller->id }}">
                                                                {{ $biller->name . ' (' . $biller->company_name . ')' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">{{ __('db.Account') }} *</label>
                                                <select class="form-control selectpicker" name="account_id">
                                                    @foreach ($lims_account_list as $account)
                                                        <option value="{{ $account->id }}"
                                                            {{ $account->is_default ? 'selected' : '' }}>
                                                            {{ $account->name }} [{{ $account->account_no }}]
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <h3>{{ __('db.search_by_reference_no_or_Product') }}</h3>
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <label>Search by Reference Number</label>

                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="sale_product_search"
                                                        name="reference_no"
                                                        value="{{ request('reference_no', $lims_sale_data->reference_no ?? '') }}"
                                                        placeholder="Enter reference number">

                                                    <a href="#" class="btn btn-primary" id="saleSearchBtn">
                                                        <i class="ti ti-search"></i> Search
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="col-md-8">

                                                <label>{{ __('db.Return Select Product') }}</label>

                                                <div class="search-box form-group mb-2" style="position:relative">
                                                    <div class="input-group pos">

                                                        <input style="border: 1px solid #7c5cc4;" type="text"
                                                            name="sale_product_code_name" id="sale-product-search-input"
                                                            placeholder="Scan/Search product by name/code/IMEI"
                                                            class="form-control" />

                                                        <button type="button" class="btn btn-primary"
                                                            onclick="barcodeSale()">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                height="16" fill="currentColor" class="bi bi-upc"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0z" />
                                                            </svg>
                                                        </button>

                                                    </div>

                                                    <div id="sale_product-results-container"></div>

                                                    <div id="no-results-message-sale"
                                                        style="background-color:#f5f6f7;color:#666;margin-top:5px;padding:3px 5px;display:none;">
                                                        No results found
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <input type="hidden" name="sale_id"
                                                            value="{{ @$lims_sale_data->id }}">
                                                        <h5>{{ __('db.Order Product') }} *</h5>
                                                        <div class="table-responsive mt-3">
                                                            <table id="sale-product-table"
                                                                class="table table-hover return-order-list">
                                                                <thead>
                                                                    <tr>
                                                                        <th>{{ __('db.name') }}</th>
                                                                        <th>{{ __('db.Quantity') }}</th>
                                                                        <th>{{ __('db.Net Unit Price') }}</th>
                                                                        <th>{{ __('db.Discount') }}</th>
                                                                        <th>{{ __('db.Tax') }}</th>
                                                                        <th>{{ __('db.Subtotal') }}</th>
                                                                        <th><i class="ti ti-trash"></i></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @include('backend.sale-exchange.partials.sale-products')
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-12">

                                                <label>{{ __('db.New Select Product') }}</label>
                                                
                                                <div class="search-box form-group mb-2" style="position:relative">
                                                    <div class="input-group pos">
                                                        <input style="border: 1px solid #7c5cc4;" type="text"
                                                            name="product_code_name" id="product-search-input"
                                                            placeholder="Scan/Search product by name/code/IMEI"
                                                            class="form-control" />
                                                        <button type="button" class="btn btn-primary"
                                                            onclick="barcodeNew()">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                height="16" fill="currentColor" class="bi bi-upc"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div id="product-results-container"></div>
                                                    <div id="no-results-message"
                                                        style="background-color: #f5f6f7;color: #666; margin-top: 5px;padding: 3px 5px; display: none;">
                                                        No results found
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="table-responsive mt-3">
                                                    <table id="myTable" class="table table-hover order-list">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('db.name') }}</th>
                                                                <th>{{ __('db.Quantity') }}</th>
                                                                <th>{{ __('db.Net Unit Price') }}</th>
                                                                <th>{{ __('db.Discount') }}</th>
                                                                <th>{{ __('db.Tax') }}</th>
                                                                <th>{{ __('db.Subtotal') }}</th>
                                                                <th><i class="ti ti-trash"></i></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="total_qty"
                                                        value="{{ @$lims_sale_data->total_qty }}" /></div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="total_discount"
                                                        value="{{ @$lims_sale_data->total_discount }}" /></div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="total_tax"
                                                        value="{{ @$lims_sale_data->total_tax }}" /></div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="total_price"
                                                        value="{{ @$lims_sale_data->total_price }}" /></div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <input type="hidden" name="item"
                                                        value="{{ @$lims_sale_data->item }}" />
                                                    <input type="hidden" name="order_tax"
                                                        value="{{ @$lims_sale_data->order_tax }}" />
                                                </div>
                                                <x-validation-error fieldName="item" />
                                            </div>
                                            <div class="col-md-2">
                                                @if (@$lims_sale_data->coupon_id)
                                                    @php
                                                        $coupon_data = DB::table('coupons')->find(
                                                            @$lims_sale_data->coupon_id,
                                                        );
                                                    @endphp
                                                    <input type="hidden" name="coupon_active" value="1" />
                                                    <input type="hidden" name="coupon_type"
                                                        value="{{ $coupon_data->type }}" />
                                                    <input type="hidden" name="coupon_amount"
                                                        value="{{ $coupon_data->amount }}" />
                                                    <input type="hidden" name="coupon_minimum_amount"
                                                        value="{{ $coupon_data->minimum_amount }}" />
                                                    <input type="hidden" name="coupon_discount"
                                                        value="{{ @$lims_sale_data->coupon_discount }}">
                                                @else
                                                    <input type="hidden" name="coupon_active" value="0" />
                                                @endif
                                                <div class="form-group">
                                                    <input type="hidden" name="grand_total"
                                                        value="{{ @$lims_sale_data->grand_total }}" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="total_qty" /></div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="total_discount" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="total_tax" /></div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="total_price" /></div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group"><input type="hidden" name="item" /><input
                                                        type="hidden" name="order_tax" /></div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <input type="hidden" name="grand_total"
                                                        class="exchage_grand_total" />
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
                                                            <option value="{{ $tax->rate }}">{{ $tax->name }}
                                                            </option>
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
                                                        <span><strong>{{ $errors->first('extension') }}</strong></span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Return Discount') }}<x-info
                                                            title="Current Return Discount" /></label>
                                                    <input type="number" name="total_sale_discount" id="discount_value"
                                                        class="form-control"
                                                        value="{{ @$lims_sale_data->order_discount ?? 0 }}" />
                                                </div>
                                            </div>
                                        </div>

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

                                        <!-- Exchange Calculation Section -->
                                        <table class="table table-bordered table-condensed exchange-totals">
                                            <tbody>
                                                <tr style="background-color: #e3f2fd;">
                                                    <td colspan="6"
                                                        style="text-align: center; font-weight: bold; padding: 10px; color: #1976d2;">
                                                        EXCHANGE CALCULATION
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Exchange Value</strong>
                                                        <span class="pull-right" id="exchange-value"
                                                            style="color: #d32f2f; font-weight: bold;">0.00</span>
                                                    </td>
                                                    <td><strong>New Products Total</strong>
                                                        <span class="pull-right" id="new-products-total"
                                                            style="color: #388e3c; font-weight: bold;">0.00</span>
                                                    </td>
                                                    <td><strong>New Shipping</strong>
                                                        <span class="pull-right" id="exchange-shipping"
                                                            style="color: #f57c00; font-weight: bold;">0.00</span>
                                                    </td>
                                                    <td><strong>New Grand Total</strong>
                                                        <span class="pull-right" id="new-grand-total"
                                                            style="color: #1565c0; font-weight: bold;">0.00</span>
                                                    </td>
                                                </tr>
                                                <tr style="background-color: #fff3e0;">
                                                    <td colspan="2"
                                                        style="text-align: right; font-size: 16px; font-weight: bold;">
                                                        BALANCE (Customer Payment):
                                                    </td>
                                                    <td colspan="4">
                                                        <input id="amount" type="hidden" value="0"
                                                            name="amount" />
                                                        <input id="payment-type" type="hidden" value="0"
                                                            name="payment_type" />
                                                        <span id="balance-display"
                                                            style="font-size: 18px; font-weight: bold; color: #1565c0;">0.00</span>
                                                        <span id="balance-status"
                                                            style="margin-left: 20px; font-weight: bold; font-size: 14px;"></span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!-- QR Scanner -->
                                        <div
                                            style="width:100%;max-width:350px;position:fixed;top:5%;left:50%;transform:translateX(-50%);z-index:999">
                                            <button type="button" class="btn btn-danger" id="closeScannerBtn"
                                                style="display:none">X</button>
                                            <div id="reader" style="width:100%;"></div>
                                        </div>

                                        <!-- Hidden Inputs for Return/Exchange Data -->
                                        <input type="hidden" id="return-products-data" name="return_products_data"
                                            value="">
                                        <input type="hidden" id="exchange-products-data" name="exchange_products_data"
                                            value="">

                                        <div class="form-group">
                                            <input type="hidden" name="draft" value="0" />
                                            <button id="submit-button" type="submit"
                                                class="btn btn-primary">{{ __('db.submit') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
            class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="modal_header" class="modal-title"></h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                            <span aria-hidden="true"><i class="ti ti-x"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="row modal-element">
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Quantity') }}</label>
                                    <input type="number" step="any" name="edit_qty" class="form-control numkey">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Unit Discount') }}</label>
                                    <input type="number" name="edit_discount" class="form-control numkey">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Price Option') }}</strong> </label>
                                        <div class="input-group">
                                            <select class="form-control selectpicker" name="price_option"
                                                class="price-option"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Unit Price') }}</label>
                                    <input type="number" name="edit_unit_price" class="form-control numkey"
                                        step="any">
                                </div>
                                <?php
                                $tax_name_all[] = 'No Tax';
                                $tax_rate_all[] = 0;
                                foreach ($lims_tax_list as $tax) {
                                    $tax_name_all[] = $tax->name;
                                    $tax_rate_all[] = $tax->rate;
                                }
                                ?>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Tax Rate') }}</label>
                                    <select name="edit_tax_rate" class="form-control selectpicker">
                                        @foreach ($tax_name_all as $key => $name)
                                            <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="edit_unit" class="col-md-4 form-group">
                                    <label>{{ __('db.Product Unit') }}</label>
                                    <select name="edit_unit" class="form-control selectpicker"></select>
                                </div>
                            </div>
                            <button type="button" name="update_btn"
                                class="btn btn-primary">{{ __('db.update') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="print-layout"></section>

    <div style="width:100%;max-width:350px;position:fixed;top:5%;left:50%;transform:translateX(-50%);z-index:999">
        <button type="button" class="btn btn-danger" id="closeScannerBtn" style="display:none"> X </button>
        <div id="reader" style="width:100%;"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        const doneTypingInterval = 300;

        const $newProductInput = $('#product-search-input');
        const $newProductResults = $('#product-results-container');
        const $saleProductInput = $('#sale-product-search-input');
        const $saleProductResults = $('#sale_product-results-container');
        const $noResults = $('#no-results-message');
        const $noResultsSale = $('#no-results-message-sale');

        function clearResults(type = 'new') {
            if (type === 'return') {
                $saleProductResults.empty().css('padding', '0');
                $noResultsSale.hide();
            } else {
                $newProductResults.empty().css('padding', '0');
                $noResults.hide();
            }
        }

        $(document).ready(function() {
            calculateTotal();
            $('#product-search-input').focus();
            let typingTimer;

            function searchProducts(search, type = 'new') {
                let $resultsContainer, $noResultsMsg;
                if (type === 'return') {
                    $resultsContainer = $saleProductResults;
                    $noResultsMsg = $noResultsSale;
                } else {
                    $resultsContainer = $newProductResults;
                    $noResultsMsg = $noResults;
                }

                $resultsContainer.css('padding', '0 10px 15px');
                $resultsContainer.html(
                    '<div class="loader" title="4" style="border:none;min-height:300px"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="10" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.2s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="20" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.4s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect></svg></div>'
                );
                $noResultsMsg.hide();

                var warehouse_id = $('#warehouse_id').val();
                if (!warehouse_id) {
                    alert('Please select warehouse');
                    return;
                }

                $.ajax({
                    url: '{{ url('/sales/search') }}',
                    type: 'GET',
                    data: {
                        warehouse_id: warehouse_id,
                        search: search
                    },
                    success: function(data) {
                        $resultsContainer.empty();
                        if (data.length > 0) {
                            $noResultsMsg.hide();
                            data.forEach(function(product) {
                                let productHtml = '',
                                    displayStock = '';
                                if (authUser > 2) {
                                    displayStock = '';
                                } else {
                                    displayStock =
                                        ` | ${product.qty} {{ __('db.In Stock') }} `;
                                }
                                var batch_id = product.product_batch_id ? product
                                    .product_batch_id : '';

                                if (product.is_imei == '1' || product.is_imei === 1 || product
                                    .is_imei === true) {
                                    let imeiNumbersArray = [],
                                        exists = false;
                                    $('.imei-number').each(function() {
                                        let val = $(this).val();
                                        if (val) {
                                            imeiNumbersArray = val.split(",");
                                            if (imeiNumbersArray.includes(product
                                                    .imei_number)) {
                                                exists = true;
                                                return;
                                            }
                                        }
                                    });
                                    if (!exists && product.imei_number.length > 0) {
                                        productHtml =
                                            `<div class="product-img" data-code="${product.code}" data-qty="${product.qty}" data-imei="${product.imei_number}" data-embedded="${product.is_embeded}" data-batch="${batch_id}" data-type="${type}" data-price="${product.price}">${product.name} (${product.code}) | ${product.price} | IMEI: ${product.imei_number}</div>`;
                                    } else {
                                        $noResultsMsg.show();
                                    }
                                } else if (product.product_batch_id != null) {
                                    if (parseInt(product.qty) > 0) {
                                        if (product.expired_date == 0) {
                                            product.expired_date = "{{ __('db.expired') }}";
                                            var expired = "expired";
                                        }
                                        productHtml =
                                            `<div class="product-img ${expired}" data-code="${product.code}" data-qty="${product.qty}" data-imei="${product.is_imei}" data-embedded="${product.is_embeded}" data-type="${type}" data-batch="${batch_id}" data-price="${product.price}">${product.name} (${product.code}) - ${product.expired_date} | ${product.price} ${displayStock}</div>`;
                                    }
                                } else {
                                    productHtml =
                                        `<div class="product-img" data-code="${product.code}" data-qty="${product.qty}" data-imei="${product.is_imei}" data-embedded="${product.is_embeded}" data-type="${type}" data-batch="${batch_id}" data-price="${product.price}">${product.name} (${product.code}) | ${product.price} ${displayStock}</div>`;
                                }
                                $resultsContainer.append(productHtml);
                            });
                            $('.product-img').on('click', function() {
                                clearResults(type);
                            });
                            if (data.length === 1 && click === 0) {
                                if (type == 'return') {
                                    $('#sale_product-results-container .product-img').first().trigger(
                                        'click');
                                } else {
                                    $('#product-results-container .product-img').first().trigger(
                                        'click');
                                }
                                clearResults(type);
                                click = 1;
                            }
                        } else {
                            clearResults(type);
                            $noResultsMsg.show();
                        }
                    },
                    error: function() {
                        $noResultsMsg.text("Error searching products.").show();
                    }
                });
            }

            var click = 0;
            $newProductInput.on('input', function() {
                const value = $(this).val().trim();
                if (value.length >= 3) {
                    click = 0;
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(() => searchProducts(value, 'new'), doneTypingInterval);
                } else {
                    clearResults('new');
                }
            });
            $newProductInput.on('paste', function(e) {
                const pastedData = (e.originalEvent || e).clipboardData.getData('text');
                if (pastedData.length >= 3) {
                    click = 0;
                    searchProducts(pastedData.trim(), 'new');
                }
            });
            $saleProductInput.on('input', function() {
                const value = $(this).val().trim();
                if (value.length >= 3) {
                    click = 0;
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(() => searchProducts(value, 'return'), doneTypingInterval);
                } else {
                    clearResults('return');
                }
            });
            $saleProductInput.on('paste', function(e) {
                const pastedData = (e.originalEvent || e).clipboardData.getData('text');
                if (pastedData.length >= 3) {
                    click = 0;
                    searchProducts(pastedData.trim(), 'return');
                }
            });
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#product-results-container, #product-search-input').length) {
                    clearResults('new');
                }
                if (!$(e.target).closest('#sale_product-results-container, #sale-product-search-input')
                    .length) {
                    clearResults('return');
                }
            });
        });
    </script>

    <script>
        const closeScannerBtn = document.getElementById("closeScannerBtn");
        const html5Qrcode = new Html5Qrcode('reader');
        let currentScannerType = 'new';

        function barcodeNew() {
            currentScannerType = 'new';
            startScanner();
        }

        function barcodeSale() {
            currentScannerType = 'return';
            startScanner();
        }

        function startScanner() {
            const qrCodeSuccessCallback = (decodedText) => {
                if (decodedText) {
                    if (currentScannerType === 'return') {
                        document.getElementById('sale-product-search-input').value = decodedText;
                    } else {
                        document.getElementById('product-search-input').value = decodedText;
                    }
                    html5Qrcode.stop();
                    closeScannerBtn.style.display = "none";
                }
            };
            const config = {
                fps: 30,
                qrbox: {
                    width: 300,
                    height: 100
                }
            };
            html5Qrcode.start({
                facingMode: "environment"
            }, config, qrCodeSuccessCallback);
            closeScannerBtn.style.display = "inline-block";
        }
        closeScannerBtn.addEventListener("click", function() {
            closeScannerBtn.style.display = "none";
            html5Qrcode.stop();
        });
    </script>

    <script type="text/javascript">
        @if (config('database.connections.saleprosaas_landlord'))
            @if (isset($numberOfInvoice))
                numberOfInvoice = <?php echo json_encode($numberOfInvoice); ?>;
                $.ajax({
                    type: 'GET',
                    async: false,
                    url: '{{ route('package.fetchData', gen_setting()->package_id) }}',
                    success: function(data) {
                        if (data['number_of_invoice'] > 0 && data['number_of_invoice'] <= numberOfInvoice) {
                            localStorage.setItem("message",
                                "You don't have permission to create another invoice as you already exceed the limit! Subscribe to another package if you wants more!"
                            );
                            location.href = "{{ route('sales.index') }}";
                        }
                    }
                });
            @endif
        @endif

        var currency = <?php echo json_encode($currency); ?>;
        var currencyChange = false;
        var without_stock = <?php echo json_encode(gen_setting()->without_stock); ?>;
        var authUser = <?php echo json_encode($authUser); ?>;
        var decimal = <?php echo json_encode(gen_setting()->decimal); ?>;
        var exchangeValue = 0,
            newProductsTotal = 0;
        $('#currency').val(currency['id']);

        $('#currency').change(function() {
            var rate = $(this).find(':selected').data('rate');
            $('#exchange_rate').val(rate);
            currency['exchange_rate'] = rate;
            $("table.order-list tbody .qty").each(function(index) {
                rowindex = index;
                currencyChange = true;
                cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) +
                    ') .product-id').val();
                qty = $(this).val();
                $.get('/product-price/' + cur_product_id, function(response) {
                    checkDiscount(qty, true, 'table.order-list', response.price);
                });
            });
        });

        function setCustomerGroupRate(id) {
            if (!id) {
                customer_group_rate = 0;
                return;
            }

            $.get('{{ url('exchange/getcustomergroup') }}/' + id, function(data) {
                customer_group_rate = (data / 100);
            });
        }
        $('select[name="customer_id"]').val($('input[name="customer_id_hidden"]').val());
        $('select[name="warehouse_id"]').val($('input[name="warehouse_id_hidden"]').val());
        $('select[name="biller_id"]').val($('input[name="biller_id_hidden"]').val());
        $('select[name="order_tax_rate"]').val($('input[name="order_tax_rate_hidden"]').val());
        $('.selectpicker').selectpicker('refresh');

        $(window).on('load', function() {
            setCustomerGroupRate($('#customer_id').val());
            recalculateAll();
        });

        var lims_product_array = [],
            product_code = [],
            product_name = [],
            product_qty = [],
            product_type = [],
            product_id = [],
            product_list = [],
            variant_list = [],
            qty_list = [];
        var product_price = [],
            wholesale_price = [],
            cost = [],
            product_discount = [],
            tax_rate = [],
            tax_name = [],
            tax_method = [],
            unit_name = [],
            unit_operator = [],
            unit_operation_value = [];
        var is_imei = [],
            is_variant = [],
            gift_card_amount = [],
            gift_card_expense = [],
            temp_unit_name = [],
            temp_unit_operator = [],
            temp_unit_operation_value = [];
        var exist_type = [],
            exist_code = [],
            exist_qty = [],
            rowindex, customer_group_rate, row_product_price, pos, role_id = <?php echo json_encode(Auth::user()->role_id); ?>;
        var warehouse_id = $('#warehouse_id').val();
        var rownumber = $('table.order-list tbody tr:last').index();

        for (rowindex = 0; rowindex <= rownumber; rowindex++) {
            product_price.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                '.product-price').val()));
            exist_code.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)')
                .text());
            exist_type.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-type').val());
            var total_discount = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                '.discount').text());
            var quantity = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
            exist_qty.push(quantity);
            product_discount.push((total_discount / quantity).toFixed({{ gen_setting()->decimal }}));
            tax_rate.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate')
                .val()));
            tax_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-name').val());
            tax_method.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-method').val());
            temp_unit_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val()
                .split(',');
            unit_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val());
            unit_operator.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit-operator')
                .val());
            unit_operation_value.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                '.sale-unit-operation-value').val());
            if (!$('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val().includes(null))
                is_imei.push(1);
            else is_imei.push(0);
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(temp_unit_name[0]);
        }

        $('.selectpicker').selectpicker({
            style: 'btn-link'
        });
        $('[data-toggle="tooltip"]').tooltip();
        $('select[name="customer_id"]').on('change', function() {
            setCustomerGroupRate($(this).val());
        });

        // ===== CALCULATION FUNCTIONS (Pure - No Recursion) =====
        function calculateExchangeValue() {
            exchangeValue = 0;
            $('table.return-order-list input.exchange-checkbox:checked').each(function() {
                var subtotalText = $(this).closest('tr').find('.sub-total').text();
                // ✅ কমা রিমুভ করে পার্স করুন
                var subtotal = parseFloat(subtotalText.replace(/,/g, '')) || 0;
                exchangeValue += subtotal;
            });
            $('#exchange-value').text(exchangeValue.toFixed(decimal));
        }

        function calculateNewProductsTotal() {
            newProductsTotal = 0;
            $('table.order-list tbody tr').each(function() {
                // ✅ একইভাবে কমা হ্যান্ডেল করুন
                var subtotal = parseFormattedNumber($(this).find('.sub-total').text());
                newProductsTotal += subtotal;
            });
            $('#new-products-total').text(newProductsTotal.toFixed(decimal));
        }

        function calculateExchangeBalance() {
            // ✅ শিপিং ও ডিসকাউন্ট পার্স করার সময়ও হেল্পার ব্যবহার করুন
            var shippingCost = parseFormattedNumber($('input[name="shipping_cost"]').val());
            var returnDiscount = parseFormattedNumber($('input[name="total_sale_discount"]').val());

            var grandTotal = newProductsTotal + shippingCost;
            var adjustedExchangeValue = exchangeValue - returnDiscount;
            var balance = grandTotal - adjustedExchangeValue;

            $('#exchange-shipping').text(shippingCost.toFixed(decimal));
            $('#new-grand-total').text(grandTotal.toFixed(decimal));
            $('.exchage_grand_total').val(grandTotal.toFixed(decimal));

            var balanceDisplay = $('#balance-display'),
                balanceStatus = $('#balance-status'),
                paymentType = $('#payment-type'),
                balanceAmount = $('#amount');

            balanceDisplay.text(Math.abs(balance).toFixed(decimal));
            balanceAmount.val(Math.abs(balance).toFixed(decimal));

            if (balance > 0) {
                balanceDisplay.css('color', '#d32f2f');
                balanceStatus.text('(Customer Pays)').css('color', '#d32f2f');
                paymentType.val('receive');
            } else if (balance < 0) {
                balanceDisplay.css('color', '#388e3c');
                balanceStatus.text('(Refund to Customer)').css('color', '#388e3c');
                paymentType.val('pay');
            } else {
                balanceDisplay.css('color', '#1565c0');
                balanceStatus.text('(No Balance)').css('color', '#1565c0');
            }
        }

        function calculateTotal() {
            var total_qty = 0,
                total_discount = 0,
                total_tax = 0,
                total = 0;

            $("table.order-list tbody .qty, table.return-order-list tbody .qty").each(function() {
                total_qty += parseFloat($(this).val() || 0);
            });
            $(".discount-value").each(function() {
                total_discount += parseFloat($(this).val() || 0);
            });
            $(".tax-value").each(function() {
                total_tax += parseFloat($(this).val() || 0);
            });
            // ✅ সাবটোটাল পার্স করার সময়ও parseFormattedNumber ব্যবহার করুন
            $(".sub-total").each(function() {
                total += parseFormattedNumber($(this).text());
            });

            $("#total-qty").text(total_qty);
            $('input[name="total_qty"]').val(total_qty);
            $("#total-discount").text(total_discount.toFixed(decimal));
            $('input[name="total_discount"]').val(total_discount.toFixed(decimal));
            $("#total-tax").text(total_tax.toFixed(decimal));
            $('input[name="total_tax"]').val(total_tax.toFixed(decimal));
            $("#total").text(total.toFixed(decimal));
            $('input[name="total_price"]').val(total.toFixed(decimal));
        }

        function parseFormattedNumber(str) {
            if (!str) return 0;
            return parseFloat(String(str).replace(/,/g, '').trim()) || 0;
        }

        function calculateGrandTotal() {
            var item = $('table.order-list tbody tr:last').index();
            if (item == -1) {
                $('#order-discount-val').val(0);
            }
            var total_qty = parseFloat($('input[name="total_qty"]').val());
            var subtotal = parseFloat($('input[name="total_price"]').val());
            var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
            var order_discount_type = $('select[name="order_discount_type"]').val();
            var order_discount_value = parseFloat($('input[name="order_discount_value"]').val());
            if (!order_discount_value) order_discount_value = {{ number_format(0, gen_setting()->decimal, '.', '') }};
            var order_discount = (order_discount_type == 'Flat') ? (currencyChange ? parseFloat(order_discount_value *
                currency['exchange_rate']) : parseFloat(order_discount_value)) : parseFloat(subtotal * (
                order_discount_value / 100));
            $("#discount").text(order_discount_value.toFixed(decimal));
            $('input[name="order_discount"]').val(order_discount);
            $('#order-discount-val').val(order_discount_value);
            $('input[name="order_discount_type"]').val(order_discount_type);
            var shipping_cost = currencyChange ? parseFloat($('input[name="shipping_cost"]').val() * currency[
                'exchange_rate']) : parseFloat($('input[name="shipping_cost"]').val());
            if (!shipping_cost) shipping_cost = {{ number_format(0, gen_setting()->decimal, '.', '') }};
            item = ++item + '(' + total_qty + ')';
            order_tax = (subtotal - order_discount) * (order_tax / 100);
            var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;
            var coupon_discount = currencyChange ? parseFloat($('input[name="coupon_discount"]').val() * currency[
                'exchange_rate']) : parseFloat($('input[name="coupon_discount"]').val());
            if (!coupon_discount) coupon_discount = {{ number_format(0, gen_setting()->decimal, '.', '') }};
            grand_total -= coupon_discount;
            $('#item').text(item);
            $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
            $('#subtotal').text(subtotal.toFixed(decimal));
            $('#order_tax').text(order_tax.toFixed(decimal));
            $('#tax').text(order_tax.toFixed(decimal));
            $('input[name="order_tax"]').val(order_tax.toFixed(decimal));
            $('#order_discount').text(order_discount.toFixed(decimal));
            $('#shipping_cost').text(shipping_cost.toFixed(decimal));
            $('input[name="shipping_cost"]').val(shipping_cost);
            $('#grand_total').text(grand_total.toFixed(decimal));
            $('input[name="grand_total"]').val(grand_total.toFixed(decimal));
            currencyChange = false;
        }

        // ✅ MASTER FUNCTION - শুধুমাত্র Event Handler থেকে কল হবে
        function recalculateAll() {
            calculateExchangeValue();
            calculateNewProductsTotal();
            calculateExchangeBalance();
            calculateTotal();
            calculateGrandTotal();
        }

        // ===== EVENT HANDLERS =====
        $(document).on('change', 'input.exchange-checkbox', function() {
            recalculateAll();
        });
        $('input[name="shipping_cost"]').on('blur change', function() {
            recalculateAll();
        });
        $('select[name="order_tax_rate"]').on("change", function() {
            recalculateAll();
        });
        $('select[name="order_discount_type"]').on("change", function() {
            recalculateAll();
        });
        $('input[name="order_discount_value"]').on("blur", function() {
            recalculateAll();
        });
        // ✅ Return Discount পরিবর্তন হলে ক্যালকুলেশন রান করুন
        $('input[name="total_sale_discount"]').on('blur change keyup', function() {
            var val = parseFloat($(this).val());
            if (isNaN(val) || val < 0) {
                $(this).val(0);
            }
            recalculateAll();
        });

        // Delete product
        $(document).on("click", "table tbody .ibtnDel", function(event) {
            rowindex = $(this).closest('tr').index();
            product_price.splice(rowindex, 1);
            wholesale_price.splice(rowindex, 1);
            product_discount.splice(rowindex, 1);
            tax_rate.splice(rowindex, 1);
            tax_name.splice(rowindex, 1);
            tax_method.splice(rowindex, 1);
            unit_name.splice(rowindex, 1);
            unit_operator.splice(rowindex, 1);
            unit_operation_value.splice(rowindex, 1);
            is_imei.splice(rowindex, 1);
            is_variant.splice(rowindex, 1);
            $(this).closest("tr").remove();
            recalculateAll();
        });

        // Edit product
        $("table.order-list, table.return-order-list").on("click", ".edit-product", function() {
            rowindex = $(this).closest('tr').index();
            edit();
        });
        $('button[name="update_btn"]').on("click", function() {
            var $row = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
            var tableSelector = 'table.order-list';
            if ($row.length === 0) {
                $row = $('table.return-order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
                tableSelector = 'table.return-order-list';
            }
            if (is_imei[rowindex]) {
                var imeiNumbers = '';
                $("#editModal .imei-numbers").each(function(i) {
                    if (i) imeiNumbers += ',' + $(this).val();
                    else imeiNumbers = $(this).val();
                });
                $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(
                    imeiNumbers);
            }
            var edit_discount = $('input[name="edit_discount"]').val(),
                edit_qty = $('input[name="edit_qty"]').val(),
                edit_unit_price = $('input[name="edit_unit_price"]').val();
            if (parseFloat(edit_discount) > parseFloat(edit_unit_price)) {
                alert('Invalid Discount Input!');
                return;
            }
            if (edit_qty < 0) {
                $('input[name="edit_qty"]').val(1);
                edit_qty = 1;
                alert("Quantity can't be less than 0");
            }
            var tax_rate_all = <?php echo json_encode($tax_rate_all); ?>;
            tax_rate[rowindex] = parseFloat(tax_rate_all[$('select[name="edit_tax_rate"]').val()]);
            tax_name[rowindex] = $('select[name="edit_tax_rate"] option:selected').text();
            var product_type = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .product_type').val();
            product_discount[rowindex] = $('input[name="edit_discount"]').val();
            if (product_type == 'standard') {
                row_unit_operator = $('#edit_unit select').find(':selected').data('operator');
                row_unit_operation_value = $('#edit_unit select').find(':selected').data('operation-value');
                if (row_unit_operator == '*') {
                    product_price[rowindex] = $('input[name="edit_unit_price"]').val() * row_unit_operation_value;
                } else {
                    product_price[rowindex] = $('input[name="edit_unit_price"]').val() / row_unit_operation_value;
                }
                var position = $('select[name="edit_unit"]').val();
                var temp_operator = temp_unit_operator[position],
                    temp_operation_value = temp_unit_operation_value[position];
                $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(
                    temp_unit_name[position]);
                temp_unit_name.splice(position, 1);
                temp_unit_operator.splice(position, 1);
                temp_unit_operation_value.splice(position, 1);
                temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
                temp_unit_operator.unshift(temp_operator);
                temp_unit_operation_value.unshift(temp_operation_value);
                unit_name[rowindex] = temp_unit_name.toString() + ',';
                unit_operator[rowindex] = temp_unit_operator.toString() + ',';
                unit_operation_value[rowindex] = temp_unit_operation_value.toString() + ',';
            } else {
                product_price[rowindex] = $('input[name="edit_unit_price"]').val();
            }
            checkDiscount(edit_qty, false, tableSelector);
            $('#editModal').modal('hide');
            recalculateAll();
        });

        // Plus/Minus buttons
        $(document).on('click', 'table .plus', function() {
            rowindex = $(this).closest('tr').index();
            var $row = $(this).closest('tr'),
                tableSelector = 'table.order-list';
            if ($row.closest('table').hasClass('return-order-list')) {
                tableSelector = 'table.return-order-list';
            }
            var qty = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val();
            var max_qty = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').attr('max');
            if (!qty) qty = 1;
            else if (max_qty && qty >= max_qty) {
                alert("Quantity cannot exceed available stock (" + max_qty + ").");
                return;
            } else qty = parseFloat(qty) + 1;
            if (is_variant[rowindex]) {
                checkQuantity(String(qty), true, tableSelector);
            } else {
                checkDiscount(qty, true, tableSelector);
            }
        });
        $(document).on('click', 'table .minus', function() {
            rowindex = $(this).closest('tr').index();
            var $row = $(this).closest('tr'),
                tableSelector = 'table.order-list';
            if ($row.closest('table').hasClass('return-order-list')) {
                tableSelector = 'table.return-order-list';
            }
            var qty = parseFloat($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) - 1;
            if (qty > 0) {
                $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
                if (is_variant[rowindex]) checkQuantity(String(qty), true, tableSelector);
                else checkDiscount(qty, '3', tableSelector);
            } else {
                qty = 1;
            }
        });

        // Batch change
        $("#myTable").on("change", ".batch-no", function() {
            rowindex = $(this).closest('tr').index();
            var $row = $(this).closest('tr'),
                tableSelector = 'table.order-list';
            if ($row.closest('table').hasClass('return-order-list')) {
                tableSelector = 'table.return-order-list';
            }
            var product_id = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-id')
                .val();
            var warehouse_id = $('#warehouse_id').val();
            $.get('{{ url('/check-batch-availability') }}/' + product_id + '/' + $(this).val() + '/' +
                warehouse_id,
                function(data) {
                    if (data['message'] != 'ok') {
                        alert(data['message']);
                        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.batch-no').val(
                            '');
                        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                            '.product-batch-id').val('');
                        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.expired-date')
                            .text('');
                    } else {
                        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                            '.product-batch-id').val(data['product_batch_id']);
                        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.expired-date')
                            .text(data['expired_date']);
                        code = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                            '.product-code').val();
                        pos = product_code.indexOf(code);
                        product_qty[pos] = data['qty'];
                    }
                });
        });

        // Product click - STOCK CHECK ONLY FOR NEW PRODUCT
        $(document).on('click', '.product-img', function() {
            var data = $(this).data();
            // ✅ শুধুমাত্র new product-এ স্টক চেক
            if (data.type === 'new' && without_stock == 'no') {
                var stock_qty = parseFloat(data.qty);
                if (stock_qty <= 0) {
                    alert('{{ __('db.out_of_stock') }}');
                    clearResults('new');
                    return;
                }
            }
            productSearch(data);
        });

        function productSearch(data) {
            var product_type = data.type;
            if (data.embedded == 1) {
                alert('{{ __('db.product_added_using_scale_weight') }}');
                return;
            }
            // ✅ শুধুমাত্র new product-এ স্টক চেক
            if (data.type === 'new' && without_stock == 'no' && parseFloat(data[19]) <= 0) {
                alert('⚠️ Out of Stock!');
                $('#product-search-input').val('');
                clearResults('new');
                return;
            }
            var item_code = data.code,
                pre_qty = 0,
                flag = true;
            var tableSelector = 'table.order-list';
            if (product_type === 'return') {
                tableSelector = 'table.return-order-list';
            }
            $(".product-code").each(function(i) {
                if ($(this).val().trim() == item_code) {
                    rowindex = i;
                    if (data.imei != 'null' && data.imei != '') {
                        imeiNumbers = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .imei-number')
                            .val();
                        if (imeiNumbers && imeiNumbers.trim() !== '') {
                            imeiNumbersArray = imeiNumbers.split(",");
                            if (imeiNumbersArray.includes(data.imei)) {
                                alert('Same imei or serial number is not allowed!');
                                flag = false;
                                $('#product-search-input').val('');
                                $('#sale-product-search-input').val('');
                                return;
                            }
                        }
                    }
                    pre_qty = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val();
                }
            });
            if (flag) {
                let product = {
                    code: data.code,
                    qty: data.qty,
                    pre_qty: (parseFloat(pre_qty) + 1),
                    imei: data.imei,
                    embedded: data.embedded,
                    batch: data.batch,
                    price: data.price,
                    customer_id: $('#customer_id').val()
                };
                $.ajax({
                    type: 'GET',
                    async: false,
                    url: '{{ url('sales/lims_product_search') }}',
                    data: {
                        data: product
                    },
                    success: function(data) {
                        if (data[23]) {
                            data[15] = 1;
                            pre_qty = 0;
                        }
                        if (pre_qty > 0 && data[21]) {
                            var old_batch = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')')
                                .find('.batch-no').val();
                            if (old_batch && old_batch != data[22]) {
                                pre_qty = 0;
                                data[15] = 1;
                            }
                        }
                        var flag = 1;
                        if (pre_qty > 0) {
                            var qty = data[15];
                            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
                            product_price[rowindex] = parseFloat(data[2] * currency['exchange_rate']) +
                                parseFloat(data[2] * currency['exchange_rate'] * customer_group_rate);
                            checkDiscount(String(qty), true, tableSelector);
                            flag = 0;
                        }
                        $("input[name='product_code_name']").val('');
                        $("input[name='sale_product_code_name']").val('');
                        if (flag) {
                            addNewProduct(data, tableSelector);
                        } else if (data[18] != 'null' && data[18] != '') {
                            var imeiNumbers = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')')
                                .find('.imei-number').val();
                            if (imeiNumbers) imeiNumbers += ',' + data[18];
                            else imeiNumbers = data[18];
                            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                                '.imei-number').val(imeiNumbers);
                        }
                    }
                });
            }
        }

        function addNewProduct(data, tableSelector) {
            // ✅ ডাটা ভ্যালিডেশন - NaN প্রতিরোধ
            var productName = (data[0] && data[0] != 'null') ? data[0] : 'Unknown Product';
            var productCode = (data[1] && data[1] != 'null') ? data[1] : '';
            var productPrice = parseFormattedNumber(data[2]) || 0;
            var productQty = parseFormattedNumber(data[15]) || 1;
            var maxStock = parseFormattedNumber(data[19]) || 99999;
            var productType = data[20] || 'standard';
            var isImei = data[13] || 0;
            var isVariant = data[14] || 0;
            var imeiNumber = (data[18] && data[18] != 'null') ? data[18] : '';

            var sale_type = (tableSelector === 'table.return-order-list') ? 'return' : 'new';
            $('.payment-btn').removeAttr('disabled');

            var newRow = $('<tr id=' + productCode + '>');
            var cols = '';
            var temp_unit_name = (data[6] || '').split(',');
            pos = product_code.indexOf(productCode);

            // ✅ স্টক ডিসপ্লে লজিক
            let stockDisplay = '';
            if (authUser <= 2 && (productType == 'standard' || productType == 'combo')) {
                if (!imeiNumber || imeiNumber == 'null') {
                    stockDisplay = ' | {{ __('db.In Stock') }} : <span class="in-stock">' + maxStock + '</span>';
                }
            }

            // ✅ প্রোডাক্ট টাইটেল - নাম সঠিকভাবে ডিসপ্লে
            if (authUser > 2) {
                cols += '<td class="product-title"><strong>' + productName + '<br><span>' + productCode + '</span>' +
                    stockDisplay + ' <strong class="product-price d-md-none"></strong>';
            } else {
                cols +=
                    '<td class="product-title"><strong class="edit-product btn btn-link pl-0 pr-0" data-toggle="modal" data-target="#editModal">' +
                    productName + ' <i class="ti ti-edit"></i></strong><br><span>' + productCode + '</span>' +
                    stockDisplay + ' <strong class="product-price d-md-none"></strong>';
            }

            // ✅ ব্যাচ/IMEI ডিসপ্লে
            if (data[12]) {
                cols +=
                    '<br><input style="font-size:13px;padding:3px 25px 3px 10px;height:30px !important" type="text" class="form-control batch-no" value="' +
                    (data[22] || '') +
                    '" required/> <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="' + (data[
                        21] || '') + '"/>';
            } else {
                cols +=
                    '<input type="text" class="form-control batch-no d-none" disabled/> <input type="hidden" class="product-batch-id" name="product_batch_id[]"/>';
            }
            cols += '</td>';

            // ✅ কোয়ান্টিটি ইনপুট - NaN প্রতিরোধ
            cols += '<td><div class="input-group"><span class="input-group-btn">';
            if (!imeiNumber || imeiNumber == 'null') {
                cols +=
                    '<button type="button" class="btn btn-default minus mr-1" style="padding:5px 8px"><i class="ti ti-minus"></i></button></span>';
            }
            cols +=
                '<input type="number" name="qty[]" class="form-control qty numkey input-number" style="font-size:13px;max-width:50px;padding:0 0;text-align:center" step="any" value="' +
                productQty + '" max="' + maxStock + '" required><span class="input-group-btn">';
            if (!imeiNumber || imeiNumber == 'null') {
                cols +=
                    '<button type="button" class="btn btn-default plus ml-1" style="padding:5px 8px"><i class="ti ti-plus"></i></button>';
            }
            cols += '</span></div></td>';

            // ✅ প্রাইস কলাম - NaN প্রতিরোধ
            cols += '<td class="product-price">' + productPrice.toFixed(decimal) + '</td>';
            cols += '<td class="discount">0.00</td>';
            cols += '<td class="tax">0.00</td>';
            cols += '<td class="sub-total">' + productPrice.toFixed(decimal) + '</td>';

            // ✅ এক্সচেঞ্জ চেকবক্স / ডিলিট বাটন
            if (tableSelector === 'table.return-order-list') {
                cols += '<td class="is-exchange"><input type="checkbox" id="exchange_' + productCode +
                    '" name="is_exchange[]" class="exchange-checkbox" checked value="' + productCode +
                    '" onchange="calculateExchangeValue()" style="width: 18px; height: 18px; cursor: pointer;"></td>';
            } else {
                cols +=
                    '<td><button type="button" class="ibtnDel btn btn-danger btn-sm mr-2"><i class="ti ti-trash"></i></button></td>';
            }

            // ✅ হিডেন ফিল্ডস - সব ভ্যালু NaN-প্রুফ
            cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + productCode + '"/>';
            cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + (data[9] || '') + '"/>';
            cols += '<input type="hidden" class="product_type" name="product_type[]" value="' + productType + '"/>';
            cols += '<input type="hidden" class="product_price" value="' + productPrice + '"/>';
            cols += '<input type="hidden" class="sale-unit" name="sale_unit[]" value="' + (temp_unit_name[0] || 'n/a') +
                '"/>';
            cols += '<input type="hidden" class="net_unit_price" name="net_unit_price[]" value="' + productPrice.toFixed(
                decimal) + '"/>';
            cols += '<input type="hidden" class="discount-value" name="discount[]" value="0.00"/>';
            cols += '<input type="hidden" class="tax-rate" name="tax_rate[]" value="' + parseFormattedNumber(data[3]) +
                '"/>';
            cols += '<input type="hidden" class="tax-value" name="tax[]" value="0.00"/>';
            cols += '<input type="hidden" class="tax-name" value="' + (data[4] || 'No Tax') + '" />';
            cols += '<input type="hidden" class="tax-method" value="' + (data[5] || '1') + '" />';
            cols += '<input type="hidden" class="sale-unit-operator" value="' + (data[7] || ',') + '"/>';
            cols += '<input type="hidden" class="sale-unit-operation-value" value="' + (data[8] || ',') + '"/>';
            cols += '<input type="hidden" class="subtotal-value" name="subtotal[]" value="' + productPrice.toFixed(
                decimal) + '"/>';
            cols += '<input type="hidden" name="type[]" value="' + sale_type + '">';

            if (imeiNumber && imeiNumber != 'null') {
                cols += '<input type="hidden" class="imei-number" name="imei_number[]" value="' + imeiNumber + '" />';
            } else {
                cols += '<input type="hidden" class="imei-number" name="imei_number[]" value="" />';
            }

            if (data[23]) {
                cols += '<input type="hidden" class="topping_product" name="topping_product[]" value="" />';
                cols += '<input type="hidden" class="topping-price" name="topping-price" value="" />';
            }

            newRow.append(cols);
            $(tableSelector + ' tbody').prepend(newRow);
            rowindex = 0;

            // ✅ প্রাইস অ্যারে আপডেট - NaN প্রতিরোধ
            var calculatedPrice = productPrice + (productPrice * (customer_group_rate || 0));
            product_price.unshift(calculatedPrice);

            if (data[16]) {
                var wholesalePrice = parseFormattedNumber(data[16]) || 0;
                wholesale_price.unshift(wholesalePrice + (wholesalePrice * (customer_group_rate || 0)));
            } else {
                wholesale_price.unshift(0);
            }

            cost.unshift(parseFormattedNumber(data[17]) || 0);
            product_discount.unshift(0);
            tax_rate.unshift(parseFormattedNumber(data[3]) || 0);
            tax_name.unshift(data[4] || 'No Tax');
            tax_method.unshift(data[5] || '1');
            unit_name.unshift(data[6] || '');
            unit_operator.unshift(data[7] || '');
            unit_operation_value.unshift(data[8] || '');
            is_imei.unshift(isImei);
            is_variant.unshift(isVariant);

            // ✅ রো প্রাইস সেট
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_price').val(calculatedPrice);

            // ✅ ক্যালকুলেশন ট্রিগার
            checkQuantity(String(productQty), true, tableSelector);
            checkDiscount(String(productQty), true, tableSelector);

            if (data[16]) {
                populatePriceOption();
                $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.edit-product').click();
            }

            // ✅ টপিংস লজিক (আপনার এক্সিস্টিং কোড রাখুন)
            if (data[23] && Array.isArray(data[23]) && data[23].length > 0) {
                // ... your toppings code ...
            }

            setTimeout(function() {
                recalculateAll();
            }, 100);
        }

        function populatePriceOption() {
            $('#editModal select[name=price_option]').empty();
            $('#editModal select[name=price_option]').append('<option value="' + product_price[rowindex] + '">' +
                product_price[rowindex] + '</option>');
            if (wholesale_price[rowindex] > 0) $('#editModal select[name=price_option]').append('<option value="' +
                wholesale_price[rowindex] + '">' + wholesale_price[rowindex] + '</option>');
            $('.selectpicker').selectpicker('refresh');
        }

        function getRowUnitData(tableSelector) {
            var $row = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')');
            var operator = ($row.find('.sale-unit-operator').val() || '').split(',');
            var operationValue = ($row.find('.sale-unit-operation-value').val() || '').split(',');

            return {
                operator: operator[0] || '*',
                operationValue: parseFloat(operationValue[0]) || 1
            };
        }

        function getPendingReturnQtyForProduct(productId, productCode) {
            var pendingReturnQty = 0;

            $('table.return-order-list tbody tr').each(function() {
                var $row = $(this);
                var rowProductId = String($row.find('.product-id').val() || '');
                var rowProductCode = String($row.find('.product-code').val() || '');

                if (!$row.find('.exchange-checkbox').is(':checked')) {
                    return;
                }

                if (rowProductId === String(productId || '') || rowProductCode === String(productCode || '')) {
                    var qty = parseFloat($row.find('.qty').val()) || 0;
                    var operator = ($row.find('.sale-unit-operator').val() || '').split(',');
                    var operationValue = ($row.find('.sale-unit-operation-value').val() || '').split(',');
                    var unitOperator = operator[0] || '*';
                    var unitOperationValue = parseFloat(operationValue[0]) || 1;

                    pendingReturnQty += unitOperator === '*' ? qty * unitOperationValue : qty / unitOperationValue;
                }
            });

            return pendingReturnQty;
        }

        function edit() {
            $(".imei-section").remove();
            var tableSelector = 'table.order-list',
                $row = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
            if ($row.length === 0) {
                $row = $('table.return-order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
                tableSelector = 'table.return-order-list';
            }
            if (is_imei[rowindex]) {
                var imeiNumbers = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number')
                    .val();
                if (imeiNumbers && imeiNumbers.length) {
                    imeiArrays = [...new Set(imeiNumbers.split(","))];
                    htmlText =
                        `<div class="col-md-8 form-group imei-section"><label>IMEI or Serial Numbers</label><div class="table-responsive"><table id="imei-table" class="table table-hover"><tbody>`;
                    for (var i = 0; i < imeiArrays.length; i++) {
                        htmlText +=
                            `<tr><td><input type="text" class="form-control imei-numbers" name="imei_numbers[]" value="` +
                            imeiArrays[i] +
                            `" /></td><td><button type="button" class="imei-del btn btn-sm btn-danger">X</button></td></tr>`;
                    }
                    htmlText += `</tbody></table></div></div>`;
                    $("#editModal .modal-element").append(htmlText);
                }
            }
            populatePriceOption();
            $("#product-cost").text(cost[rowindex]);
            var row_product_name_code = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                'td:nth-child(1)').text().trim();
            if (!row_product_name_code) {
                row_product_name_code = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find(
                    'td:nth-child(1) strong').text().trim();
            }
            $('#modal_header').text(row_product_name_code);
            var qty = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
            $('input[name="edit_qty"]').val(qty);
            cur_product_id = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
            @if (isset($draft_product_discount))
                if (product_discount[rowindex] < 1) {
                    draft_discounts = @json($draft_product_discount['discount']);
                    product_discount[rowindex] = draft_discounts[cur_product_id];
                }
            @endif
            $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed(
                {{ gen_setting()->decimal }}));
            var tax_name_all = <?php echo json_encode($tax_name_all); ?>;
            pos = tax_name_all.indexOf(tax_name[rowindex]);
            $('select[name="edit_tax_rate"]').val(pos);
            var row_product_code = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code')
                .val();
            var product_type = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();
            if (product_type == 'standard') {
                unitConversion();
                temp_unit_name = (unit_name[rowindex]).split(',');
                temp_unit_name.pop();
                temp_unit_operator = (unit_operator[rowindex]).split(',');
                temp_unit_operator.pop();
                temp_unit_operation_value = (unit_operation_value[rowindex]).split(',');
                temp_unit_operation_value.pop();
                $('select[name="edit_unit"]').empty();
                $.each(temp_unit_name, function(key, value) {
                    $('select[name="edit_unit"]').append('<option data-operator="' + temp_unit_operator[key] +
                        '" data-operation-value="' + temp_unit_operation_value[key] + '" value="' + key + '">' +
                        value + '</option>');
                });
                $("#edit_unit").show();
            } else {
                row_product_price = product_price[rowindex];
                $("#edit_unit").hide();
            }
            $('input[name="edit_unit_price"]').val(row_product_price.toFixed({{ gen_setting()->decimal }}));
            $('.selectpicker').selectpicker('refresh');
        }

        $(document).on("click", "table#imei-table tbody .imei-del", function() {
            var edit_qty = parseFloat($('input[name="edit_qty"]').val());
            edit_qty = (edit_qty - 1);
            $('input[name="edit_qty"]').val(edit_qty);
            var tableSelector = 'table.order-list',
                $row = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
            if ($row.length === 0) {
                $row = $('table.return-order-list tbody tr:nth-child(' + (rowindex + 1) + ')');
                tableSelector = 'table.return-order-list';
            }
            let imeis = $(tableSelector + ' tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(),
                target = $(this).closest("tr").find('.imei-numbers').val();
            $(this).closest("tr").remove();
            let arr = imeis.split(',').map(s => s.trim());
            arr = arr.filter(i => i !== target);
            let updated = arr.join(',');
            $(tableSelector + ' tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(updated);
            if (edit_qty == 0) {
                $('#editModal').modal('hide');
                $(tableSelector + ' tr:eq(' + rowindex + ')').remove();
            }
            $(tableSelector + ' tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(edit_qty);
            checkDiscount(edit_qty, false, tableSelector);
            recalculateAll();
        });

        function checkDiscount(qty, flag, tableSelector = 'table.order-list', price = 0) {
            var customer_id = $('#customer_id').val(),
                warehouse_id = $('#warehouse_id').val(),
                product_id = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
            $.ajax({
                type: 'GET',
                async: false,
                url: '{{ url('/') }}/sales/check-discount?qty=' + qty + '&customer_id=' + customer_id +
                    '&product_id=' + product_id + '&warehouse_id=' + warehouse_id,
                success: function(data) {
                    if (product_price[rowindex].length == 0) {
                        product_price[rowindex] = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) +
                            ') .product_price').val();
                    }
                    product_price[rowindex] = parseFloat(product_price[rowindex] * currency['exchange_rate']) +
                        parseFloat(product_price[rowindex] * currency['exchange_rate'] * customer_group_rate);
                    var productDiscount = parseFloat($('#discount').text());
                    if (flag == true) $('#discount').text(productDiscount + data[2]);
                    else if (flag == false) $('#discount').text(productDiscount - data[2] * qty);
                    else if (flag == 'input') $('#discount').text(productDiscount - data[2] * previousqty +
                        data[2] * qty);
                    else $('#discount').text(productDiscount - data[2]);
                }
            });
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
            flag = true;
            checkQuantity(String(qty), flag, tableSelector);
            // ❌ recalculateAll() কল করবেন না এখানে - checkQuantity করবে
        }

        function checkQuantity(sale_qty, flag, tableSelector = 'table.order-list') {
            var $row = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')');
            var row_product_code = $row.find('.product-code').val();
            var row_product_id = $row.find('.product-id').val();
            var qty = parseFloat($row.find('.qty').attr('max'));
            var product_type = $row.find('.product_type').val();
            var isReturnTable = tableSelector.includes('return-order-list');
            // ✅ শুধুমাত্র order-list (new product) এর জন্য স্টক চেক
            if (without_stock == 'no' && !isReturnTable) {
                if (product_type && (product_type.trim() == 'standard' || product_type.trim() == 'combo')) {
                    var unitData = getRowUnitData(tableSelector);
                    var parsedSaleQty = parseFloat(sale_qty) || 0;
                    var total_qty = (unitData.operator == '*') ?
                        parsedSaleQty * unitData.operationValue :
                        parsedSaleQty / unitData.operationValue;
                    var availableQty = (isNaN(qty) ? 0 : qty) + getPendingReturnQtyForProduct(row_product_id, row_product_code);

                    if (total_qty > availableQty) {
                        alert('Quantity exceeds stock quantity!');
                        if (flag) {
                            sale_qty = Math.max(1, parsedSaleQty - 1);
                            checkQuantity(sale_qty, true, tableSelector);
                        } else {
                            edit();
                            return;
                        }
                    }
                }
            }
            $row.find('.qty').val(sale_qty);
            if (!flag) {
                $('#editModal').modal('hide');
            }
            calculateRowProductData(sale_qty, tableSelector);
            // ✅ এখানে recalculateAll() কল করুন
            recalculateAll();
        }

        function unitConversion() {
            var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(",")),
                row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(
                    ","));
            if (row_unit_operator == '*') {
                row_product_price = product_price[rowindex] * row_unit_operation_value;
            } else {
                row_product_price = product_price[rowindex] / row_unit_operation_value;
            }
        }

        function calculateRowProductData(quantity, tableSelector = 'table.order-list') {
            if (product_discount[rowindex] < 1) {
                cur_product_id = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
                @if (isset($draft_product_discount))
                    if (product_discount[rowindex] < 1) {
                        draft_discounts = @json($draft_product_discount['discount']);
                        product_discount[rowindex] = draft_discounts[cur_product_id];
                    }
                @endif
            }
            if (product_type[pos] == 'standard') unitConversion();
            else row_product_price = product_price[rowindex];
            var net_unit_price, tax, sub_total, sub_total_unit;
            if (tax_method[rowindex] == 1) {
                net_unit_price = row_product_price - product_discount[rowindex];
                tax = net_unit_price * quantity * (tax_rate[rowindex] / 100);
                sub_total = (net_unit_price * quantity) + tax;
                sub_total_unit = parseFloat(quantity) ? sub_total / quantity : sub_total;
            } else {
                sub_total_unit = row_product_price - product_discount[rowindex];
                net_unit_price = (100 / (100 + tax_rate[rowindex])) * sub_total_unit;
                tax = (sub_total_unit - net_unit_price) * quantity;
                sub_total = sub_total_unit * quantity;
            }
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount-value').val((product_discount[
                rowindex] * quantity).toFixed({{ gen_setting()->decimal }}));
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val(tax_rate[rowindex]
                .toFixed({{ gen_setting()->decimal }}));
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(net_unit_price
                .toFixed({{ gen_setting()->decimal }}));
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed(
                {{ gen_setting()->decimal }}));
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-price').text(sub_total_unit
                .toFixed({{ gen_setting()->decimal }}));
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total.toFixed({{ gen_setting()->decimal }}));
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(sub_total.toFixed({{ gen_setting()->decimal }}));
            // ❌ calculateTotal(), calculateNewProductsTotal() এখানে কল করবেন না - recalculateAll() করবে
        }

        function cancel(rownumber) {
            while (rownumber >= 0) {
                product_price.pop();
                wholesale_price.pop();
                product_discount.pop();
                tax_rate.pop();
                tax_name.pop();
                tax_method.pop();
                unit_name.pop();
                unit_operator.pop();
                unit_operation_value.pop();
                $('table.order-list tbody tr:last').remove();
                rownumber--;
            }
            $('input[name="shipping_cost"]').val('');
            $('input[name="order_discount_value"]').val('');
            $('select[name="order_tax_rate"]').val(0);
            recalculateAll();
        }

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

        $('#payment-form').on('submit', function(e) {
            var rownumber = $('table.order-list tbody tr:last').index();
            $("table.order-list tbody .qty, table.return-order-list tbody .qty").each(function() {
                if ($(this).val() == '') {
                    alert('One of products has no quantity!');
                    e.preventDefault();
                }
            });
            if (rownumber < 0) {
                alert("Please insert product to order table!");
                e.preventDefault();
            } else if (parseFloat($('input[name="total_qty"]').val()) <= 0) {
                alert('Product quantity is 0');
                e.preventDefault();
            } else {
                $("#submit-button").prop('disabled', true);
                $(".batch-no").prop('disabled', false);
            }
        });

        function toggleExchange(el) {
            const row = el.closest('tr');
            const exchangeInputs = row.querySelectorAll('input[name^="exchange["]');
            exchangeInputs.forEach(input => {
                input.disabled = !el.checked;
            });
        }
        $('#saleSearchBtn').on('click', function() {
            let reference = $('#sale_product_search').val().trim();
            if (reference === '') {
                alert('Please enter a reference number');
                return;
            }

            $.ajax({
                url: "{{ route('sale.exchange.search') }}",
                type: "GET",
                data: {
                    reference: reference
                },
                success: function(res) {
                    if (res.status) {
                        $('#sale-product-table tbody').html(res.html);

                        // ✅ FIX: HTML ইনজেক্ট হওয়ার পর ফুল রিক্যালকুলেশন রান করুন
                        setTimeout(function() {
                            recalculateAll();
                            // ✅ প্রথম প্রোডাক্টের চেকবক্স অটো চেকড করে দিন (অপশনাল)
                            $('table.return-order-list .exchange-checkbox').first().prop(
                                'checked', true).trigger('change');
                        }, 100);
                    } else {
                        alert(res.message || 'Sale not found');
                    }
                },
                error: function() {
                    alert('Error searching reference number');
                }
            });
        });

        document.getElementById('saleSearchBtn').addEventListener('click', function(e) {
            e.preventDefault();

            let ref = document.getElementById('sale_product_search').value;

            if (!ref) {
                alert('Please enter reference number');
                return;
            }

            // reload page with query string
            window.location.href = "{{ route('exchange.create') }}?reference_no=" + encodeURIComponent(ref);
        });
    </script>
    <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
