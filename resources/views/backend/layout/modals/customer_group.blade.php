        <div id="customer-group-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('Customer Group Report') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('The field labels marked with are required input fields') }}.</small></p>
                        <form action="{{ route('report.customer_group') }}" method="post">
                            @csrf
                        <?php
                        $lims_customer_group_list = DB::table('customer_groups')->where('is_active', true)->get();
                        ?>
                        <div class="form-group">
                            <label>{{ __('Customer Group') }} *</label>
                            <select name="customer_group_id" class="selectpicker form-control" required
                                data-live-search="true" id="customer-group-id" data-live-search-style="begins"
                                title="Select customer group...">
                                @foreach ($lims_customer_group_list as $customer_group)
                                    <option value="{{ $customer_group->id }}">{{ $customer_group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}" />
                        <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}" />

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('submit') }}</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
