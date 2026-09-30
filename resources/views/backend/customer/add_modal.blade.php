@php
    if(!isset($lims_customer_group_all)) {
        $lims_customer_group_all = \App\Models\CustomerGroup::where('is_active', true)->get();
    }
@endphp
<!-- add customer modal -->
<div id="addCustomer" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('customer.store') }}" method="post" enctype="multipart/form-data" id="customer-form">
            @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Customer')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.Customer Group')}} *</strong> </label>
                        <select required class="form-control selectpicker" name="customer_group_id">
                            @foreach($lims_customer_group_all as $customer_group)
                            <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.name')}} *</strong> </label>
                        <input type="text" name="customer_name" required class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.email')}}</label>
                        <input type="text" name="email" placeholder="example@example.com" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('db.Type') }}</label>
                        <select name="type" class="form-control">
                            @foreach(\App\Enums\CustomerTypeEnum::cases() as $case)
                                <option value="{{ $case->value }}">
                                    {{ $case->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.Phone Number')}} *</label>
                        <input type="text" name="phone_number" required class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.WhatsApp Number')}}</label>
                        <input type="text" name="wa_number" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.Address')}}</label>
                        <input type="text" name="address" required class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.City')}}</label>
                        <input type="text" name="city" required class="form-control">
                    </div>
                </div>
                <div class="col-md-6 customer-credit-field">
                    <div class="form-group">
                        <label>{{__('db.Credit Limit')}}</label>
                        <input type="number" name="credit_limit" class="form-control" value="0" step="any" min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.Tax Number')}}</label>
                        <input type="text" name="tax_no" class="form-control">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <input type="hidden" name="pos" value="1">
                <button type="button" class="btn btn-primary customer-submit-btn">{{__('db.submit')}}</button>
            </div>
        </div>
        </form>
      </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        function toggleModalCreditFields() {
            var customerType = $('#addCustomer select[name="type"]').val();
            if (customerType === 'walkin') {
                $('#addCustomer .customer-credit-field').hide();
                $('#addCustomer input[name="credit_limit"]').val('0');
            } else {
                $('#addCustomer .customer-credit-field').show();
            }
        }

        $('#addCustomer select[name="type"]').on('change', function() {
            toggleModalCreditFields();
        });

        toggleModalCreditFields();

        // Prevent duplicate bindings
        $('.customer-submit-btn').off('click').on("click", function() {
            var form = $("#customer-form");

            $.ajax({
                type:'POST',
                url:'{{route("customer.store")}}',
                data: form.serialize(),
                success:function(response) {
                    if(response['id']) {
                        key = response['id'];
                        value = response['name']+' ['+response['phone_number']+']';
                        // For typical select fields
                        if($('select[name="customer_id"]').length) {
                            $('select[name="customer_id"]').append('<option value="'+ key +'" data-type="'+response['type']+'">'+ value +'</option>');
                            $('select[name="customer_id"]').val(key).trigger('change');
                            
                            // If the page has a setCustomerGroupRate function (e.g., Sale/Create), call it
                            if (typeof setCustomerGroupRate === "function") {
                                setCustomerGroupRate(key);
                            }
                        }
                        // For Project specific field (edit_customer_id)
                        if($('select[name="edit_customer_id"]').length) {
                            $('select[name="edit_customer_id"]').append('<option value="'+ key +'" data-type="'+response['type']+'">'+ value +'</option>');
                            $('select[name="edit_customer_id"]').val(key).trigger('change');
                        }
                        $('.selectpicker').selectpicker('refresh');
                        $("#addCustomer").modal('hide');
                        $("#customer-form")[0].reset();
                    } else {
                        alert('Something went wrong!');
                    }
                },
                error:function(xhr) {
                    var msg = 'Error adding customer. Make sure all required fields are filled correctly.';
                    if (xhr.responseText) {
                        try {
                            var err = JSON.parse(xhr.responseText);
                            if (err.errors) {
                                msg = Object.values(err.errors).flat().join('\n');
                            } else if (err.message) {
                                msg = err.message;
                            }
                        } catch(e) {}
                    }
                    alert(msg);
                }
            });
        });
    });
</script>
@endpush
