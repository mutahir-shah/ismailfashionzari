        <div id="expense-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('Add Expense') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('The field labels marked with are required input fields') }}.</small></p>
                        <form action="{{ route('expenses.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                        <?php
                        $lims_expense_category_list = DB::table('expense_categories')->where('is_active', true)->get();
                        $lims_tax_list = DB::table('taxes')->where('is_active', true)->get();
                        if (optional(Auth::user())->role_id > 2) {
                            $lims_warehouse_list = DB::table('warehouses')
                                ->where([['is_active', true], ['id', optional(Auth::user())->warehouse_id]])
                                ->get();
                        } else {
                            $lims_warehouse_list = DB::table('warehouses')->where('is_active', true)->get();
                        }
                        $lims_account_list = app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();
                        ?>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>{{ __('Date') }}</label>
                                <input type="text" name="created_at" class="form-control date"
                                    placeholder="{{ __('db.Choose date') }}"
                                    value="{{ date(gen_setting()->date_format, strtotime('now')) }}" />
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Expense Category') }} *</label>
                                <select name="expense_category_id"
                                    class="selectpicker form-control"
                                    required
                                    data-live-search="true"
                                    title="Select Expense Category...">
                                    @foreach ($lims_expense_category_list as $expense_category)
                                        <option value="{{ $expense_category->id }}">{{ $expense_category->name }} ({{ $expense_category->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Warehouse') }} *</label>
                                <select name="warehouse_id" class="selectpicker form-control" required
                                    data-live-search="true" data-live-search-style="begins"
                                    title="Select Warehouse...">
                                    @foreach ($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Amount') }} *</label>
                                <input type="number" name="amount" step="any" required class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Tax') }} ({{ __('db.Inclusive') }})</label>
                                <select name="tax_id" class="selectpicker form-control" data-live-search="true" title="Select Tax...">
                                    <option value="">{{ __('No Tax') }}</option>
                                    @foreach ($lims_tax_list as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12" id="employee-fields" style="display:none; width:100%">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Employee') }} *</label>
                                        <select id="expense-employee" name="employee_id"
                                            class="selectpicker form-control" data-live-search="true"
                                            title="Select Employee...">
                                            @foreach (\App\Models\Employee::where('is_active', 1)->get() as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label>{{ __('db.Type') }} *</label>
                                        <select id="expense-type" name="type" class="selectpicker form-control">
                                            <option value="expense">{{ __('db.Expense') }}</option>
                                            <option value="advance">{{ __('db.advance') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.employee_advance_payment_account') }}</label>
                                <select class="form-control selectpicker" name="account_id">
                                    @foreach ($lims_account_list as $account)
                                        @if ($account->is_default)
                                            <option selected value="{{ $account->id }}">{{ $account->name }}
                                                [{{ $account->account_no }}]</option>
                                        @else
                                            <option value="{{ $account->id }}">{{ $account->name }}
                                                [{{ $account->account_no }}]</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('db.Attach Document') }}</label>
                                    <i class="ti ti-info-circle" data-toggle="tooltip"
                                        title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                    <input type="file" name="document" class="form-control" />
                                    @if ($errors->has('extension'))
                                        <span>
                                            <strong>{{ $errors->first('extension') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
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
        <script>
            document.addEventListener('change', function (event) {
                if (!event.target.matches('#expense-modal select[name="expense_category_id"]')) return;
                var employeeFields = document.getElementById('employee-fields');
                if (employeeFields) employeeFields.style.display = event.target.value === '0' ? 'block' : 'none';
            });
        </script>
