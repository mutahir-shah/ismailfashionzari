@foreach ($lims_product_sale_data as $key => $product_sale)
    @php
        $product = DB::table('products')->find($product_sale->product_id);
        if (!$product) continue;

        $qty = $product_sale->qty - $product_sale->return_qty;
        $tax = DB::table('taxes')->where('rate', $product_sale->tax_rate)->first();
        $unit = DB::table('units')->find($product_sale->sale_unit_id);

        // Stock display logic
        $stockDisplay = '';
        if (Auth::user()->role_id <= 2) {
            $stockDisplay = "<span class='text-muted'>| In Stock: {$qty}</span>";
        }
    @endphp

    <tr class="return-product-row">
        {{-- Product Name & Code --}}
        <td class="product-title">
            <strong class="edit-product btn btn-link pl-0 pr-0" data-toggle="modal" data-target="#editModal">
                {{ $product->name }} <i class="ti ti-edit"></i>
            </strong>
            <br>
            <span>{{ $product->code }}</span>
            {!! $stockDisplay !!}
            <strong class="product-price d-md-none"></strong>

            {{-- Hidden Fields --}}
            <input type="hidden" class="product-code" name="product_code[]" value="{{ $product->code }}">
            <input type="hidden" class="product-id" name="product_id[]" value="{{ $product->id }}">
            <input type="hidden" class="product_type" name="product_type[]" value="{{ $product->type ?? 'standard' }}">
            <input type="hidden" name="product_sale_id[]" value="{{ $product_sale->id }}">

            {{-- Batch/IMEI Display --}}
            @if($product_sale->product_batch_id)
                <br>
                <small class="text-muted">Batch: {{ $product_sale->batch_no ?? 'N/A' }}</small>
            @endif
            @if($product_sale->imei_number && $product_sale->imei_number != 'null')
                <br>
                <small class="text-muted">IMEI: {{ $product_sale->imei_number }}</small>
            @endif
        </td>

        {{-- Quantity --}}
        <td>
            <div class="input-group">
                <span class="input-group-btn">
                    <button type="button" class="btn btn-default minus mr-1" style="padding:5px 8px">
                        <i class="ti ti-minus"></i>
                    </button>
                </span>
                <input type="number"
                    name="qty[]"
                    class="form-control qty numkey input-number"
                    style="font-size:13px;max-width:50px;padding:0 0;text-align:center"
                    step="any"
                    value="{{ $qty }}"
                    max="{{ $qty }}"
                    onchange="checkQuantity(this.value, true, 'table.return-order-list')">
                <span class="input-group-btn">
                    <button type="button" class="btn btn-default plus ml-1" style="padding:5px 8px">
                        <i class="ti ti-plus"></i>
                    </button>
                </span>
            </div>
        </td>

        {{-- Net Unit Price --}}
        <td class="product-price">
            {{ number_format($product_sale->net_unit_price, gen_setting()->decimal) }}
        </td>

        {{-- Discount --}}
        <td class="discount">
            {{ number_format($product_sale->discount, gen_setting()->decimal) }}
        </td>

        {{-- Tax --}}
        <td class="tax">
            {{ number_format($product_sale->tax, gen_setting()->decimal) }}
        </td>

        {{-- Subtotal --}}
        <td class="sub-total">
            {{ number_format($product_sale->total, gen_setting()->decimal) }}
        </td>

        {{-- Exchange Checkbox --}}
        <td class="is-exchange text-center">
            <input type="checkbox"
                name="is_exchange[]"
                class="exchange-checkbox"
                value="{{ $product->code }}"
                onchange="recalculateAll()"
                style="width:18px;height:18px;cursor:pointer"
                title="Check to include in exchange">
        </td>

        {{-- ===== REQUIRED HIDDEN FIELDS FOR JS ===== --}}
        <input type="hidden" name="type[]" value="return">

        <input type="hidden" class="sale-unit"
            name="sale_unit[]" value="{{ $unit->unit_name ?? 'n/a' }}">

        <input type="hidden" class="net_unit_price"
            name="net_unit_price[]" value="{{ $product_sale->net_unit_price }}">

        <input type="hidden" class="discount-value"
            name="discount[]" value="{{ $product_sale->discount }}">

        <input type="hidden" class="tax-rate"
            name="tax_rate[]" value="{{ $product_sale->tax_rate }}">

        <input type="hidden" class="tax-name"
            value="{{ $tax->name ?? 'No Tax' }}">

        <input type="hidden" class="tax-method"
            value="{{ $product->tax_method ?? 1 }}">

        <input type="hidden" class="tax-value"
            name="tax[]" value="{{ $product_sale->tax }}">

        <input type="hidden" class="subtotal-value"
            name="subtotal[]" value="{{ $product_sale->total }}">

        <input type="hidden" class="imei-number"
            name="imei_number[]" value="{{ $product_sale->imei_number ?? '' }}">

        {{-- Unit Conversion Fields --}}
        @php
            $unitNames = [];
            $unitOperators = [];
            $unitValues = [];
            if ($product->type == 'standard' && $unit) {
                $units = DB::table('units')->where(function($q) use ($product) {
                    $q->where('base_unit', $product->unit_id)
                      ->orWhere('id', $product->unit_id);
                })->get();
                foreach ($units as $u) {
                    $unitNames[] = $u->unit_name;
                    $unitOperators[] = $u->operator;
                    $unitValues[] = $u->operation_value;
                }
            }
        @endphp
        <input type="hidden" class="sale-unit-operator" value="{{ implode(',', $unitOperators) }},">
        <input type="hidden" class="sale-unit-operation-value" value="{{ implode(',', $unitValues) }},">
    </tr>
@endforeach
