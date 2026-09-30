        <div id="biller-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
            class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('Biller Report') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('The field labels marked with are required input fields') }}.</small></p>
                        <form action="{{ route('report.biller') }}" method="post">
                            @csrf
                        <?php
                        $lims_biller_list = DB::table('billers')->where('is_active', true)->get();
                        ?>
                        <div class="form-group">
                            <label>{{ __('Biller') }} *</label>
                            <select name="biller_id" class="selectpicker form-control" required
                                data-live-search="true" id="user-id" data-live-search-style="begins"
                                title="Select biller...">
                                @foreach ($lims_biller_list as $biller)
                                    <option value="{{ $biller->id }}">
                                        {{ $biller->name . ' (' . $biller->phone_number . ')' }}</option>
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
