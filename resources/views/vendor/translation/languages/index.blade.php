@extends('backend.layout.main')
@push('css')

@endpush
@section('content')

    @include('includes.session_message')
    
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Languages</h2>
            @if (count($languages))
                <a href="{{ route('translations') }}" class="btn btn-primary">Manage Translations</a>
            @endif
        </div>

        <div class="alert alert-info">
            Default Language: <strong>{{ $defaultLanguage ? $defaultLanguage->name : 'No Default Language' }}</strong>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" id="language_code" class="form-control" placeholder="Language Code (e.g., en)">
            </div>
            <div class="col-md-4">
                <input type="text" id="language_name" class="form-control" placeholder="Language Name (e.g., English)">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" onclick="addLanguage()">Add Language</button>
            </div>
        </div>

        <!-- Modal for updating the language -->
        <div class="modal fade" id="updateLanguageModal" tabindex="-1" aria-labelledby="updateLanguageModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateLanguageModalLabel">Update Language</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="updateLanguageForm">
                            <input type="hidden" id="update_id">
                            <div class="form-group">
                                <label for="update_language_code">Language Code (e.g., en)</label>
                                <input type="text" id="update_language_code" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="update_language_name">Language Name (e.g., English)</label>
                                <input type="text" id="update_language_name" class="form-control">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="updateLanguage()">Save changes</button>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-secondary">
                <tr>
                    <th class="col-2">Locale</th>
                    <th class="col-5">Name</th>
                    <th class="col-2  text-center">Is Default</th>
                    <th class="col-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="language_list">
                @foreach ($languages as $language)
                    <tr>
                        <td>{{ $language->language }}</td>
                        <td>{{ $language->name }}</td>
                        <td class="text-center align-middle">
                            @if ($language->is_default)
                                <span class="badge bg-success">Default</span>
                            @else
                                <button class="btn btn-sm btn-outline-secondary" onclick="setDefault({{ $language->id }})">Set Default</button>
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            <button class="btn btn-warning btn-sm" onclick="showUpdateModal({{ $language->id }}, '{{ $language->language }}', '{{ $language->name }}')"></i>Update</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteLanguage({{ $language->id }})"></i>Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>


@endsection

@push('scripts')

<script>
    
    let languageId = null;
    let user_verified = @json(config('app.user_verified'));

    function addLanguage() {
        if(user_verified == '1') {
            let language = $('#language_code').val();
            let name = $('#language_name').val();

            $.ajax({
                url: '{{url("/")}}/languages/create',
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                contentType: 'application/json',
                data: JSON.stringify({ language, name }),
                success: function () {
                    $('#language_code, #language_name').val('');
                    location.reload();
                },
                error: function () {
                    showErrorMessage("Error adding language.");
                }
            });
        }
        else {
         alert('This feature is disable for demo!');
        }
    }

    function showUpdateModal(id, code, name) {
        $('#update_id').val(id);
        $('#update_language_code').val(code);
        $('#update_language_name').val(name);
        $('#updateLanguageModal').modal('show');
    }

    function updateLanguage() {
        if(user_verified == '1') {
            languageId = $('#update_id').val();
            let language = $('#update_language_code').val();
            let name = $('#update_language_name').val();

            $.ajax({
                url: `{{url("/")}}/languages/${languageId}`,
                method: 'PUT',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                contentType: 'application/json',
                data: JSON.stringify({ language, name }),
                success: function (response) {
                    if (response.error) {
                        showErrorMessage(response.error);
                    } else {
                        $('#updateLanguageModal').modal('hide');
                        location.reload();
                    }
                },
                error: function () {
                    showErrorMessage("An error occurred while updating the language.");
                }
            });
        }
        else {
         alert('This feature is disable for demo!');
        }

    }

    function showErrorMessage(message, isSuccess = false) {
        let messageType = isSuccess ? 'alert-success' : 'alert-danger';
        let messageContainer = $(`
            <div class="alert ${messageType} alert-dismissible fade show" role="alert">
                ${message} 
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);
        $('body').prepend(messageContainer);
    }

    function setDefault(id) {
        if(user_verified == '1') {
            $.ajax({
                url: `{{url("/")}}/languages/${id}/set-default`,
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                success: function () {
                    location.reload();
                },
                error: function () {
                    showErrorMessage("Failed to set default language.");
                }
            });
        }
        else {
         alert('This feature is disable for demo!');
        }
    }

    function deleteLanguage(id) {
        if(user_verified == '1') {
            if (confirm('Are you sure?')) {
                $.ajax({
                    url: `{{url("/")}}/languages/${id}`,
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    success: function () {
                        location.reload();
                    },
                    error: function () {
                        showErrorMessage("Error deleting language.");
                    }
                });
            }
        }
        else {
         alert('This feature is disable for demo!');
        }
    }
</script>


@endpush
