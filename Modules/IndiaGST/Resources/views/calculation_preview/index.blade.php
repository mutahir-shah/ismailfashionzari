@extends('backend.layout.main')

@section('content')
<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4>{{ __('indiagst::app.calculation_preview') }}</h4>
                <p class="text-muted">{{ __('indiagst::app.preview_only_notice') }}</p>
            </div>
            <div class="card-body">
                <form id="calculation-preview-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.transaction_date') }}</label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.goods_service_classification') }}</label>
                            <select name="goods_service_classification" class="form-control" id="classification">
                                <option value="goods">{{ __('indiagst::app.goods') }}</option>
                                <option value="service">{{ __('indiagst::app.service') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.supply_rule_code') }}</label>
                            <select name="supply_rule_code" class="form-control" id="supply_rule">
                                <!-- Populated by JS based on classification -->
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.product_id') }}</label>
                            <input type="number" name="product_id" class="form-control" placeholder="Optional Product ID">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.tax_id') }} (Generic Tax)</label>
                            <input type="number" name="tax_id" class="form-control" placeholder="Optional Tax ID">
                        </div>
                        @if(auth()->user()->hasPermissionTo('gst_tax_profiles.override'))
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.explicit_gst_tax_profile') }}</label>
                            <select name="gst_tax_profile_id" class="form-control">
                                <option value="">{{ __('indiagst::app.none') }}</option>
                                @foreach($taxProfiles as $profile)
                                    <option value="{{ $profile->id }}">{{ $profile->code }} ({{ $profile->total_gst_rate }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.unit_price') }}</label>
                            <input type="number" name="unit_price" class="form-control" value="0" step="0.01">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.quantity') }}</label>
                            <input type="number" name="quantity" class="form-control" value="1" step="0.01">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.line_discount') }}</label>
                            <input type="number" name="line_discount" class="form-control" value="0" step="0.01">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.allocated_invoice_discount') }}</label>
                            <input type="number" name="allocated_invoice_discount" class="form-control" value="0" step="0.01">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.taxable_additional_charges') }}</label>
                            <input type="number" name="taxable_additional_charges" class="form-control" value="0" step="0.01">
                        </div>
                        <div class="col-md-3 form-group mt-4">
                            <input type="checkbox" name="is_tax_inclusive" value="1">
                            <label>{{ __('indiagst::app.is_tax_inclusive') }}</label>
                        </div>
                    </div>

                    <h5 class="mt-4">{{ __('indiagst::app.states_and_locations') }}</h5>
                    <div class="row">
                        <div class="col-md-3 form-group state-field state-supplier">
                            <label>{{ __('indiagst::app.supplier_state') }}</label>
                            <select name="supplier_state_code" class="form-control">
                                <option value="">{{ __('indiagst::app.select') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group state-field state-billing">
                            <label>{{ __('indiagst::app.billing_state') }}</label>
                            <select name="billing_state_code" class="form-control">
                                <option value="">{{ __('indiagst::app.select') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group state-field state-shipping">
                            <label>{{ __('indiagst::app.shipping_state') }}</label>
                            <select name="shipping_state_code" class="form-control">
                                <option value="">{{ __('indiagst::app.select') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group state-field state-delivery">
                            <label>{{ __('indiagst::app.delivery_destination_state') }}</label>
                            <select name="delivery_destination_state_code" class="form-control">
                                <option value="">{{ __('indiagst::app.select') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group state-field state-bill-to">
                            <label>{{ __('indiagst::app.bill_to_party_state') }}</label>
                            <select name="bill_to_party_state_code" class="form-control">
                                <option value="">{{ __('indiagst::app.select') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group state-field state-ship-to">
                            <label>{{ __('indiagst::app.ship_to_party_state') }}</label>
                            <select name="ship_to_party_state_code" class="form-control">
                                <option value="">{{ __('indiagst::app.select') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group state-field state-location">
                            <label>{{ __('indiagst::app.location_of_goods_state') }}</label>
                            <select name="location_of_goods_state_code" class="form-control">
                                <option value="">{{ __('indiagst::app.select') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group state-field state-installation">
                            <label>{{ __('indiagst::app.installation_site_state') }}</label>
                            <select name="installation_site_state_code" class="form-control">
                                <option value="">{{ __('indiagst::app.select') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if(auth()->user()->hasPermissionTo('gst_place_of_supply.override'))
                    <h5 class="mt-4">{{ __('indiagst::app.manual_pos_override') }}</h5>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>{{ __('indiagst::app.override_state') }}</label>
                            <select name="place_of_supply_override" class="form-control">
                                <option value="">{{ __('indiagst::app.none') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->code }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ __('indiagst::app.override_reason') }}</label>
                            <input type="text" name="override_reason" class="form-control">
                        </div>
                    </div>
                    @endif

                    <div class="form-group mt-4">
                        <button type="button" class="btn btn-primary" id="btn-calculate">{{ __('indiagst::app.calculate') }}</button>
                    </div>
                </form>

                <hr>

                <div id="result-container" style="display:none;">
                    <h4>{{ __('indiagst::app.calculation_result') }}</h4>
                    <div id="result-content" class="bg-light p-3 border">
                        <!-- Result JSON/Table goes here -->
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    const rulesConfig = {
        goods: [
            {id: 'goods_movement', text: 'Goods Involving Movement'},
            {id: 'goods_bill_to_ship_to', text: 'Bill-to Ship-to Model'},
            {id: 'goods_no_movement', text: 'Goods Without Movement'},
            {id: 'goods_installation', text: 'Installation or Assembly'},
            {id: 'export_with_tax', text: 'Export (With Tax) [Unsupported]'},
            {id: 'sez_with_tax', text: 'SEZ Supply (With Tax) [Unsupported]'}
        ],
        service: [
            {id: 'service_default_b2b', text: 'Default B2B Service'},
            {id: 'service_default_b2c', text: 'Default B2C Service'},
            {id: 'export_with_tax', text: 'Export (With Tax) [Unsupported]'},
            {id: 'oidar', text: 'OIDAR [Unsupported]'}
        ]
    };

    function updateRules() {
        const type = $('#classification').val();
        const ruleSelect = $('#supply_rule');
        ruleSelect.empty();
        
        rulesConfig[type].forEach(rule => {
            ruleSelect.append(new Option(rule.text, rule.id));
        });
        
        updateStateFields();
    }

    function updateStateFields() {
        $('.state-field').hide();
        $('.state-supplier').show(); // usually needed

        const rule = $('#supply_rule').val();
        if (rule === 'goods_movement') {
            $('.state-delivery').show();
        } else if (rule === 'goods_bill_to_ship_to') {
            $('.state-bill-to').show();
            $('.state-ship-to').show();
        } else if (rule === 'goods_no_movement') {
            $('.state-location').show();
        } else if (rule === 'goods_installation') {
            $('.state-installation').show();
        } else if (rule === 'service_default_b2b' || rule === 'service_default_b2c') {
            $('.state-billing').show();
        }
    }

    $(document).ready(function() {
        $('#classification').on('change', updateRules);
        $('#supply_rule').on('change', updateStateFields);
        updateRules();

        $('#btn-calculate').click(function() {
            $.ajax({
                url: '{{ url("indiagst/calculation-preview") }}',
                type: 'POST',
                data: $('#calculation-preview-form').serialize(),
                success: function(response) {
                    $('#result-container').show();
                    
                    let html = `<div class="row">
                        <div class="col-md-6">
                            <p><strong>Success:</strong> ${response.is_successful ? 'Yes' : 'No'}</p>
                            <p><strong>Profile Source:</strong> ${response.profile_resolution_source || 'N/A'}</p>
                            <p><strong>Taxability Type:</strong> ${response.taxability_type || 'N/A'}</p>
                            <p><strong>Supply Rule:</strong> ${response.supply_rule_code || 'N/A'}</p>
                            <p><strong>Place of Supply:</strong> ${response.place_of_supply_state_code || 'N/A'}</p>
                            <p><strong>Jurisdiction:</strong> ${response.jurisdiction || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Taxable Value:</strong> ${response.taxable_value || 0}</p>
                            <p><strong>Total Tax (GST):</strong> ${response.total_tax || 0}</p>
                            <p><strong>Total Cess:</strong> ${response.total_cess || 0}</p>
                            <p><strong>Final Value:</strong> ${response.total_value || 0}</p>
                        </div>
                    </div>`;

                    if (response.components && response.components.length > 0) {
                        html += `<h5>Components</h5><table class="table table-sm">
                            <thead><tr><th>Name</th><th>Rate</th><th>Amount</th></tr></thead><tbody>`;
                        response.components.forEach(c => {
                            html += `<tr><td>${c.name}</td><td>${c.rate}</td><td>${c.amount}</td></tr>`;
                        });
                        html += `</tbody></table>`;
                    }

                    if (response.validation_errors && response.validation_errors.length > 0) {
                        html += `<div class="alert alert-danger mt-2"><strong>Validation Errors:</strong><br>${response.validation_errors.join('<br>')}</div>`;
                    }
                    if (response.resolution_warnings && response.resolution_warnings.length > 0) {
                        html += `<div class="alert alert-warning mt-2"><strong>Warnings:</strong><br>${response.resolution_warnings.join('<br>')}</div>`;
                    }

                    $('#result-content').html(html);
                },
                error: function(xhr) {
                    alert('Error performing calculation.');
                }
            });
        });
    });
</script>
@endpush
