        <div id="account-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ app(\App\Services\AccountingModeService::class)->isDoubleEntryAuthoritative() ? __('db.payment_account_add') : __('db.Add Account') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('The field labels marked with are required input fields') }}.</small></p>
                        <form action="{{ route('accounts.store') }}" method="post">
                            @csrf
                        <div class="form-group">
                            <label>{{ __('Account No') }} *</label>
                            <input type="text" name="account_no" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{{ __('name') }} *</label>
                            <input type="text" name="name" required class="form-control">
                        </div>
                        @if(!app(\App\Services\AccountingModeService::class)->isDoubleEntryAuthoritative())<div class="form-group">
                            <label>{{ __('Initial Balance') }}</label>
                            <input type="number" name="initial_balance" step="any" class="form-control">
                        </div>@else<div class="alert alert-info">{{ __('db.payment_account_opening_balance_guidance') }}</div>@endif
                        <div class="form-group">
                            <label>{{ __('Note') }}</label>
                            <textarea name="note" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('submit') }}</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
