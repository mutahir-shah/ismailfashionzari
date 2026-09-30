@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')

@if (in_array('ecommerce', explode(',', $general_setting->modules)) ||
        in_array('restaurant', explode(',', $general_setting->modules)))
    @push('css')
<link rel="stylesheet" href="{{ asset($asset_prefix . 'css/dropzone.css') }}">
        <style>
            .search_result,
            .search_result_addon {
                border: 1px solid #e4e6fc;
                border-radius: 5px;
                overflow-y: scroll;
            }

            .search_result>div,
            .search_result_addon>div,
            .selected_items>div,
            .selected_addons>div {
                border-top: 1px solid #e4e6fc;
                cursor: pointer;
                display: flex;
                align-items: center;
                padding: 10px;
                position: relative;
            }

            .search_result>div>img,
            .search_result_addon>div>img,
            .selected_items>div>img,
            .selected_addons>div>img {
                margin-right: 10px;
                max-width: 40px;
            }

            .search_result>div h4,
            .search_result_addon>div h4,
            .selected_items>div h4,
            .selected_addons>div h4 {
                font-size: 0.9rem;
            }

            .search_result>div i,
            .search_result_addon>div i,
            {
            color: #54b948;
            position: absolute;
            right: 5px;
            top: 30%
            }

            .search_result div:first-child,
            .search_result_addon div:first-child,
            {
            border-top: none
            }

            .selected_items .remove_item,
            .selected_addons .remove_item {
                position: absolute;
                right: 20px;
                top: 20px
            }

            ;

            .delVarOption {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
        </style>
    @endpush
@endif

@section('content')
    <section class="forms">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Edit Recipe') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>
                            <form id="product-form" method="post" action="{{ route('manufacturing.recipes.store') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <h4>Recipe : <small>{{ $lims_product_data->name }}</small></h4>
                                            <input type="hidden" name="p_id" value="{{ $lims_product_data->id }}">
                                        </div>
                                    </div>


                                </div>
                                <div class="row">
                                    <div id="digital" class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('db.Attach File') }} *</strong> </label>
                                            <div class="input-group">
                                                <input type="file" id="file" name="file" class="form-control">
                                            </div>
                                            <span class="validation-msg"></span>
                                        </div>
                                    </div>
                                    <div id="combo" class="col-md-12 mb-1">
                                        <label>{{ __('db.add_Ingredient') }}</label>
                                        <div class="search-box input-group mb-3">
                                            <button class="btn btn-secondary"><i class="ti ti-barcode"></i></button>
                                            <input type="text" name="product_code_name" id="lims_productcodeSearch"
                                                placeholder="{{ __('db.Please type product code and select') }}"
                                                class="form-control" />
                                        </div>

                                        <div class="table-responsive">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('db.product') }}</th>
                                                        <th>{{ __('db.Wastage Percent') }}</th>
                                                        <th>{{ __('db.Quantity') }}</th>
                                                        <th>{{ __('db.Unit Cost') }}</th>
                                                        <th>{{ __('db.Unit Price') }}</th>
                                                        <th>{{ __('db.Sub total') }}</th>
                                                        <th><i class="ti ti-trash"></i></th>
                                                    </tr>

                                                </thead>
                                                <tbody class="combo_product_list_table" id="ingredients-table">
                                                    @php
                                                        $product_list = explode(',', $lims_product_data->product_list);
                                                        $wastage_percent = explode(',', $lims_product_data->wastage_percent);
                                                        $qty_list = explode(',', $lims_product_data->qty_list);
                                                        $variant_list = explode(',', $lims_product_data->variant_list);
                                                        $price_list = explode(',', $lims_product_data->price_list);
                                                        $unit_id = explode(',', $lims_product_data->combo_unit_id);
                                                    @endphp
                                                    @foreach ($product_list as $key => $id)
                                                        <tr>
                                                            <?php
                                                            $product    = App\Models\Product::find($id);
                                                            $combo_unit = App\Models\Unit::query()->where('id', $product->unit_id)->orWhere('base_unit', $product->unit_id)->get()->unique('id');
                                                            $unit       = App\Models\Unit::query()
                                                                                ->where('id', $unit_id[$key])
                                                                                ->first();
                                                            if($unit->operator == '*'){
                                                                $subtotal = $price_list[$key] * ($unit->operation_value * $qty_list[$key] ?? 1);
                                                            }elseif($unit->operator == '/'){
                                                                $subtotal = $price_list[$key] / $unit->operation_value;
                                                            }else{
                                                                $subtotal = $price_list[$key] * 1;
                                                            }

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
                                                                    <input type="number"
                                                                        class="form-control wastage_percent"
                                                                        name="wastage_percent[]"
                                                                        value="{{ @$wastage_percent[$key] ?? 0 }}"
                                                                        min="0" step="any" />
                                                                    <div class="input-group-append">
                                                                        <span class="input-group-text">%</span>
                                                                    </div>
                                                            </td>
                                                            <td>
                                                                <div class="input-group" style="max-width: unset">
                                                                    <input type="number" class="form-control qty"
                                                                        min="0" name="product_qty[]"
                                                                        value="{{ $qty_list[$key] ?? 1 }}" step="any"
                                                                        placeholder="Qty" aria-label="Quantity">
                                                                    <div class="input-group-append">
                                                                        <select name="combo_unit_id[]" style="width: 112px;"
                                                                            class="form-control combo_unit_id"
                                                                            onchange="calculate_price()">
                                                                            @foreach ($combo_unit as $row)
                                                                                <option value="{{ $row->id }}"
                                                                                    data-operation_value="{{ $row->operation_value }}"
                                                                                    data-operator="{{ $row->operator }}"
                                                                                    {{ $row->id ==  $unit_id[$key]  ? 'selected' : '' }}>
                                                                                    {{ $row->unit_name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </td>


                                                            <td><input type="number" class="form-control unit_cost"
                                                                    name="product_unit_cost[]" value="{{ $product->cost }}"
                                                                    step="any" /></td>
                                                            <td><input type="number" class="form-control unit_price"
                                                                    name="unit_price[]" value="{{ $price_list[$key] }}"
                                                                    step="any" /></td>
                                                            <td><input type="number" class="form-control subtotal"
                                                                    name="subtotal[]" value="{{ $subtotal ?? 0 }}" step="any" /></td>
                                                            <td><button type="button"
                                                                    class="ibtnDel btn btn-danger btn-sm">X</button></td>
                                                            <input type="hidden" class="product-id" name="product_list[]"
                                                                value="{{ $id }}" />
                                                            <input type="hidden" class="variant-id" name="variant_list[]" value="{{ $variant_list[$key] }}">
                                                        </tr>
                                                    @endforeach

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <table class="table table-bordered table-condensed totals">
                                            <tr>
                                                <td><strong>{{ __('db.Wastage percent') }}</strong>
                                                    <span class="pull-right"
                                                        id="wastage_percent">{{ number_format(0, $general_setting->decimal, '.', '') }}%</span>
                                                </td>



                                                <td><strong>{{ __('db.Total') }}</strong>
                                                    <span class="pull-right"
                                                        id="total">{{ number_format(0, $general_setting->decimal, '.', '') }}</span>
                                                </td>

                                                <td><strong>{{ __('db.grand total') }}</strong>
                                                    <span class="pull-right"
                                                        id="grand_total">{{ number_format(0, $general_setting->decimal, '.', '') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{ __('db.note') }}</label>
                                            <textarea rows="5" class="form-control" name="note"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group mt-3">
                                            <button type="submit" id="submit-btn"
                                                class="btn btn-primary">{{ __('db.Save') }}</button>
                                        </div>
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
<script type="text/javascript" src="{{ asset($asset_prefix . 'js/dropzone.js') }}"></script>
    <script type="text/javascript">
        calculate_price();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#is_addon").on('click', function() {
            if ($("#is_addon").prop('checked') == false) {
                $('.extra-section,.related-section').css('display', 'block');
            } else {
                $('.extra-section,.related-section').css('display', 'none');
            }
        })

        @if (in_array('ecommerce', explode(',', $general_setting->modules)) ||
                in_array('restaurant', explode(',', $general_setting->modules)))
            $('#search_products').on('input', function() {
                var item = $(this).val();
                $('.search_result').html(
                    '<div class="d-block text-center"><div class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>'
                    );

                if (item.length >= 3) {
                    $.ajax({
                        type: "get",
                        url: "{{ url('search') }}/" + item,
                        success: function(data) {
                            $('.search_result').html('').css('height', '200px');
                            $.each(data, function(key, value) {
                                var image = value.image.split(',');
                                $('.search_result').append('<div data-id="' + value.id +
                                    '"><img src="{{ asset('images/product/small/') }}/' +
                                    image[0] + '"><h4>' + value.name +
                                    '</h4><i class="ti ti-checkmark d-none"></i></div>')
                            })
                        }
                    })
                } else if (item.length < 3) {
                    $('.search_result').html('');
                }
            });

            $(document).on('click', '.search_result div', function() {
                $(this).find('i').removeClass('d-none');
                var selected_item = '<div data-id="' + $(this).data('id') + '">' + $(this).html() +
                    '<span class="remove_item"><i class="ti ti-x"></i></span></div>';
                if ($('.selected_ids').html().indexOf($(this).data('id')) === -1) {
                    $('.selected_items').prepend(selected_item);
                    $('.selected_ids').append($(this).data('id') + ',');
                    $('.selected_items .ti ti-checkmark').addClass('d-none');
                }
            });

            $(document).on('click', '.remove_item', function() {
                var item = $(this).parent().remove();
                var remove_id = $(this).parent().data('id');
                var selected_ids = $('.selected_ids').html().replace(remove_id + ',', '');
                $('.selected_ids').html(selected_ids);

            });
        @endif

        @if (in_array('restaurant', explode(',', $general_setting->modules)))
            $('#search_addons').on('input', function() {
                var item = $(this).val();
                $('.search_result_addon').html(
                    '<div class="d-block text-center"><div class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>'
                    );

                if (item.length >= 3) {
                    $.ajax({
                        type: "get",
                        url: "{{ url('search') }}/" + item,
                        success: function(data) {
                            $('.search_result_addon').html('').css('height', '200px');
                            $.each(data, function(key, value) {
                                var image = value.image.split(',');
                                $('.search_result_addon').append('<div data-id="' + value.id +
                                    '"><img src="{{ asset('images/product/small/') }}/' +
                                    image[0] + '"><h4>' + value.name +
                                    '</h4><i class="ti ti-checkmark d-none"></i></div>')
                            })
                        }
                    })
                } else if (item.length < 3) {
                    $('.search_result_addon').html('');
                }
            });

            $(document).on('click', '.search_result_addon div', function() {
                $(this).find('i').removeClass('d-none');
                var selected_addon = '<div data-id="' + $(this).data('id') + '">' + $(this).html() +
                    '<span class="remove_item"><i class="ti ti-x"></i></span></div>';
                if ($('.selected_addon_ids').html().indexOf($(this).data('id')) === -1) {
                    $('.selected_addons').prepend(selected_addon);
                    $('.selected_addon_ids').append($(this).data('id') + ',');
                    $('.selected_addons .ti ti-checkmark').addClass('d-none');
                }
            });

            $(document).on('click', '.remove_item', function() {
                var item = $(this).parent().remove();
                var remove_addon_id = $(this).parent().data('id');
                var selected_addon_ids = $('.selected_addon_ids').html().replace(remove_addon_id + ',', '');
                $('.selected_addon_ids').html(selected_addon_ids);

            });
        @endif

        @if (config('database.connections.saleprosaas_landlord'))
            numberOfProduct = <?php echo json_encode($numberOfProduct); ?>;
            $.ajax({
                type: 'GET',
                async: false,
                url: '{{ route('package.fetchData', $general_setting->package_id) }}',
                success: function(data) {
                    if (data['number_of_product'] > 0 && data['number_of_product'] <= numberOfProduct) {
                        localStorage.setItem("message",
                            "You don't have permission to create another product as you already exceed the limit! Subscribe to another package if you wants more!"
                            );
                        location.href = "{{ route('products.index') }}";
                    }
                }
            });
        @endif

        $("#digital").hide();
        // $("#combo").hide();
        $("#variant-section").hide();
        $("#initial-stock-section").hide();
        $("#diffPrice-section").hide();
        $("#promotion_price").hide();
        $("#start_date").hide();
        $("#last_date").hide();
        var variantPlaceholder = <?php echo json_encode(__('db.Enter variant value seperated by comma')); ?>;
        var variantIds = [];
        var combinations = [];
        var oldCombinations = [];
        var oldAdditionalCost = [];
        var oldAdditionalPrice = [];
        var step;
        var numberOfWarehouse = <?php echo json_encode(count($lims_warehouse_list)); ?>;

        $('[data-toggle="tooltip"]').tooltip();

        let gencodeUrl = "{{ route('product.gencode') }}";

        $('#genbutton').on("click", function() {
            $.get(gencodeUrl, function(data) {
                $("input[name='code']").val(data);
            });
        });

        $('.add-more-variant').on("click", function() {
            var htmlText =
                '<div class="row"><div class="col-md-4 form-group mt-2"><label>Option *</label><input type="text" name="variant_option[]" class="form-control variant-field" placeholder="Size, Color etc..."></div><div class="col-md-7 form-group mt-2"><label>Value *</label><input type="text" name="variant_value[]" class="type-variant form-control variant-field"></div><div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;"><button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="ti ti-x"></i></button></div></div>';
            $("#variant-input-section").append(htmlText);
            $('.type-variant').tagsInput();
        });

        $(document).on("click", '.delVarOption', function() {
            $(this).parent().parent().remove();
            $('.type-variant').tagsInput();
        });

        //start variant related js
        $(function() {
            $('.type-variant').tagsInput();
        });

        (function($) {
            var delimiter = [];
            var inputSettings = [];
            var callbacks = [];

            $.fn.addTag = function(value, options) {
                options = jQuery.extend({
                    focus: false,
                    callback: true
                }, options);
                this.each(function() {
                    var id = $(this).attr('id');
                    var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
                    if (tagslist[0] === '') tagslist = [];

                    value = jQuery.trim(value);

                    if ((inputSettings[id].unique && $(this).tagExist(value)) || !_validateTag(value,
                            inputSettings[id], tagslist, delimiter[id])) {
                        $('#' + id + '_tag').addClass('error');
                        return false;
                    }

                    $('<span>', {
                        class: 'tag'
                    }).append(
                        $('<span>', {
                            class: 'tag-text'
                        }).text(value),
                        $('<button>', {
                            class: 'tag-remove'
                        }).click(function() {
                            return $('#' + id).removeTag(encodeURI(value));
                        })
                    ).insertBefore('#' + id + '_addTag');
                    tagslist.push(value);

                    $('#' + id + '_tag').val('');
                    if (options.focus) {
                        $('#' + id + '_tag').focus();
                    } else {
                        $('#' + id + '_tag').blur();
                    }

                    $.fn.tagsInput.updateTagsField(this, tagslist);

                    if (options.callback && callbacks[id] && callbacks[id]['onAddTag']) {
                        var f = callbacks[id]['onAddTag'];
                        f.call(this, this, value);
                    }

                    if (callbacks[id] && callbacks[id]['onChange']) {
                        var i = tagslist.length;
                        var f = callbacks[id]['onChange'];
                        f.call(this, this, value);
                    }

                    $(".type-variant").each(function(index) {
                        variantIds.splice(index, 1, $(this).attr('id'));
                    });

                    //start custom code
                    first_variant_values = $('#' + variantIds[0]).val().split(_getDelimiter(delimiter[
                        variantIds[0]]));
                    combinations = first_variant_values;
                    step = 1;
                    while (step < variantIds.length) {
                        var newCombinations = [];
                        for (var i = 0; i < combinations.length; i++) {
                            new_variant_values = $('#' + variantIds[step]).val().split(_getDelimiter(
                                delimiter[variantIds[step]]));
                            for (var j = 0; j < new_variant_values.length; j++) {
                                newCombinations.push(combinations[i] + '/' + new_variant_values[j]);
                            }
                        }
                        combinations = newCombinations;
                        step++;
                    }
                    var rownumber = $('table.variant-list tbody tr:last').index();
                    if (rownumber > -1) {
                        oldCombinations = [];
                        oldAdditionalCost = [];
                        oldAdditionalPrice = [];
                        $(".variant-name").each(function(i) {
                            oldCombinations.push($(this).text());
                            oldAdditionalCost.push($('table.variant-list tbody tr:nth-child(' + (i +
                                1) + ')').find('.additional-cost').val());
                            oldAdditionalPrice.push($('table.variant-list tbody tr:nth-child(' + (
                                i + 1) + ')').find('.additional-price').val());
                        });
                    }
                    $("table.variant-list tbody").remove();
                    var newBody = $("<tbody>");
                    for (i = 0; i < combinations.length; i++) {
                        var variant_name = combinations[i];
                        var item_code = variant_name + '-' + $("#code").val();
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td class="variant-name">' + variant_name +
                            '<input type="hidden" name="variant_name[]" value="' + variant_name +
                            '" /></td>';
                        cols +=
                            '<td><input type="text" class="form-control item-code" name="item_code[]" value="' +
                            item_code + '" /></td>';
                        //checking if this variant already exist in the variant table
                        oldIndex = oldCombinations.indexOf(combinations[i]);
                        if (oldIndex >= 0) {
                            cols +=
                                '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="' +
                                oldAdditionalCost[oldIndex] + '" step="any" /></td>';
                            cols +=
                                '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="' +
                                oldAdditionalPrice[oldIndex] + '" step="any" /></td>';
                        } else {
                            cols +=
                                '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="" step="any" /></td>';
                            cols +=
                                '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="" step="any" /></td>';
                        }
                        newRow.append(cols);
                        newBody.append(newRow);
                    }
                    $("table.variant-list").append(newBody);
                    //end custom code
                });
                return false;
            };

            $.fn.removeTag = function(value) {
                value = decodeURI(value);

                this.each(function() {
                    var id = $(this).attr('id');

                    var old = $(this).val().split(_getDelimiter(delimiter[id]));

                    $('#' + id + '_tagsinput .tag').remove();

                    var str = '';
                    for (i = 0; i < old.length; ++i) {
                        if (old[i] != value) {
                            str = str + _getDelimiter(delimiter[id]) + old[i];
                        }
                    }

                    $.fn.tagsInput.importTags(this, str);

                    if (callbacks[id] && callbacks[id]['onRemoveTag']) {
                        var f = callbacks[id]['onRemoveTag'];
                        f.call(this, this, value);
                    }
                });

                return false;
            };

            $.fn.tagExist = function(val) {
                var id = $(this).attr('id');
                var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
                return (jQuery.inArray(val, tagslist) >= 0);
            };

            $.fn.importTags = function(str) {
                var id = $(this).attr('id');
                $('#' + id + '_tagsinput .tag').remove();
                $.fn.tagsInput.importTags(this, str);
            };

            $.fn.tagsInput = function(options) {
                var settings = jQuery.extend({
                    interactive: true,
                    placeholder: variantPlaceholder,
                    minChars: 0,
                    maxChars: null,
                    limit: null,
                    validationPattern: null,
                    width: 'auto',
                    height: 'auto',
                    autocomplete: null,
                    hide: true,
                    delimiter: ',',
                    unique: true,
                    removeWithBackspace: true
                }, options);

                var uniqueIdCounter = 0;

                this.each(function() {
                    if (typeof $(this).data('tagsinput-init') !== 'undefined') return;

                    $(this).data('tagsinput-init', true);

                    if (settings.hide) $(this).hide();

                    var id = $(this).attr('id');
                    if (!id || _getDelimiter(delimiter[$(this).attr('id')])) {
                        id = $(this).attr('id', 'tags' + new Date().getTime() + (++uniqueIdCounter)).attr(
                            'id');
                    }

                    var data = jQuery.extend({
                        pid: id,
                        real_input: '#' + id,
                        holder: '#' + id + '_tagsinput',
                        input_wrapper: '#' + id + '_addTag',
                        fake_input: '#' + id + '_tag'
                    }, settings);

                    delimiter[id] = data.delimiter;
                    inputSettings[id] = {
                        minChars: settings.minChars,
                        maxChars: settings.maxChars,
                        limit: settings.limit,
                        validationPattern: settings.validationPattern,
                        unique: settings.unique
                    };

                    if (settings.onAddTag || settings.onRemoveTag || settings.onChange) {
                        callbacks[id] = [];
                        callbacks[id]['onAddTag'] = settings.onAddTag;
                        callbacks[id]['onRemoveTag'] = settings.onRemoveTag;
                        callbacks[id]['onChange'] = settings.onChange;
                    }

                    var markup = $('<div>', {
                        id: id + '_tagsinput',
                        class: 'tagsinput'
                    }).append(
                        $('<div>', {
                            id: id + '_addTag'
                        }).append(
                            settings.interactive ? $('<input>', {
                                id: id + '_tag',
                                class: 'tag-input',
                                value: '',
                                placeholder: settings.placeholder
                            }) : null
                        )
                    );

                    $(markup).insertAfter(this);

                    $(data.holder).css('width', settings.width);
                    $(data.holder).css('min-height', settings.height);
                    $(data.holder).css('height', settings.height);

                    if ($(data.real_input).val() !== '') {
                        $.fn.tagsInput.importTags($(data.real_input), $(data.real_input).val());
                    }

                    // Stop here if interactive option is not chosen
                    if (!settings.interactive) return;

                    $(data.fake_input).val('');
                    $(data.fake_input).data('pasted', false);

                    $(data.fake_input).on('focus', data, function(event) {
                        $(data.holder).addClass('focus');

                        if ($(this).val() === '') {
                            $(this).removeClass('error');
                        }
                    });

                    $(data.fake_input).on('blur', data, function(event) {
                        $(data.holder).removeClass('focus');
                    });

                    if (settings.autocomplete !== null && jQuery.ui.autocomplete !== undefined) {
                        $(data.fake_input).autocomplete(settings.autocomplete);
                        $(data.fake_input).on('autocompleteselect', data, function(event, ui) {
                            $(event.data.real_input).addTag(ui.item.value, {
                                focus: true,
                                unique: settings.unique
                            });

                            return false;
                        });

                        $(data.fake_input).on('keypress', data, function(event) {
                            if (_checkDelimiter(event)) {
                                $(this).autocomplete("close");
                            }
                        });
                    } else {
                        $(data.fake_input).on('blur', data, function(event) {
                            $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                                focus: true,
                                unique: settings.unique
                            });

                            return false;
                        });
                    }

                    // If a user types a delimiter create a new tag
                    $(data.fake_input).on('keypress', data, function(event) {
                        if (_checkDelimiter(event)) {
                            event.preventDefault();

                            $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                                focus: true,
                                unique: settings.unique
                            });

                            return false;
                        }
                    });

                    $(data.fake_input).on('paste', function() {
                        $(this).data('pasted', true);
                    });

                    // If a user pastes the text check if it shouldn't be splitted into tags
                    $(data.fake_input).on('input', data, function(event) {
                        if (!$(this).data('pasted')) return;

                        $(this).data('pasted', false);

                        var value = $(event.data.fake_input).val();

                        value = value.replace(/\n/g, '');
                        value = value.replace(/\s/g, '');

                        var tags = _splitIntoTags(event.data.delimiter, value);

                        if (tags.length > 1) {
                            for (var i = 0; i < tags.length; ++i) {
                                $(event.data.real_input).addTag(tags[i], {
                                    focus: true,
                                    unique: settings.unique
                                });
                            }

                            return false;
                        }
                    });

                    // Deletes last tag on backspace
                    data.removeWithBackspace && $(data.fake_input).on('keydown', function(event) {
                        if (event.keyCode == 8 && $(this).val() === '') {
                            event.preventDefault();
                            var lastTag = $(this).closest('.tagsinput').find('.tag:last > span')
                                .text();
                            var id = $(this).attr('id').replace(/_tag$/, '');
                            $('#' + id).removeTag(encodeURI(lastTag));
                            $(this).trigger('focus');
                        }
                    });

                    // Removes the error class when user changes the value of the fake input
                    $(data.fake_input).keydown(function(event) {
                        // enter, alt, shift, esc, ctrl and arrows keys are ignored
                        if (jQuery.inArray(event.keyCode, [13, 37, 38, 39, 40, 27, 16, 17, 18,
                            225]) === -1) {
                            $(this).removeClass('error');
                        }
                    });
                });

                return this;
            };

            $.fn.tagsInput.updateTagsField = function(obj, tagslist) {
                var id = $(obj).attr('id');
                $(obj).val(tagslist.join(_getDelimiter(delimiter[id])));
            };

            $.fn.tagsInput.importTags = function(obj, val) {
                $(obj).val('');

                var id = $(obj).attr('id');
                var tags = _splitIntoTags(delimiter[id], val);

                for (i = 0; i < tags.length; ++i) {
                    $(obj).addTag(tags[i], {
                        focus: false,
                        callback: false
                    });
                }

                if (callbacks[id] && callbacks[id]['onChange']) {
                    var f = callbacks[id]['onChange'];
                    f.call(obj, obj, tags);
                }
            };

            var _getDelimiter = function(delimiter) {
                if (typeof delimiter === 'undefined') {
                    return delimiter;
                } else if (typeof delimiter === 'string') {
                    return delimiter;
                } else {
                    return delimiter[0];
                }
            };

            var _validateTag = function(value, inputSettings, tagslist, delimiter) {
                var result = true;

                if (value === '') result = false;
                if (value.length < inputSettings.minChars) result = false;
                if (inputSettings.maxChars !== null && value.length > inputSettings.maxChars) result = false;
                if (inputSettings.limit !== null && tagslist.length >= inputSettings.limit) result = false;
                if (inputSettings.validationPattern !== null && !inputSettings.validationPattern.test(value))
                    result = false;

                if (typeof delimiter === 'string') {
                    if (value.indexOf(delimiter) > -1) result = false;
                } else {
                    $.each(delimiter, function(index, _delimiter) {
                        if (value.indexOf(_delimiter) > -1) result = false;
                        return false;
                    });
                }

                return result;
            };

            var _checkDelimiter = function(event) {
                var found = false;

                if (event.which === 13) {
                    return true;
                }

                if (typeof event.data.delimiter === 'string') {
                    if (event.which === event.data.delimiter.charCodeAt(0)) {
                        found = true;
                    }
                } else {
                    $.each(event.data.delimiter, function(index, delimiter) {
                        if (event.which === delimiter.charCodeAt(0)) {
                            found = true;
                        }
                    });
                }

                return found;
            };

            var _splitIntoTags = function(delimiter, value) {
                if (value === '') return [];

                if (typeof delimiter === 'string') {
                    return value.split(delimiter);
                } else {
                    var tmpDelimiter = '∞';
                    var text = value;

                    $.each(delimiter, function(index, _delimiter) {
                        text = text.split(_delimiter).join(tmpDelimiter);
                    });

                    return text.split(tmpDelimiter);
                }

                return [];
            };
        })(jQuery);
        //end of variant related js

        tinymce.init({
            selector: 'textarea:not(.no-tiny)',
            height: 130,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor textcolor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table contextmenu paste code wordcount'
            ],
            toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
            branding: false
        });



        $('select[name="unit_id"]').on('change', function() {

            unitID = $(this).val();
            if (unitID) {
                populate_category(unitID);
            } else {
                $('select[name="sale_unit_id"]').empty();
                $('select[name="purchase_unit_id"]').empty();
            }
        });
        <?php $productArray = []; ?>
        var lims_product_code = [
            @foreach ($lims_product_list_without_variant as $product)
                <?php
                $productArray[] = htmlspecialchars($product->code) . ' (' . preg_replace('/[\n\r]/', '<br>', htmlspecialchars($product->name)) . ')';
                ?>
            @endforeach
            @foreach ($lims_product_list_with_variant as $product)
                <?php
                $productArray[] = htmlspecialchars($product->item_code) . ' (' . preg_replace('/[\n\r]/', '<br>', htmlspecialchars($product->name)) . ')';
                ?>
            @endforeach
            <?php
            echo '"' . implode('","', $productArray) . '"';
            ?>
        ];

        var lims_productcodeSearch = $('#lims_productcodeSearch');

        lims_productcodeSearch.autocomplete({
            source: function(request, response) {
                var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
                response($.grep(lims_product_code, function(item) {
                    return matcher.test(item);
                }));
            },
            select: function(event, ui) {
                var data = ui.item.value;
                $.ajax({
                    type: 'GET',
                    url: '{{ route('product.search') }}',
                    data: {
                        data: data
                    },
                    success: function(responseData) {
                        data = responseData[0];
                        var flag = 1;
                        // $(".product-id").each(function() {
                        //     if ($(this).val() == data[8]) {
                        //         alert('Duplicate input is not allowed!')
                        //         flag = 0;
                        //     }
                        // });
                        $("input[name='product_code_name']").val('');
                        if (flag) {
                            var newRow = $("<tr>");
                            var cols = '';
                            cols += '<td>' + data[0] + ' [' + data[1] + ']</td>';
                            cols += `<td>
                                    <div class="input-group">
                                        <input type="number" name="wastage_percent[]" class="form-control wastage_percent" value="0"/>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </td>`;
                            cols += `<td>
                                    <div class="input-group" style="max-width: unset">
                                        <input type="number"
                                            class="form-control qty"
                                            min="0"
                                            name="product_qty[]"
                                            value="1"
                                            step="any"
                                            placeholder="Qty"
                                            aria-label="Quantity">
                                        <div class="input-group-append">
                                            ` + data[13] + `
                                        </div>
                                    </div>
                                </td>`;
                            cols +=
                                '<td><input type="number" class="form-control unit_cost" name="product_unit_cost[]" value="' +
                                data[10] + '"/></td>';
                            cols +=
                                '<td><input type="number" class="form-control unit_price" name="unit_price[]" value="' +
                                data[2] + '" step="any"/></td>';
                            cols +=
                                '<td><input type="number" class="form-control subtotal" name="subtotal[]" value="' +
                                data[2] + '" step="any"/></td>';
                            cols +=
                                '<td><button type="button" class="ibtnDel btn btn-sm btn-danger">X</button></td>';
                            cols +=
                                '<input type="hidden" class="product-id" name="product_list[]" value="' +
                                data[8] + '"/>';
                            cols += '<input type="hidden" class="" name="variant_list[]" value="' +
                                data[9] + '"/>';

                            newRow.append(cols);
                            $(".combo_product_list_table").append(newRow);
                            calculate_price();
                        }
                    }
                });
            }
        });

        //Change quantity or unit price
        $("#myTable").on('input', '.qty , .unit_cost, .unit_price, .wastage_percent', function() {
            calculate_price();
        });

        //Delete product
        $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
            $(this).closest("tr").remove();
            calculate_price();
        });

        function hide() {
            $("#cost").hide(300);
            $("#unit").hide(300);
            // $("#alert-qty").hide(300);
        }

        function calculate_price() {
            let price = 0;
            let cost = 0;

            $(".qty").each(function() {
                let $row = $(this).closest('tr');
                let rowIndex = $row.index();

                let quantity = parseFloat($(this).val()) || 0;
                let unit_price = parseFloat($row.find('.unit_price').val()) || 0;
                let unit_cost = parseFloat($row.find('.unit_cost').val()) || 0;

                // Handle combo unit conversion
                let $selectedOption = $row.find('.combo_unit_id option:selected');
                let operator = $selectedOption.data('operator');
                let operationValue = parseFloat($selectedOption.data('operation_value')) || 1;

                let convertedQty = quantity;
                if (operator === '*') {
                    convertedQty = quantity * operationValue;
                } else if (operator === '/') {
                    convertedQty = quantity / operationValue;
                }

                // Calculate subtotal and accumulate cost/price
                let subtotal = convertedQty * unit_price;
                cost += convertedQty * unit_cost;
                price += subtotal;

                // Update subtotal in DOM
                $row.find('.subtotal').val(subtotal.toFixed(2));
            });

             // westage percent calculation
            let wastage_percent = 0;
            $('.wastage_percent').each(function(){
                wastage_percent += parseFloat($(this).val()) || 0;
            });

            $('#wastage_percent').html(wastage_percent + '%')

            // Get production and shipping cost safely
            let production_cost = parseFloat($('.production_cost').val()) || 0;
            let shipping_cost = parseFloat($('.shipping_cost').val()) || 0;

            // Calculate total unit cost from all unit_cost inputs
            let total_cost = 0;
            $('input[name="product_unit_cost[]"]').each(function() {
                total_cost += parseFloat($(this).val()) || 0;
            });

            // Final price including additional costs
            grand_total = price + shipping_cost + production_cost;

            // Update results in DOM
            $('#shipping_cost').html(shipping_cost.toFixed(2));
            $('#production_cost').html(production_cost.toFixed(2));
            $('#total').html(price.toFixed(2));
            $('#grand_total').html(grand_total.toFixed(2));
        }


        var starting_date = $('#starting_date');
        starting_date.datepicker({
            format: "dd-mm-yyyy",
            startDate: "<?php echo date('d-m-Y'); ?>",
            autoclose: true,
            todayHighlight: true
        });

        var ending_date = $('#ending_date');
        ending_date.datepicker({
            format: "dd-mm-yyyy",
            startDate: "<?php echo date('d-m-Y'); ?>",
            autoclose: true,
            todayHighlight: true
        });

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


        jQuery.validator.setDefaults({

            highlight: function(element) {
                $(element).closest('div.form-group').removeClass('has-success').addClass('has-error');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).closest('div.form-group').removeClass('has-error').addClass('has-success');
                $(element).closest('div.form-group').find('.validation-msg').html('');
            }
        });





        $('#selectRecipe').on('change', function() {
            var productId = $(this).val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Ajax Request
            $.ajax({
                url: '{{ route('manufacturing.productions.get-ingredients')  }}',
                type: 'POST',
                data: {
                    product_id: productId
                },
                success: function(response) {
                    $('#ingredients-table').html(response.ingredients)
                    calculate_price();
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        });


        $('.production_cost').on('input', function() {
            calculate_price();
        })

        $('.shipping_cost').on('input', function() {
            calculate_price();
        })

        $(".dropzone").sortable({
            items: '.dz-preview',
            cursor: 'grab',
            opacity: 0.5,
            containment: '.dropzone',
            distance: 20,
            tolerance: 'pointer',
            stop: function() {
                var queue = myDropzone.getAcceptedFiles();
                newQueue = [];
                $('#imageUpload .dz-preview .dz-filename [data-dz-name]').each(function(count, el) {
                    var name = el.innerHTML;
                    queue.forEach(function(file) {
                        if (file.name === name) {
                            newQueue.push(file);
                        }
                    });
                });
                myDropzone.files = newQueue;
            }
        });
    </script>
@endpush
