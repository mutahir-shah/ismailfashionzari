        <?php
        $product_list = explode(',', $lims_product_data->product_list);
        $wastage_percent = explode(',', $lims_product_data->wastage_percent);
        $qty_list = explode(',', $lims_product_data->qty_list);
        $combo_unit_id_list = explode(',', $lims_product_data->combo_unit_id);
        $variant_list = $lims_product_data->variant_list ? explode(',', $lims_product_data->variant_list) : null;
        $price_list = explode(',', $lims_product_data->price_list);
        ?>
        @foreach ($product_list as $key => $id)
            <tr>
                <?php
                $product = App\Models\Product::find($id);
                $stock = App\Models\Product_Warehouse::where([['product_id',$product->id],['warehouse_id',$warehouse_id]])->latest()->first();
                $unit = App\Models\Unit::query()->where('id', $product->unit_id)->first();
                $actual_base_unit = $unit->base_unit ? $unit->base_unit : $unit->id;
                $combo_unit = App\Models\Unit::query()->where('id', $actual_base_unit)->orWhere('base_unit', $actual_base_unit)->get()->unique('id');
                if ($lims_product_data->variant_list && isset($variant_list[$key]) && $variant_list[$key] != "") {
                    $product_variant_data = App\Models\ProductVariant::select('item_code')->FindExactProduct($id, $variant_list[$key])->first();
                    $product->code = $product_variant_data->item_code;
                } else {
                    $variant_list[$key] = '';
                }
                ?>
                <td>{{ $product->name }} [{{ $product->code }}]</td>
                <td>
                    <div class="input-group">
                        <input type="number" class="form-control wastage_percent" name="wastage_percent[]"
                            value="{{ @$wastage_percent[$key] ?? 0 }}" min="0" step="any" />
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                </td>
                <?php
                $is_edit = isset($lims_product_data->reference_no) ? true : false;
                $old_qty_base = 0;
                if ($is_edit && isset($combo_unit_id_list[$key])) {
                    $selected_unit = App\Models\Unit::find($combo_unit_id_list[$key]);
                    if ($selected_unit) {
                        if ($selected_unit->operator == '*') {
                            $old_qty_base = $qty_list[$key] * $selected_unit->operation_value;
                        } else {
                            $old_qty_base = $qty_list[$key] / $selected_unit->operation_value;
                        }
                    } else {
                        $old_qty_base = $qty_list[$key];
                    }
                }
                $current_stock = $stock->qty ?? 0;
                $available_stock = $current_stock + $old_qty_base;
                ?>
                <td>
                    <div class="input-group" style="max-width: unset">
                        <input type="number" class="form-control qty" name="product_qty[]" data-qty="{{ $qty_list[$key] }}"
                            value="{{ $qty_list[$key] ?? 1 }}" step="any" placeholder="Qty" aria-label="Quantity" data-stock="{{ $available_stock }}" data-old_qty="{{ $old_qty_base }}" data-current_stock="{{ $current_stock }}">

                        <div class="input-group-append">
                            <select name="production_unit_ids[]" style="width: 112px; border-top-left-radius: 0; border-bottom-left-radius: 0;"
                                class="form-control production_unit_ids"
                                onchange="calculate_price()">
                                @foreach ($combo_unit as $row)
                                    <option value="{{ $row->id }}"
                                        data-operation_value="{{ $row->operation_value }}"
                                        data-unit_name="{{ $row->unit_name }}"
                                        data-operator="{{ $row->operator }}"
                                        @if (isset($combo_unit_id_list[$key]) && $combo_unit_id_list[$key] == $row->id) selected @elseif(!isset($combo_unit_id_list[$key]) && $product->unit_id == $row->id) selected @endif>
                                        {{ $row->unit_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="stock_list[]" value="{{ $current_stock }}">
                    </div>
                    <div class="mt-1">
                        <span class="text-danger qty-error" style="font-size: 12px; display: block;"></span>
                    </div>
                </td>


                <td><input type="text" class="form-control unit_name" disabled
                        value="{{  $qty_list[$key] ?? 1  }} ({{ $unit->unit_name }})" step="any" /></td>
                <td><input type="number" class="form-control unit_price" name="unit_price[]"
                        value="{{ $price_list[$key] }}" step="any" /></td>
                <td><input type="number" class="form-control subtotal" name="subtotal[]" value="0.00"
                        step="any" /></td>
                <td><button type="button" class="ibtnDel btn btn-danger btn-sm">X</button></td>
                <input type="hidden" class="product-id" name="product_list[]" value="{{ $id }}" />
                <input type="hidden" class="variant-id" name="variant_id[]" value="{{ $variant_list[$key] }}" />
            </tr>
        @endforeach
