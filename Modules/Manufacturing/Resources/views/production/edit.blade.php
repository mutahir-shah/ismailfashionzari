@extends('backend.layout.main')

@if(in_array('ecommerce',explode(',',$general_setting->modules)) || in_array('restaurant',explode(',',$general_setting->modules)))
@push('css')
<style>
.search_result, .search_result_addon {border:1px solid #e4e6fc;border-radius:5px;overflow-y: scroll;}
.search_result > div, .search_result_addon > div, .selected_items > div, .selected_addons > div {border-top:1px solid #e4e6fc;cursor:pointer;display:flex;align-items:center;padding: 10px;position: relative;}
.search_result > div > img, .search_result_addon > div > img, .selected_items > div > img, .selected_addons > div > img {margin-right: 10px;max-width: 40px;}
.search_result > div h4, .search_result_addon > div h4, .selected_items > div h4, .selected_addons > div h4 {font-size: 0.9rem;}
.search_result > div i,  .search_result_addon > div i, {color:#54b948;position:absolute;right:5px;top:30%}
.search_result div:first-child, .search_result_addon div:first-child, {border-top:none}
.selected_items .remove_item, .selected_addons .remove_item {position: absolute;right: 20px;top:20px};
.delVarOption{display: flex;flex-direction: column;align-items: center;}
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
                        <h4>{{__('db.Edit Production')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form id="product-form" method="post" action="{{ route('manufacturing.productions.update', $lims_production_data->id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.date')}}</label>
                                           <input type="text" name="created_at" class="form-control date" value="{{ date('d-m-Y', strtotime($lims_production_data->created_at)) }}" placeholder="Choose date" />

                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Warehouse')}} *</label>
                                            <select required id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}" @if($lims_production_data->warehouse_id == $warehouse->id) selected @endif>{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div class="form-check mt-4">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    id="finalized"
                                                    name="status"
                                                    value="1"
                                                    @if($lims_production_data->status == 1) checked @endif
                                                >
                                                <label class="form-check-label" for="finalized">
                                                    {{ __('db.Finalized') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Attach Document')}}</label> <i class="ti ti-info-circle" data-toggle="tooltip" title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                            <input type="file" name="document" class="form-control" >
                                            @if($errors->has('extension'))
                                                <span>
                                                   <strong>{{ $errors->first('extension') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Select Recipe')}} *</label>
                                            <select required id="selectRecipe" name="product_id" class="selectpicker form-control" data-live-search="true" title="Select Recipe...">
                                                @foreach($lims_product_list as $product)
                                                    <option value="{{$product->id}}" @if($lims_production_data->product_id == $product->id) selected @endif>{{$product->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                     <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Total Qty')}}</label>
                                            <input type="number" name="total_qty" min="0" class="form-control total_qty" value="{{$lims_production_data->total_qty}}" step="any" />
                                        </div>
                                    </div>


                                </div>
                            <div class="row">
                                <div id="digital" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Attach File')}} *</strong> </label>
                                        <div class="input-group">
                                            <input type="file" id="file" name="file" class="form-control">
                                        </div>
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div  class="col-md-12 mb-1">
                                    <label>{{__('db.Ingredient List')}}</label>
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-hover order-list">
                                            <thead>
                                                <tr>
                                                    <th>{{__('db.product')}}</th>
                                                    <th>{{__('db.Wastage Percent')}}</th>
                                                    <th>{{__('db.Quantity')}}</th>
                                                    <th>{{__('db.Final Quantity')}}</th>
                                                    <th>{{__('db.Unit Price')}}</th>
                                                    <th>{{__('db.Sub total')}}</th>
                                                    <th><i class="ti ti-trash"></i></th>
                                                </tr>

                                            </thead>
                                            <tbody class="combo_product_list_table" id="ingredients-table">
                                                <?php $lims_production_data->combo_unit_id = $lims_production_data->production_units_ids; ?>
                                                @include('manufacturing::production.ingredients', ['lims_product_data' => $lims_production_data, 'warehouse_id' => $lims_production_data->warehouse_id])
                                            </tbody>
                                        </table>
                                    </div>
                                </div>



                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label> {{__('db.Production Cost')}} </label>
                                            <input type="number" name="production_cost" class="form-control production_cost" value="{{$lims_production_data->production_cost}}" step="any" />
                                        </div>
                                    </div>



                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Shipping Cost')}}</label>
                                            <input type="number" name="shipping_cost" value="{{$lims_production_data->shipping_cost}}" class="form-control shipping_cost" step="any" />
                                        </div>
                                    </div>
                                    <input type="hidden" name="total_cost" class="total_cost">
                                    <input type="hidden" name="grand_total" class="grand_total">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.note')}}</label>
                                        <textarea rows="5" class="form-control" name="note">{{$lims_production_data->note}}</textarea>
                                    </div>
                                    <div class="form-group mt-3">
                                        <button type="submit" id="submit-btn" class="btn btn-primary submit-btn">{{__('db.update')}}</button>
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
            <td><strong>{{__('db.Production Cost')}}</strong>
                <span class="pull-right" id="production_cost">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>

            <td><strong>{{__('db.Shipping Cost')}}</strong>
                <span class="pull-right" id="shipping_cost">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>

            <td><strong>{{__('db.Total')}}</strong>
                <span class="pull-right" id="total">{{number_format(0, $general_setting->decimal, '.', '')}}</span>
            </td>

            <td><strong>{{__('db.grand total')}}</strong>
                <span class="pull-right" id="grand_total">{{number_format(0, $general_setting->decimal, '.', '')}}</span>

            </td>
        </table>
    </div>
    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modal-header" class="modal-title"></h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row modal-element">
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Quantity')}}</label>
                                <input type="number" name="edit_qty" class="form-control" step="any">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Unit Discount')}}</label>
                                <input type="number" name="edit_discount" class="form-control" step="any">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Unit Cost')}}</label>
                                <input type="number" name="edit_unit_cost" class="form-control" step="any">
                            </div>
                            <?php
                                $tax_name_all[] = 'No Tax';
                                $tax_rate_all[] = 0;
                                foreach($lims_tax_list as $tax) {
                                    $tax_name_all[] = $tax->name;
                                    $tax_rate_all[] = $tax->rate;
                                }
                            ?>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Tax Rate')}}</label>
                                <select name="edit_tax_rate" class="form-control selectpicker">
                                    @foreach($tax_name_all as $key => $name)
                                    <option value="{{$key}}">{{$name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{__('db.Product Unit')}}</label>
                                <select name="edit_unit" class="form-control selectpicker">
                                </select>
                            </div>
                        </div>
                        <button type="button" name="update_btn" class="btn btn-primary">{{__('db.update')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('scripts')
<script type="text/javascript">

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });




    $("#is_addon").on('click', function(){
        if($("#is_addon").prop('checked') == false){
            $('.extra-section,.related-section').css('display','block');
        }else{
            $('.extra-section,.related-section').css('display','none');
        }
    })

    @if(in_array('ecommerce',explode(',',$general_setting->modules)) || in_array('restaurant',explode(',',$general_setting->modules)))
    $('#search_products').on('input', function() {
        var item = $(this).val();
        $('.search_result').html('<div class="d-block text-center"><div class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>');

        if(item.length >= 3){
            $.ajax({
                type: "get",
                url: "{{url('search')}}/" + item,
                success: function(data) {
                    $('.search_result').html('').css('height','200px');
                    $.each(data,function(key, value){
                        var image = value.image.split(',');
                        $('.search_result').append('<div data-id="'+value.id+'"><img src="{{asset("images/product/small/")}}/'+image[0]+'"><h4>'+value.name+'</h4><i class="ti ti-checkmark d-none"></i></div>')
                    })
                }
            })
        } else if (item.length < 3) {
            $('.search_result').html('');
        }
    });

    $(document).on('click','.search_result div',function(){
        $(this).find('i').removeClass('d-none');
        var selected_item = '<div data-id="'+$(this).data('id')+'">'+$(this).html()+'<span class="remove_item"><i class="ti ti-x"></i></span></div>';
        if ($('.selected_ids').html().indexOf($(this).data('id')) === -1){
            $('.selected_items').prepend(selected_item);
            $('.selected_ids').append($(this).data('id')+',');
            $('.selected_items .ti ti-checkmark').addClass('d-none');
        }
    });

    $(document).on('click','.remove_item',function(){
        var item = $(this).parent().remove();
        var remove_id = $(this).parent().data('id');
        var selected_ids = $('.selected_ids').html().replace(remove_id+',','');
        $('.selected_ids').html(selected_ids);

    });
    @endif

    @if(in_array('restaurant',explode(',',$general_setting->modules)))
    $('#search_addons').on('input', function() {
        var item = $(this).val();
        $('.search_result_addon').html('<div class="d-block text-center"><div class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>');

        if(item.length >= 3){
            $.ajax({
                type: "get",
                url: "{{url('search')}}/" + item,
                success: function(data) {
                    $('.search_result_addon').html('').css('height','200px');
                    $.each(data,function(key, value){
                        var image = value.image.split(',');
                        $('.search_result_addon').append('<div data-id="'+value.id+'"><img src="{{asset("images/product/small/")}}/'+image[0]+'"><h4>'+value.name+'</h4><i class="ti ti-checkmark d-none"></i></div>')
                    })
                }
            })
        } else if (item.length < 3) {
            $('.search_result_addon').html('');
        }
    });

    $(document).on('click','.search_result_addon div',function(){
        $(this).find('i').removeClass('d-none');
        var selected_addon = '<div data-id="'+$(this).data('id')+'">'+$(this).html()+'<span class="remove_item"><i class="ti ti-x"></i></span></div>';
        if ($('.selected_addon_ids').html().indexOf($(this).data('id')) === -1){
            $('.selected_addons').prepend(selected_addon);
            $('.selected_addon_ids').append($(this).data('id')+',');
            $('.selected_addons .ti ti-checkmark').addClass('d-none');
        }
    });

    $(document).on('click','.remove_item',function(){
        var item = $(this).parent().remove();
        var remove_addon_id = $(this).parent().data('id');
        var selected_addon_ids = $('.selected_addon_ids').html().replace(remove_addon_id +',','');
        $('.selected_addon_ids').html(selected_addon_ids);

    });
    @endif

    $("ul#manufacturing").siblings('a').attr('aria-expanded','true');
    $("ul#manufacturing").addClass("show");
    $("ul#manufacturing #production-create-menu").addClass("active");

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
    var numberOfWarehouse = <?php echo json_encode(count($lims_warehouse_list)) ?>;

    $('[data-toggle="tooltip"]').tooltip();

    let gencodeUrl = "{{ route('product.gencode') }}";

        $('#genbutton').on("click", function() {
            $.get(gencodeUrl, function(data) {
                $("input[name='code']").val(data);
            });
        });

    $('.add-more-variant').on("click", function() {
        var htmlText = '<div class="row"><div class="col-md-4 form-group mt-2"><label>Option *</label><input type="text" name="variant_option[]" class="form-control variant-field" placeholder="Size, Color etc..."></div><div class="col-md-7 form-group mt-2"><label>Value *</label><input type="text" name="variant_value[]" class="type-variant form-control variant-field"></div><div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;"><button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="ti ti-x"></i></button></div></div>';
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

                if ((inputSettings[id].unique && $(this).tagExist(value)) || !_validateTag(value, inputSettings[id], tagslist, delimiter[id])) {
                    $('#' + id + '_tag').addClass('error');
                    return false;
                }

                $('<span>', {class: 'tag'}).append(
                    $('<span>', {class: 'tag-text'}).text(value),
                    $('<button>', {class: 'tag-remove'}).click(function() {
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
                first_variant_values = $('#'+variantIds[0]).val().split(_getDelimiter(delimiter[variantIds[0] ]));
                combinations = first_variant_values;
                step = 1;
                while(step < variantIds.length) {
                    var newCombinations = [];
                    for (var i = 0; i < combinations.length; i++) {
                        new_variant_values = $('#'+variantIds[step]).val().split(_getDelimiter(delimiter[variantIds[step] ]));
                        for (var j = 0; j < new_variant_values.length; j++) {
                            newCombinations.push(combinations[i]+'/'+new_variant_values[j]);
                        }
                    }
                    combinations = newCombinations;
                    step++;
                }
                var rownumber = $('table.variant-list tbody tr:last').index();
                if(rownumber > -1) {
                    oldCombinations = [];
                    oldAdditionalCost = [];
                    oldAdditionalPrice = [];
                    $(".variant-name").each(function(i) {
                        oldCombinations.push($(this).text());
                        oldAdditionalCost.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-cost').val());
                        oldAdditionalPrice.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-price').val());
                    });
                }
                $("table.variant-list tbody").remove();
                var newBody = $("<tbody>");
                for(i = 0; i < combinations.length; i++) {
                    var variant_name = combinations[i];
                    var item_code = variant_name+'-'+$("#code").val();
                    var newRow = $("<tr>");
                    var cols = '';
                    cols += '<td class="variant-name">'+variant_name+'<input type="hidden" name="variant_name[]" value="' + variant_name + '" /></td>';
                    cols += '<td><input type="text" class="form-control item-code" name="item_code[]" value="'+item_code+'" /></td>';
                    //checking if this variant already exist in the variant table
                    oldIndex = oldCombinations.indexOf(combinations[i]);
                    if(oldIndex >= 0) {
                        cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="'+oldAdditionalCost[oldIndex]+'" step="any" /></td>';
                        cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="'+oldAdditionalPrice[oldIndex]+'" step="any" /></td>';
                    }
                    else {
                        cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="" step="any" /></td>';
                        cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="" step="any" /></td>';
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
                    id = $(this).attr('id', 'tags' + new Date().getTime() + (++uniqueIdCounter)).attr('id');
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

                var markup = $('<div>', {id: id + '_tagsinput', class: 'tagsinput'}).append(
                    $('<div>', {id: id + '_addTag'}).append(
                        settings.interactive ? $('<input>', {id: id + '_tag', class: 'tag-input', value: '', placeholder: settings.placeholder}) : null
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

                $(data.fake_input).on('paste', function () {
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
                         var lastTag = $(this).closest('.tagsinput').find('.tag:last > span').text();
                         var id = $(this).attr('id').replace(/_tag$/, '');
                         $('#' + id).removeTag(encodeURI(lastTag));
                         $(this).trigger('focus');
                    }
                });

                // Removes the error class when user changes the value of the fake input
                $(data.fake_input).keydown(function(event) {
                    // enter, alt, shift, esc, ctrl and arrows keys are ignored
                    if (jQuery.inArray(event.keyCode, [13, 37, 38, 39, 40, 27, 16, 17, 18, 225]) === -1) {
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
            if (inputSettings.validationPattern !== null && !inputSettings.validationPattern.test(value)) result = false;

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
      branding:false
    });



    $('select[name="unit_id"]').on('change', function() {

        unitID = $(this).val();
        if(unitID) {
            populate_category(unitID);
        }else{
            $('select[name="sale_unit_id"]').empty();
            $('select[name="purchase_unit_id"]').empty();
        }
    });
    <?php $productArray = []; ?>
    var lims_product_code = [
        @foreach($lims_product_list_without_variant as $product)
            <?php
                $productArray[] = htmlspecialchars($product->code) . ' (' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)) . ')';
            ?>
        @endforeach
        @foreach($lims_product_list_with_variant as $product)
            <?php
                $productArray[] = htmlspecialchars($product->item_code) . ' (' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)) . ')';
            ?>
        @endforeach
            <?php
                echo  '"'.implode('","', $productArray).'"';
            ?> ];

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
                url: '{{  route('manufacturing.products.search') }}',
                data: {
                    data: data
                },
                success: function(responseData) {
                    data = responseData[0];
                    var flag = 1;
                    $(".product-id").each(function() {
                        if ($(this).val() == data[8]) {
                            alert('Duplicate input is not allowed!')
                            flag = 0;
                        }
                    });
                    $("input[name='product_code_name']").val('');
                    if(flag){
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + data[0] +' [' + data[1] + ']</td>';
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
                                            `+data[13]+`
                                        </div>
                                    </div>
                                </td>`;
                        cols += '<td><input type="number" class="form-control unit_cost" name="product_unit_cost[]" value="' + data[10] + '"/></td>';
                        cols += '<td><input type="number" class="form-control unit_price" name="unit_price[]" value="' + data[2] + '" step="any"/></td>';
                        cols += '<td><input type="number" class="form-control subtotal" name="subtotal[]" value="' + data[2] + '" step="any"/></td>';
                        cols += '<td><button type="button" class="ibtnDel btn btn-sm btn-danger">X</button></td>';
                        cols += '<input type="hidden" class="product-id" name="product_list[]" value="' + data[8] + '"/>';
                        cols += '<input type="hidden" class="" name="variant_id[]" value="' + data[9] + '"/>';

                        newRow.append(cols);
                        $(".combo_product_list_table").append(newRow);
                        calculate_price();
                    }
                }
            });
        }
    });

    //Change quantity or unit price
    $("#myTable").on('input', '.qty , .unit_cost, .unit_price', function() {
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

    $(window).keydown(function(e){
        if (e.which == 13) {
            var $targ = $(e.target);

            if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
                var focusNext = false;
                $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                    if (this === e.target) {
                        focusNext = true;
                    }
                    else if (focusNext){
                        $(this).focus();
                        return false;
                    }
                });

                return false;
            }
        }
    });


    jQuery.validator.setDefaults({
        errorPlacement: function (error, element) {
            if(error.html() == 'Select Category...')
                error.html('This field is required.');
            $(element).closest('div.form-group').find('.validation-msg').html(error.html());
        },
        highlight: function (element) {
            $(element).closest('div.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('div.form-group').removeClass('has-error').addClass('has-success');
            $(element).closest('div.form-group').find('.validation-msg').html('');
        }
    });

    function validate() {
        var product_code = $("input[name='code']").val();
        var barcode_symbology = $('select[name="barcode_symbology"]').val();
        var exp = /^\d+$/;

        if(!(product_code.match(exp)) && (barcode_symbology == 'UPCA' || barcode_symbology == 'UPCE' || barcode_symbology == 'EAN8' || barcode_symbology == 'EAN13') ) {
            alert('Product code must be numeric.');
            return false;
        }
        else if(product_code.match(exp)) {
            if(barcode_symbology == 'UPCA' && product_code.length > 11){
                alert('Product code length must be less than 12');
                return false;
            }
            else if(barcode_symbology == 'EAN8' && product_code.length > 7){
                alert('Product code length must be less than 8');
                return false;
            }
            /*else if(barcode_symbology == 'EAN13' && product_code.length > 12){
                alert('Product code length must be less than 13');
                return false;
            }*/
        }

        if( $("#type").val() == 'combo' ) {
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                alert("Please insert product to table!")
                return false;
            }
        }
        if($("#is-variant").is(":checked")) {
            rowindex = $("table#variant-table tbody tr:last").index();
            if (rowindex < 0) {
                alert('This product has variant. Please insert variant to table');
                return false;
            }
        }
        $("input[name='price']").prop('disabled',false);
        return true;
    }

    $('#warehouse_id').on('change', function() {
         $('#selectRecipe').val('').selectpicker('refresh');
        $('#ingredients-table').html('');
        $('.total_qty').val(1);
    })



    $('#selectRecipe').on('change', function() {
         var productId = $(this).val();
         var warehouseId = $('#warehouse_id').val();
         $('.total_qty').val(1);

        if (!warehouseId) {
            alert('Please select warehouse');
            $(this).val('');
            return; // Stop the function if warehouse not selected
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Ajax Request
        $.ajax({
            url: '{{  route('manufacturing.productions.get-ingredients')  }}',
            type: 'POST',
            data: {
                product_id: productId,
                warehouse_id: warehouseId
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

    $('.production_cost').on('input',function(){
        calculate_price();
    })

        $('.shipping_cost').on('input',function(){
        calculate_price();
    })

    $('.total_qty').on('keyup change', function(){
         var productId = $('#selectRecipe').val();
        if (!productId) {
            alert('Please select Product');
            $(this).val(1);
            return; // Stop the function if warehouse not selected
        }
        $('.qty').each(function(){
            var product_qty = $(this).data('qty');
            var total_qty = $('.total_qty').val();
            $(this).val(product_qty * total_qty);
        })
        calculate_price();
    });

     function calculate_price() {
        let price = 0;
        let cost = 0;
        let submit_excess = 0;

        $(".qty").each(function () {
            let $row = $(this).closest('tr');
            let rowIndex = $row.index();

            let quantity = parseFloat($(this).val()) || 0;
            let unit_price = parseFloat($row.find('.unit_price').val()) || 0;
            let unit_cost = parseFloat($row.find('.unit_cost').val()) || 0;
            let qty = parseFloat($row.find('.qty').val()) || 0;

            // Handle combo unit conversion
            let $selectedOption = $row.find('.production_unit_ids option:selected');
            let operator = $selectedOption.data('operator');
            let unit_name = $selectedOption.data('unit_name');
            let old_qty = parseFloat($row.find('.qty').data('old_qty')) || 0;
            let current_stock = parseFloat($row.find('.qty').data('current_stock')) || 0;
            let max_allowed_base = old_qty + Math.max(0, current_stock);

            let operationValue = parseFloat($selectedOption.data('operation_value')) || 1;

            let convertedQty = quantity;
            var available = quantity;
            
            if (operator === '*') {
                convertedQty = quantity * operationValue;
                available = (max_allowed_base / operationValue).toFixed(2);
            } else if (operator === '/') {
                convertedQty = quantity / operationValue;
                available = (max_allowed_base * operationValue).toFixed(2);
            } else {
                available = max_allowed_base.toFixed(2);
            }

            if (convertedQty > old_qty && (convertedQty - old_qty) > current_stock && qty > 0) {
               let errorMessage = `<span class="text-danger qty-error">Qty exceeds available stock (${available} - ${unit_name})</span>`;
               $row.find('.qty-error').html(errorMessage);
               submit_excess += 1;
            } else {
                $row.find('.qty-error').html('');
            }

            // Calculate subtotal and accumulate cost/price


            let subtotal = convertedQty * unit_price;
            cost += convertedQty * unit_cost;
            price += subtotal;

            var change_qty = $(this).val();
            // $(this).attr('data-qty',change_qty);


            // Update subtotal in DOM
            $row.find('.subtotal').val(subtotal.toFixed(2));
            $row.find('.unit_name').val(change_qty+' '+ unit_name );
        });
        if(submit_excess == 0){
             $('.submit-btn').prop('disabled', false);
        }else{
            $('.submit-btn').prop('disabled', true);
        }

        // Get production and shipping cost safely
        let production_cost = parseFloat($('.production_cost').val()) || 0;
        let shipping_cost = parseFloat($('.shipping_cost').val()) || 0;

        // Calculate total unit cost from all unit_cost inputs
        let total_cost = 0;
        $('input[name="product_unit_cost[]"]').each(function () {
            total_cost += parseFloat($(this).val()) || 0;
        });

        $('input[name="product_qty[]"]').each(function () {
            var this_val = $(this).val();
            // ingredient_qty = parseFloat($(this).val(4 * this_val)) || 0;
        });

        // Final price including additional costs
        grand_total = price + shipping_cost + production_cost;

        // Update results in DOM
        $('#shipping_cost').html(shipping_cost.toFixed(2));
        $('#production_cost').html(production_cost.toFixed(2));
        $('#total').html(price.toFixed(2));
        $('.total_cost').val(price.toFixed(2));
        $('#grand_total').html(grand_total.toFixed(2));
        $('.grand_total').val(grand_total.toFixed(2));
    }
    $(document).ready(function() {
        calculate_price();
    });

</script>
@endpush
