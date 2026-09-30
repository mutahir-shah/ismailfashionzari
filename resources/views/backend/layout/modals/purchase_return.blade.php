        <div id="add-purchase-return" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('return-purchase.create') }}" method="get">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">Add Purchase Return</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('The field labels marked with are required input fields') }}.</small></p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Purchase Reference') }} *</label>
                                    <input type="text" name="reference_no" class="form-control">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('db.submit') }}</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
