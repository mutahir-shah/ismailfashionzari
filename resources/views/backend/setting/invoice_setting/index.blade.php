@extends('backend.layout.main') @section('content')

@push('css')
<style>
#table-loader {
    background-color: #f9f9f9;
}
.table {
    background-color: #fff;
    border-collapse: collapse !important;
    border-radius: 10px;
}

/* Switch track */
.custom-switch .custom-control-input:disabled:checked ~ .custom-control-label::before,
.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #7c5cc4; /* Green when active */
    border-color: #7c5cc4;
}

.custom-control-input:not(:checked) ~ .custom-control-label::before {
    background-color: #ddd; /* Red when inactive */
    border-color: #ddd;
}
</style>
@endpush

    @include('includes.session_message')
    <section>
        <div class="container-fluid mt-5">

            <div class=" mb-3 ">
                <a class="btn btn-primary float-end" href="{{ route('settings.invoice.create') }}"> <i class="ti ti-plus"></i>
                    {{ __('db.Add New Invoice Setting') }}</a>
            </div>


            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-3">{{ __('db.Template Name') }}</th>
                            <th class="col-2">{{ __('db.Size') }}</th>
                            <th class="col-2  text-center">{{ __('db.Default') }}</th>
                            <th class="col-3 text-center">{{ __('db.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="table-loader" style="display: none;">
                            <td colspan="5" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden"></span>
                                </div>
                            </td>
                        </tr>
                        @forelse ($invoiceSettings as $invoice)
                            <tr>
                                <td>{{ $invoice->template_name }}</td>
                                <td>{{ $invoice->size }}</td>
                                <td class="text-center">
                                    @if ($invoice->is_default)
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" 
                                                class="custom-control-input set-default" 
                                                id="switchDefault{{ $invoice->id }}" 
                                                checked 
                                                disabled>
                                            <label class="custom-control-label" for="switchDefault{{ $invoice->id }}">
                                            </label>
                                        </div>
                                    @else
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" 
                                                class="custom-control-input set-default change-status" 
                                                id="switchDefault{{ $invoice->id }}"
                                                data-id="{{ $invoice->id }}"
                                                data-column="is_default" 
                                                data-url="{{ route('settings.invoice.update', $invoice->id) }}">
                                            <label class="custom-control-label" style="cursor: pointer;" for="switchDefault{{ $invoice->id }}">
                                            </label>
                                        </div>
                                    @endif
                                </td>



                                <td class="text-center align-middle">
                                    <a class="btn btn-warning btn-sm"
                                        href="{{ route('settings.invoice.edit', $invoice->id) }}"></i>{{ __('db.update')}}</a>
                                    <button class="btn btn-danger btn-sm delete-invoice" data-id="{{ $invoice->id }}"
                                        data-url="{{ route('settings.invoice.destroy', $invoice->id) }}">{{ __('db.delete')}}</button>
                                    {{-- <a href="{{ route('settings.invoice.show', $invoice->id) }}" class="btn btn-outline-primary">Show
                                    </a> --}}
                                </td>
                            </tr>
                        @empty
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.delete-invoice').on('click', function() {
                var button = $(this);
                var id = button.data('id');
                var url = button.data('url');
                var row = button.closest('tr');

                if (confirm('Are you sure you want to delete this invoice setting?')) {
                    $('#table-loader').show(); // Show loader row

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success == true) {
                                row.fadeOut(400, function() {
                                    $(this).remove();
                                });
                            }else if (response.success == false){
                                alert('Default invoice cannot be deleted');
                            } else {
                                alert(response.not_permitted);
                            }

                        },
                        error: function() {
                            alert('Error deleting invoice.');
                        },
                        complete: function() {
                            $('#table-loader').hide();
                        }
                    });
                }
            });

            $(document).on('click', '.change-status', function() {
                $('.set-default').prop('checked', false);
                $(this).prop('checked', true);
                let isChecked = $(this).is(':checked');

                var button = $(this);
                var id = button.data('id');
                var url = button.data('url');
                var column = button.data('column');
                console.log(id, url, column)
                if (confirm('Are you sure you want to change the status?')) {
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'PUT', // important if you're using Laravel's resourceful routes
                            id: id,
                            column: column
                        },
                        success: function() {
                            $(this).prop('checked', true);
                        },
                        error: function() {
                            alert("Failed to update status.");
                        }
                    });
                }
            })

        });
    </script>
@endpush
