@extends('backend.layout.main')

@section('content')

<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Update Supplier')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic">
                            <small>{{__('db.The field labels marked with are required input fields')}}.</small>
                        </p>

                        <form action="{{ route('supplier.update', $lims_supplier_data->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                        <div class="row">

                            {{-- Name --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.name')}} *</label>
                                    <input type="text" name="name" value="{{$lims_supplier_data->name}}" required class="form-control">
                                </div>
                            </div>

                            {{-- Image --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Image')}}</label>
                                    <input type="file" name="image" class="form-control">
                                    @if($errors->has('image'))
                                        <span><strong>{{ $errors->first('image') }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            {{-- Company Name --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Company Name')}} *</label>
                                    <input type="text" name="company_name" value="{{$lims_supplier_data->company_name}}" required class="form-control">
                                    @if($errors->has('company_name'))
                                        <span><strong>{{ $errors->first('company_name') }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            {{-- VAT --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.VAT Number')}} / {{__('db.Tax Number')}}</label>
                                    <input type="text" name="vat_number" value="{{$lims_supplier_data->vat_number}}" class="form-control">
                                </div>
                            </div>

                            {{-- Opening Balance --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Opening balance')}} ({{__('db.Due')}})</label>
                                    <input type="number" name="opening_balance"
                                           class="form-control"
                                           value="{{$lims_supplier_data->opening_balance ?? 0}}"
                                           step="any" min="0">
                                </div>
                            </div>

                            {{-- Payment Term --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Payment Term</label>
                                    <div class="d-flex">
                                        <input type="number"
                                               name="pay_term_no"
                                               class="form-control"
                                               value="{{$lims_supplier_data->pay_term_no}}"
                                               placeholder="30">
                                        <select name="pay_term_period" class="form-control ml-2">
                                            <option value="days" {{($lims_supplier_data->pay_term_period == 'days') ? 'selected' : ''}}>Days</option>
                                            <option value="months" {{($lims_supplier_data->pay_term_period == 'months') ? 'selected' : ''}}>Months</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.email')}} *</label>
                                    <input type="email" name="email" value="{{$lims_supplier_data->email}}" required class="form-control">
                                    @if($errors->has('email'))
                                        <span><strong>{{ $errors->first('email') }}</strong></span>
                                    @endif
                                </div>
                            </div>

                            {{-- Phone --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Phone Number')}} *</label>
                                    <input type="text" name="phone_number" value="{{$lims_supplier_data->phone_number}}" required class="form-control">
                                </div>
                            </div>

                            {{-- WhatsApp --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.WhatsApp Number')}}</label>
                                    <input type="text" name="wa_number" value="{{$lims_supplier_data->wa_number}}" class="form-control">
                                </div>
                            </div>

                            {{-- Address --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Address')}} *</label>
                                    <input type="text" name="address" value="{{$lims_supplier_data->address}}" required class="form-control">
                                </div>
                            </div>

                            {{-- City --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.City')}} *</label>
                                    <input type="text" name="city" value="{{$lims_supplier_data->city}}" required class="form-control">
                                </div>
                            </div>

                            {{-- State --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.State')}}</label>
                                    <input type="text" name="state" value="{{$lims_supplier_data->state}}" class="form-control">
                                </div>
                            </div>

                            {{-- Postal Code --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Postal Code')}}</label>
                                    <input type="text" name="postal_code" value="{{$lims_supplier_data->postal_code}}" class="form-control">
                                </div>
                            </div>

                            {{-- Country --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{__('db.Country')}}</label>
                                    <input type="text" name="country" value="{{$lims_supplier_data->country}}" class="form-control">
                                </div>
                            </div>

                            {{-- Bank Details --}}
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Supplier Bank Account Details</label>
                                    <textarea name="bank_details"
                                              class="form-control"
                                              rows="3"
                                              placeholder="Bank Name, Account Name, Account Number, IBAN, SWIFT etc.">{{$lims_supplier_data->bank_details}}</textarea>
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
    $("ul#people #supplier-edit-menu").addClass("active");
</script>
@endpush
