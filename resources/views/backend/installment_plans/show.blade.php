@extends('backend.layout.main')

@section('content')

<style>
    .btn-icon i { margin-right: 5px; }
    .top-fields { margin-top: 10px; position: relative; }
    .top-fields label {
        font-size: 11px;
        font-weight: 600;
        margin-left: 10px;
        padding: 0 3px;
        position: absolute;
        top: -8px;
        z-index: 9;
    }
    .top-fields input {
        font-size: 13px;
        height: 45px;
    }
</style>

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Instalment Plan Details</h5>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4"><strong>Plan Name:</strong> {{ $plan->name }}</div>
                <div class="col-md-4"><strong>Price:</strong> {{ number_format($plan->price, 2) }}</div>
                <div class="col-md-4"><strong>Additional Amount:</strong> {{ number_format($plan->additional_amount, 2) }}</div>
                <div class="col-md-4"><strong>Total Amount:</strong> {{ number_format($plan->total_amount, 2) }}</div>
                <div class="col-md-4"><strong>Down Payment:</strong> {{ number_format($plan->down_payment, 2) }}</div>
                <div class="col-md-4"><strong>Months:</strong> {{ $plan->months }}</div>
            </div>

            <hr>

            <h6>Instalments</h6>
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Payment Date</th>
                        <th class="text-center w-25">Status</th>
                        <th class="text-center w-25">Amount</th>
                        <th class="text-center" style="width: 130px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plan->installments as $key => $installment)
                        <tr>
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td class="text-center">{{ \Carbon\Carbon::parse($installment->payment_date)->format('d M Y') }}</td>
                            @if ($installment->status === 'completed')
                                <!-- <td class="text-center">{{ \Carbon\Carbon::parse($installment->updated_at)->format('d M Y') }}</td> -->
                                <td class="text-center">
                                    <span class="badge bg-success">Completed</span>
                                </td>
                            @else
                                <!-- <td class="text-center">{{ \Carbon\Carbon::parse($installment->payment_date)->format('d M Y') }}</td> -->
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">Pending</span>
                                </td>
                            @endif
                            <td class="text-center">{{ number_format($installment->amount, 2) }}</td>
                            <td class="text-center">
                                @if ($installment->status === 'pending' && !$saleFullyPaid)
                                    <button 
                                        class="btn btn-sm btn-primary add-payment-btn" 
                                        data-id="{{ $installment->id }}" 
                                        data-amount="{{ $installment->amount }}"
                                        data-date="{{ $installment->payment_date }}">
                                        <i class="ti ti-plus"></i> Add Payment
                                    </button>
                                @elseif ($saleFullyPaid)
                                    <span class="badge bg-success py-2 px-3">
                                        <i class="ti ti-check"></i> Sale Fully Paid
                                    </span>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        <i class="ti ti-check"></i> Paid
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No installments found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ✅ Add Payment Modal -->
<div class="modal fade" id="add-payment" tabindex="-1" aria-labelledby="addPaymentLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addPaymentLabel"><i class="ti ti-plus-circle me-2"></i> Add Payment</h5>
                <button type="button" class="btn-close btn-close-white button-close-x" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>

            <div class="modal-body">
                <form action="{{ route('sale.add-payment') }}" method="POST" enctype="multipart/form-data" class="payment-form">
                    @csrf
                    <input type="hidden" name="balance">
                    <input type="hidden" name="installment_id" id="installment_id">
                    <input type="hidden" name="sale_id" value="{{ $plan->reference_id }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Received Amount *</label>
                            <input type="number" name="paying_amount" class="form-control" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Paying Amount *</label>
                            <input type="number" id="amount" name="amount" class="form-control" readonly required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_at" id="payment_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Payment Receiver</label>
                            <input type="text" name="payment_receiver" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="paid_by_id" class="form-label d-block">Paid By *</label>
                                <select name="paid_by_id" id="paid_by_id" class="form-select w-100">
                                    @if(in_array("cash", $options))
                                        <option value="1">{{ __('db.Cash') }}</option>
                                    @endif
                                    @if(in_array("gift_card", $options))
                                        <option value="2">{{ __('db.Gift Card') }}</option>
                                    @endif
                                    @if(in_array("card", $options))
                                        <option value="3">{{ __('db.Credit Card') }}</option>
                                    @endif
                                    @if(in_array("cheque", $options))
                                        <option value="4">{{ __('db.Cheque') }}</option>
                                    @endif
                                    @if(in_array("deposit", $options))
                                        <option value="6">{{ __('db.Deposit') }}</option>
                                    @endif
                                    @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                        <option value="7">{{ __('db.Points') }}</option>
                                    @endif
                                    @foreach($options as $option)
                                        @if(!in_array($option, ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'paypal', 'pesapal']))
                                            <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="account_id" class="form-label">Account *</label>
                                <select class="form-select w-100" name="account_id" id="account_id">
                                    @foreach($lims_account_list as $account)
                                        <option value="{{ $account->id }}" {{ $account->is_default ? 'selected' : '' }}>
                                            {{ $account->name }} [{{ $account->account_no }}]
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Attach Document</label>
                            <input type="file" name="document" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Payment Note</label>
                            <textarea name="payment_note" rows="3" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-secondary button-close-cancel" data-bs-dismiss="modal">
                            <i class="ti ti-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Submit Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // 🟢 When clicking "Add Payment"
    $(document).on('click', '.add-payment-btn', function () {
        const id = $(this).data('id');
        const amount = $(this).data('amount');
        const date = $(this).data('date');

        $('#installment_id').val(id);
        $("input[name='paying_amount']").val(amount);
        $("input[name='balance']").val(amount);
        $('#amount').val(amount);
        $('#payment_at').val(date ? date.split(' ')[0] : '{{ date('Y-m-d') }}');

        $('#add-payment').modal('show');
    });

    // 🟠 Reset form when modal closes
    $('#add-payment').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('#installment_id').val('');
    });

    // 🟣 Optional: prevent double submit
    $('.payment-form').on('submit', function() {
        $(this).find('button[type="submit"]').prop('disabled', true).text('Processing...');
    });

    $('.button-close-x, .button-close-cancel').on('click', function() {
        $('#add-payment').modal('hide');
    });
});
</script>
@endpush
