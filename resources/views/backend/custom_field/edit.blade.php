@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Update Custom Field')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('custom-fields.update', $custom_field_data->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>{{__('db.Field Belongs To')}} *</label>
                                    <select name="belongs_to" required class="form-control">
                                        <option value="">{{__('db.Nothing Selected')}}</option>
                                        <option value="product">{{__('db.product')}}</option>
                                        <option value="sale">{{__('db.Sale')}}</option>
                                        <option value="purchase">{{__('db.Purchase')}}</option>
                                        <option value="customer">{{__('db.customer')}}</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                	<label>{{__('db.Field Name')}} *</label>
                                    <input type="text" name="name" required class="form-control" value="{{$custom_field_data->name}}">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{__('db.Field Type')}} *</label>
                                    <select name="type" required class="form-control">
                                        <option value="">{{__('db.Nothing Selected')}}</option>
                                        <option data-option="false" value="text">Text</option>
                                        <option data-option="false" value="number">Number</option>
                                        <option data-option="false" value="textarea">Textarea</option>
                                        <option data-option="true" data-option="true" value="select">Select</option>
                                        <option data-option="true" value="multi_select">Multi Select</option>
                                        <option data-option="true" value="checkbox">CheckBox</option>
                                        <option data-option="true" value="radio_button">Radio Button</option>
                                        <option data-option="false" value="date_picker">Date Picker</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group default-value-1 @if($custom_field_data->type == 'textarea'){{'d-none'}}@endif">
                                    <label>{{__('db.Default Value')}}</label>
                                    <input type="text" name="default_value_1" class="form-control" value="@if($custom_field_data->type!='textarea'){{$custom_field_data->default_value}}@endif">
                                </div>
                                <div class="col-md-4 form-group default-value-2 @if($custom_field_data->type != 'textarea'){{'d-none'}}@endif">
                                    <label>{{__('db.Default Value')}}</label>
                                    <textarea name="default_value_2" rows="5" class="form-control">@if($custom_field_data->type=='textarea'){{$custom_field_data->default_value}}@endif</textarea>
                                </div>
                                <div class="col-md-4 form-group option-value d-none">
                                    <label>{{__('db.Options')}} *</label> <i class="ti ti-info-circle" data-toggle="tooltip" title="{{__('db.Only use for Select, Multi Select, Checkbox, Radio Button types Populate the field by separating the options by coma eq apple,orange,banana')}}"></i>
                                    <textarea name="option_value" rows="5" class="form-control">{{$custom_field_data->option_value}}</textarea>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Grid (Bootstrap Column eq. 12) - Max is 12 *</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon2">col-md-</span>
                                        <input class="form-control mt-0" type="number" name="grid_value" required aria-describedby="basic-addon2" value="{{$custom_field_data->grid_value}}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Visibility')}}</label><br>
                                        <label class="radio-inline">
                                            <input type="checkbox" name="is_table"> {{__('db.Show on Table')}}
                                        </label>
                                        &nbsp;
                                        <label class="radio-inline is-invoice-section">
                                          <input type="checkbox" name="is_invoice"> {{__('db.Show on Invoice')}}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-2 mt-4">
                                    <input type="checkbox" name="is_required" value="1">
                                    <label>{{__('db.Required')}}</label>
                                </div>
                                <div class="col-md-2 mt-4">
                                    <input type="checkbox" name="is_admin" value="1">
                                    <label>{{__('db.Admin Only')}}</label>
                                </div>
                                <div class="col-md-2 mt-4">
                                    <input type="checkbox" name="is_disable" value="1">
                                    <label>{{__('db.Disabled')}}</label>
                                </div>
                                <div class="col-md-12 mt-2">
                                    <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
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
<script type="text/javascript">
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #custom-field-list-menu").addClass("active");
    $('[data-toggle="tooltip"]').tooltip();

    var customFieldData = <?php echo json_encode($custom_field_data) ?>;
    $("select[name=belongs_to]").val(customFieldData['belongs_to']);
    $("select[name=type]").val(customFieldData['type']);
    if(customFieldData['belongs_to'] == 'purchase')
        $(".is-invoice-section").hide();
    if(customFieldData['is_table'])
        $("input[name=is_table]").prop('checked', true);
    if(customFieldData['is_invoice'])
        $("input[name=is_invoice]").prop('checked', true);
    if(customFieldData['is_required'])
        $("input[name=is_required]").prop('checked', true);
    if(customFieldData['is_admin'])
        $("input[name=is_admin]").prop('checked', true);
    if(customFieldData['is_disable'])
        $("input[name=is_disable]").prop('checked', true);
    if($("select[name=type]").find(':selected').data('option')) {
        $(".option-value").removeClass('d-none');
        $("textarea[name=option_value]").prop("required", true);
    }
    $('.selectpicker').selectpicker('refresh');

    $("select[name=belongs_to]").on("change", function() {
        if($(this).val() == 'purchase')
            $(".is-invoice-section").hide(300);
        else
            $(".is-invoice-section").show(300);
    });

    $("select[name=type]").on("change", function () {
        if($(this).val() == 'textarea') {
            $(".default-value-2").removeClass('d-none');
            $(".default-value-1").addClass('d-none');
            $("input[name=default_value_1]").val('');
        }
        else {
            $(".default-value-1").removeClass('d-none');
            $(".default-value-2").addClass('d-none');
            $("textarea[name=default_value_2]").val('');
        }
        if($(this).find(':selected').data('option')) {
            $(".option-value").removeClass('d-none');
            $("textarea[name=option_value]").prop("required", true);
        }
        else {
            $(".option-value").addClass('d-none');
            $("textarea[name=option_value]").prop("required", false);
        }
    });
</script>
@endpush
