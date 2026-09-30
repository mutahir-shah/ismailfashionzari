@extends('backend.layout.main')

@section('content')

<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Add Supplier')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic">
                            <small>{{__('db.The field labels marked with are required input fields')}}.</small>
                        </p>

                        <form action="{{ route('supplier.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row">

                                {{-- Both Customer & Supplier --}}
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <input type="checkbox" name="both" value="1" />&nbsp;
                                        <label>{{__('db.Both Customer and Supplier')}}</label>
                                    </div>
                                </div>

                                <div class="col-md-4 customer-group-section">
                                    <div class="form-group">
                                        <label>{{__('db.Customer Group')}} *</label>
                                        <select class="form-control selectpicker" id="customer-group-id" name="customer_group_id">
                                            @foreach($lims_customer_group_all as $customer_group)
                                                <option value="{{$customer_group->id}}">
                                                    {{$customer_group->name}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Name --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.name')}} *</label>
                                        <input type="text" name="name" required class="form-control">
                                    </div>
                                </div>

                                {{-- Image --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Image')}}</label>
                                        <input type="file" name="image" class="form-control">
                                    </div>
                                </div>

                                {{-- Company Name --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Company Name')}} *</label>
                                        <input type="text" name="company_name" required class="form-control">
                                    </div>
                                </div>

                                {{-- VAT --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.VAT Number')}} / {{__('db.Tax Number')}}</label>
                                        <input type="text" name="vat_number" class="form-control">
                                    </div>
                                </div>

                                {{-- Opening Balance --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Opening balance')}} ({{__('db.Due')}})</label>
                                        <input type="number" name="opening_balance"
                                            class="form-control"
                                            value="0" step="any" min="0">
                                    </div>
                                </div>

                                {{-- Email --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.email')}} *</label>
                                        <input type="email"
                                            name="email"
                                            required
                                            class="form-control">
                                    </div>
                                </div>

                                {{-- Phone --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Phone Number')}} *</label>
                                        <input type="text"
                                            name="phone_number"
                                            required
                                            class="form-control">
                                    </div>
                                </div>

                                {{-- WhatsApp --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.WhatsApp Number')}}</label>
                                        <input type="text"
                                            name="wa_number"
                                            class="form-control">
                                    </div>
                                </div>

                                {{-- Address --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Address')}} *</label>
                                        <input type="text"
                                            name="address"
                                            required
                                            class="form-control">
                                    </div>
                                </div>

                                {{-- City --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.City')}} *</label>
                                        <input type="text"
                                            name="city"
                                            required
                                            class="form-control">
                                    </div>
                                </div>

                                {{-- State --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.State')}}</label>
                                        <input type="text"
                                            name="state"
                                            class="form-control">
                                    </div>
                                </div>

                                {{-- Postal Code --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Postal Code')}}</label>
                                        <input type="text"
                                            name="postal_code"
                                            class="form-control">
                                    </div>
                                </div>

                                {{-- Country --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Country')}}</label>
                                        <input type="text"
                                            name="country"
                                            class="form-control">
                                    </div>
                                </div>

                                {{-- Payment Term --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.payment_term')}}</label>
                                        <div class="d-flex">
                                            <input type="number"
                                                name="pay_term_no"
                                                class="form-control"
                                                placeholder="30">
                                            <select name="pay_term_period"
                                                    class="form-control ml-2">
                                                <option value="days">Days</option>
                                                <option value="months">Months</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Bank Details --}}
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Supplier Bank Account Details</label>
                                        <textarea name="bank_details"
                                                class="form-control"
                                                rows="3"
                                                placeholder="Bank Name, Account Name, Account Number, IBAN, SWIFT etc."></textarea>
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <div class="col-md-12">
                                    <div class="form-group mt-4">
                                        <input type="submit"
                                            value="{{__('db.submit')}}"
                                            class="btn btn-primary">
                                    </div>
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
    $("ul#people").siblings('a').attr('aria-expanded','true');
    $("ul#people").addClass("show");
    $("ul#people #supplier-create-menu").addClass("active");
    $(".customer-group-section").hide();

    $('input[name="both"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.customer-group-section').show(300);
            $('select[name="customer_group_id"]').prop('required',true);
        }
        else{
            $('.customer-group-section').hide(300);
            $('select[name="customer_group_id"]').prop('required',false);
        }
    });
</script>
@endpush
