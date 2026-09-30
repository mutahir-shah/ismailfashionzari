        <div id="add-exchange" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">

            <div role="document" class="modal-dialog">
                <div class="modal-content">

                    <form action="{{ route('exchange.create') }}" method="get">

                        <!-- Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('db.Add Sale Exchange') }}</h5>
                            <button type="button" data-dismiss="modal" class="close">
                                <span><i class="ti ti-x"></i></span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">

                            <!-- Instruction for user -->
                            <p class="text-muted">
                                <small>
                                    You can enter a Sale Reference to exchange a specific sale. <br>
                                    Or continue without it to open a blank Exchange page.
                                </small>
                            </p>

                            <!-- Optional Reference Input -->
                            <div class="form-group">
                                <label>{{ __('db.Sale Reference') }} ({{ __('db.Optional') }})</label>
                                <input type="text" name="reference_no" class="form-control"
                                    placeholder="Example: SALE-1234">
                            </div>

                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">

                            <!-- Submit with reference (if provided) -->
                            <button type="submit" class="btn btn-primary">
                                {{ __('db.Continue to Exchange') }}
                            </button>

                            <!-- Direct access without reference -->
                            <a href="{{ route('exchange.create') }}" class="btn btn-outline-secondary">
                                {{ __('db.Continue Without Reference') }}
                            </a>

                        </div>

                    </form>

                </div>
            </div>
        </div>
