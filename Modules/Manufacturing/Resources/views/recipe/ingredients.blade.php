        <?php
        $product_list = explode(',', $lims_product_data->product_list);
        $wastage_percent = explode(',', $lims_product_data->wastage_percent);
        $qty_list = explode(',', $lims_product_data->qty_list);
        $variant_list = explode(',', $lims_product_data->variant_list);
        $price_list = explode(',', $lims_product_data->price_list);
        ?>
        @if(isset($product_list[0]) && trim($product_list[0]) !== '')
        @foreach ($product_list as $key => $id)
            <tr>
                <?php
                $product = App\Models\Product::find($id);
                $combo_unit = App\Models\Unit::query()->where('id', $product->unit_id)->orWhere('base_unit', $product->unit_id)->get()->unique('id');

                if ($lims_product_data->variant_list && $variant_list[$key]) {
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
                <td>
                    <div class="input-group" style="max-width: unset">
                        <input type="number" class="form-control qty" min="0" name="product_qty[]"
                            value="{{ $qty_list[$key] ?? 1 }}" step="any" placeholder="Qty" aria-label="Quantity">

                        <div class="input-group-append">
                            <select name="combo_unit_id[]" style="width: 112px; border-top-left-radius: 0; border-bottom-left-radius: 0;"
                                class="form-control combo_unit_id"
                                onchange="calculate_price()">
                                @foreach ($combo_unit as $row)
                                    <option value="{{ $row->id }}"
                                        data-operation_value="{{ $row->operation_value }}"
                                        data-operator="{{ $row->operator }}"
                                        @if ($lims_product_data->unit_id == $row->id) 'selected' @endif>
                                        {{ $row->unit_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </td>


                <td><input type="number" class="form-control unit_cost" name="product_unit_cost[]"
                        value="{{ $product->cost }}" step="any" /></td>
                <td><input type="number" class="form-control unit_price" name="unit_price[]"
                        value="{{ $price_list[$key] }}" step="any" /></td>
                <td><input type="number" class="form-control subtotal" name="subtotal[]" value="0.00"
                        step="any" /></td>
                <td><button type="button" class="ibtnDel btn btn-danger btn-sm">X</button></td>
                <input type="hidden" class="product-id" name="product_list[]" value="{{ $id }}" />
                <input type="hidden" class="variant-id" name="variant_list[]" value="{{ $variant_list[$key] }}" />
            </tr>
        @endforeach
        @else

        @endif
