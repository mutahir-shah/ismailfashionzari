@extends('backend.layout.main')

@section('content')
<section id="pos-layout" class="forms hidden-print">
    <div class="container-fluid">
        <x-error-message key="not_permitted" />
        <div class="row">
            <div class="col-md-12">
                <form id="bulkDeleteForm" action="{{ route('purchases.forceDeleteSelected') }}" method="POST">
                    @csrf
                    @method('DELETE')

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Deleted Purchases</h5>
                            <div>
                                <button type="submit" class="btn btn-sm btn-danger ms-3" id="deleteSelectedBtn" disabled onclick="return confirm('Are you sure you want to permanently delete the selected records? This action cannot be undone!');">
                                    <i class="fas fa-trash-alt"></i> Delete Permanently
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th><input type="checkbox" id="selectAll"></th>
                                            <th style="width: 10%">Date</th>
                                            <th style="width: 10%">Reference ID</th>
                                            <th style="width: 10%">Created By</th>
                                            <th style="width: 10%">Supplier</th>
                                            <th style="width: 10%">Warehouse</th>
                                            <th style="width: 10%">Payment Status</th>
                                            <th style="width: 10%">Amount</th>
                                            <th style="width: 10%">Due</th>
                                            <th style="width: 10%">Deleted By</th>
                                            <th style="width: 10%">Deleted At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lims_deleted_data as $purchase)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="ids[]" value="{{ $purchase->id }}" class="rowCheckbox">
                                            </td>
                                            <td class="align-middle">{{ $purchase->created_at->format(config('date_format') . ' h:i:s') }}</td>
                                            <td class="align-middle">{{ $purchase->reference_no }}</td>
                                            <td class="align-middle">{{ $purchase->user->name }}</td>
                                            <td class="align-middle">
                                                {{ $purchase->supplier->name ?? 'N/A' }}
                                            </td>
                                            <td class="align-middle">{{ $purchase->warehouse->name }}</td>
                                            <td class="align-middle">
                                                {{ $purchase->payment_status == 1 ? 'Due' : 'Paid' }}
                                            </td>
                                            <td class="align-middle">{{ $purchase->grand_total }}</td>
                                            <td class="align-middle">{{ $purchase->grand_total - $purchase->paid_amount }}</td>
                                            <td class="align-middle">{{ $purchase->deleter->name ?? 'System' }}</td>
                                            <td class="align-middle">{{ $purchase->deleted_at->format(config('date_format') . ' h:i:s') }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="11" class="text-center py-4 text-muted align-middle">
                                                <i class="fas fa-trash-alt fa-2x mb-3"></i><br>
                                                No deleted purchases found
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Select/Deselect all checkboxes
    $('#selectAll').on('change', function () {
        $('.rowCheckbox').prop('checked', this.checked);
        toggleDeleteButton();
    });

    $('.rowCheckbox').on('change', function () {
        if ($('.rowCheckbox:checked').length === $('.rowCheckbox').length) {
            $('#selectAll').prop('checked', true);
        } else {
            $('#selectAll').prop('checked', false);
        }
        toggleDeleteButton();
    });

    function toggleDeleteButton() {
        $('#deleteSelectedBtn').prop('disabled', $('.rowCheckbox:checked').length === 0);
    }
</script>
@endpush
