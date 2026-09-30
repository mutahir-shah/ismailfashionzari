@extends('backend.layout.top-head')
@push('css')
<style type="text/css">
    body{font-family:'Inter',sans-serif}
    section.pos-section {padding: 10px 0}
    thead tr th {
    background-color: #d6deff;
    }
    svg {
        width: 20px;
        height: 20px;
        stroke: #7c5cc4;
    }
</style>
@endpush

@section('content')

<section id="pos-layout" class="forms pos-section">
    <div class="container">
        <div class="row">
            <div class="col-md-7 pos-form">
                <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                    <div>
                        <strong id="customer"></strong>
                    </div>
                    <a id="btnFullscreen" data-toggle="tooltip" title="Full Screen"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg></a>
                </div>
                <div class="table-responsive transaction-list">
                    <table id="myTable" class="table table-hover table-striped order-list table-fixed">
                        <thead class="d-md-table-header-group">
                            <tr>
                                <th class="col-sm-5 col-6">{{ __('db.product') }}</th>
                                <th class="col-sm-2">{{ __('db.Price') }}</th>
                                <th class="col-sm-3">{{ __('db.Quantity') }}</th>
                                <th class="col-sm-2">{{ __('db.Subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-id"></tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-md-6">
                        <div class="d-flex justify-content-between">
                            <strong class="totals-title">{{ __('db.Items') }}</strong>
                            <strong id="item"></strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="d-flex justify-content-between">
                            <strong class="totals-title">{{ __('db.Total') }}</strong>
                            <strong id="subtotal"></strong>
                        </div>
                    </div>

                    <div class="col-sm-6 col-md-3 mt-3">
                        <div class="d-flex flex-column">
                            <strong class="totals-title">{{ __('db.Discount') }}</strong>
                            <strong id="discount" class="d-block"></strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3 mt-3">
                        <div class="d-flex flex-column">
                            <strong class="totals-title">{{ __('db.Coupon') }}</strong>
                            <strong id="couponText" class="d-block"></strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3 mt-3">
                        <div class="d-flex flex-column">
                            <strong class="totals-title">{{ __('db.Tax') }}</strong>
                            <strong id="tax" class="d-block"></strong>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3 mt-3">
                        <div class="d-flex flex-column">
                            <strong class="totals-title">{{ __('db.Shipping') }}</strong>
                            <strong id="shippingCost" class="d-block"></strong>
                        </div>
                    </div>
                </div>

                <!-- Grand Total -->
                <div class="payment-amount d-md-block mt-3 mb-3">
                    <h2>
                        {{ __('db.Total Payable') }}
                        <span id="totalPayable"></span>
                    </h2>

                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="d-flex flex-column">
                                <strong class="totals-title">{{ __('db.Cash Received') }}</strong>
                                <strong id="CashReceived" class="d-block"></strong>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="d-flex flex-column">
                                <strong class="totals-title">{{ __('db.Total Paying') }}</strong>
                                <strong id="totalPaying" class="d-block"></strong>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="d-flex flex-column">
                                <strong class="totals-title">{{ __('db.Change') }}</strong>
                                <strong id="change" class="d-block"></strong>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="d-flex flex-column">
                                <strong class="totals-title">{{ __('db.Due') }}</strong>
                                <strong id="due" class="d-block"></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side (Empty for now) -->
            <div class="col-md-5"></div>
        </div>
    </div>
</section>

@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        let storageUpdateTimer = null;
        let isShowDisplayData = false;

        async function showDisplayData() {
            if (isShowDisplayData) return; // Prevent simultaneous executions
            isShowDisplayData = true;

            let customer_display_data_array = JSON.parse(localStorage.getItem("customer_display_data_array"));

            // Check if stored data exists
            if (!customer_display_data_array) {
                // console.warn("No stored form data found.");
                return;
            }

            // If products exist inside the object
            if (customer_display_data_array.products && Array.isArray(customer_display_data_array.products)) {
                // Clear tbody before inserting
                $("#tbody-id").empty();

                // Loop products
                customer_display_data_array.products.forEach((p, index) => {
                    $("#tbody-id").append(`
                        <tr>
                            <td class="col-sm-5 col-6">${p.name}</td>
                            <td class="col-sm-2">${p.price}</td>
                            <td class="col-sm-3">${p.qty}</td>
                            <td class="col-sm-2">${p.subtotal}</td>
                        </tr>
                    `);
                });
            }

            $("#customer").text(customer_display_data_array.customer);
            $("#item").text(customer_display_data_array.item);
            $("#subtotal").text(customer_display_data_array.subtotal);
            $("#discount").text(customer_display_data_array.discount);
            $("#couponText").text(customer_display_data_array.couponText);
            $("#tax").text(customer_display_data_array.tax);
            $("#shippingCost").text(customer_display_data_array.shippingCost);
            $("#totalPayable").text(customer_display_data_array.totalPayable);
            $("#CashReceived").text(customer_display_data_array.CashReceived);
            $("#totalPaying").text(customer_display_data_array.totalPaying);
            $("#change").text(customer_display_data_array.change);
            $("#due").text(customer_display_data_array.due);

            isShowDisplayData = false;
        }

        // Load table data initially
        showDisplayData();

        function debounceStorageUpdate() {
            clearTimeout(storageUpdateTimer);
            storageUpdateTimer = setTimeout(() => {
                showDisplayData();
            }, 400); // 400ms debounce time
        }
        // Prevent duplicate updates when localStorage changes rapidly
        window.onstorage = debounceStorageUpdate;
    });
</script>
@endpush
