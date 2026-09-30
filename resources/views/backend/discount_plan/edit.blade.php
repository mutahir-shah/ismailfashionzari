@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Update Discount Plan')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('discount-plans.update', $lims_discount_plan->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-4">
                                	<label>{{__('db.name')}} *</label>
                                    <input type="text" name="name" value="{{$lims_discount_plan->name}}" required class="form-control">
                                </div>
                                <div class="col-md-4">
                                	<label>{{__('db.customer')}} *</label>
                                    <select required name="customer_id[]" 
                                            id="customer-select"
                                            class="selectpicker form-control customer-ids" 
                                            data-live-search="true" 
                                            title="Select customer..." 
                                            multiple>
                                        @foreach($lims_customer_list as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name . ' (' . $customer->phone_number . ')' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                	<label>{{__('db.Type')}} *</label>
                                    <select required name="type" 
                                            id="type-select"
                                            class="selectpicker form-control">
                                        <option value="limited" {{ $lims_discount_plan->type == 'limited' ? 'selected' : '' }}>Limited</option>
                                        <option value="generic" {{ $lims_discount_plan->type == 'generic' ? 'selected' : '' }}>Generic</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <div class="form-group">
                                    	@if($lims_discount_plan->is_active)
                                        <input type="checkbox" name="is_active" value="1" checked>
                                        @else
                                        <input type="checkbox" name="is_active" value="1">
                                        @endif
                                        <label>{{__('db.Active')}}</label>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3">
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
    $("ul#setting #discount-plan-menu").addClass("active");

    $('[data-toggle="tooltip"]').tooltip();

    $(document).ready(function () {
        var selected_customer_ids = <?php echo json_encode($selected_customer_ids); ?>;
        var all_customer_ids = <?php echo json_encode($all_customer_ids); ?>;

        var initialType = $('#type-select').val();
        if (initialType === 'generic') {
            $(".customer-ids").val(all_customer_ids);
        } else {
            $(".customer-ids").val(selected_customer_ids);
        }
        $('.selectpicker').selectpicker('refresh');

        $('#type-select').on('change', function () {
            if ($(this).val() === 'generic') {
                $(".customer-ids").val(all_customer_ids);
            } else {
                $(".customer-ids").val(selected_customer_ids);
            }
            $('.selectpicker').selectpicker('refresh');
        });
    });
</script>
@endpush
