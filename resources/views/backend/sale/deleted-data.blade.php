@extends('backend.layout.main')

@section('content')
<section id="pos-layout" class="forms hidden-print">
    <div class="container-fluid">
        <x-error-message key="not_permitted" />
        <div class="row">
            <div class="col-md-12">
                <form id="bulkDeleteForm" action="{{ route('sales.forceDeleteSelected') }}" method="POST">
                    @csrf
                    @method('DELETE')

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Deleted Sales</h5>
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
                                            <th>Date</th>
                                            <th>Reference ID</th>
                                            <th>Created By</th>
                                            <th>Customer</th>
                                            <th>Warehouse</th>
                                            <th>Payment Status</th>
                                            <th>Amount</th>
                                            <th>Due</th>
                                            <th>Deleted By</th>
                                            <th>Deleted At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lims_deleted_data as $sale)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="ids[]" value="{{ $sale->id }}" class="rowCheckbox">
                                            </td>
                                            <td>{{ $sale->created_at->format(config('date_format') . ' h:i:s') }}</td>
                                            <td>{{ $sale->reference_no }}</td>
                                            <td>{{ $sale->user->name }}</td>
                                            <td>{{ $sale->customer->name }}</td>
                                            <td>{{ $sale->warehouse->name }}</td>
                                            <td>
                                                @if($sale->payment_status == 1)
                                                    Pending
                                                @elseif($sale->payment_status == 2)
                                                    Due
                                                @elseif($sale->payment_status == 3)
                                                    Partial
                                                @elseif($sale->payment_status == 4)
                                                    Paid
                                                @endif
                                            </td>
                                            <td>{{ $sale->grand_total }}</td>
                                            <td>{{ $sale->grand_total - $sale->paid_amount }}</td>
                                            <td>{{ $sale->deleter->name ?? 'System' }}</td>
                                            <td>{{ $sale->deleted_at->format(config('date_format') . ' h:i:s') }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="11" class="text-center py-4 text-muted align-middle">
                                                <i class="fas fa-trash-alt fa-2x mb-3"></i><br>
                                                No deleted sales found
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
