@extends('backend.layout.main')
@push('css')

@endpush
@section('content')

    @include('includes.session_message')
    
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Translations</h2>
            <a href="{{ route('languages') }}" class="btn btn-primary">Manage Languages</a>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Select Language:</label>
                <select id="localeName" class="form-select border border-secondary" onchange="fetchTranslations()">
                    @foreach ($languages as $language)
                        <?php 
                            $default = $language->is_default;
                        ?>
                        <option value="{{ $language->language }}" @selected($default)>{{ $language->name }}</option>
                    @endforeach
                </select>
                
            </div>
        </div>


        <div class="row mb-3 d-flex align-items-end">
            <div class="col-md-5">
                <label for="key" class="form-label">Key (English)</label>
                <input type="text" id="key" class="form-control" placeholder="Key">
            </div>
            <div class="col-md-5">
                <label for="value" class="form-label">Value (Translation)</label>
                <input type="text" id="value" class="form-control" placeholder="Value">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" onclick="addTranslation()">Add</button>
            </div>
        </div>

        
        <!-- Search Input -->
        <div class="mb-3">
            <input type="text" class="form-control" id="searchInput" placeholder="Search by key or value">
        </div>

        <!-- Update Modal -->
        <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">Update Translation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="update_id">
                        <div class="mb-3">
                            <label for="update_key" class="form-label">Key</label>
                            <input type="text" class="form-control" id="update_key">
                        </div>
                        <div class="mb-3">
                            <label for="update_value" class="form-label">Value</label>
                            <input type="text" class="form-control" id="update_value">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="updateTranslation()">Save changes</button>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-secondary">
                <tr>
                    <th class="col-3">Key</th>
                    <th class="col-7">Value</th>
                    <th class="col-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="translation_list">
                @foreach ($translations as $translation)
                    <tr>
                        <td>{{$translation->key}}</td>
                        <td>{{$translation->value}}</td>
                        <td class="text-center align-middle">
                            <button class="btn btn-warning btn-sm" onclick="showUpdateModal('{{$translation->id}}', '{{$translation->key}}', '{{$translation->value}}')">Update</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteTranslation('{{$translation->id}}')">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>


@endsection

@push('scripts')

<script>
    function fetchTranslations() {
        let localeName = $('#localeName').val();
        $.get(`{{url("/")}}/translations/${localeName}`, function(data) {
            let list = $('#translation_list');
            list.empty();
            data.forEach(tr => {
                list.append(`
                    <tr>
                        <td>${tr.key}</td>
                        <td>${tr.value}</td>
                        <td class="text-center align-middle">
                            <button class="btn btn-warning btn-sm" onclick="showUpdateModal('${tr.id}', '${tr.key}', '${tr.value}')">Update</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteTranslation('${tr.id}')">Delete</button>
                        </td>
                    </tr>
                `);
            });
        });
    }

    function addTranslation() {
        let localeName = $('#localeName').val();
        let key = $('#key').val();
        let value = $('#value').val();

        $.ajax({
            url: '{{url("/")}}/translations',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            contentType: 'application/json',
            data: JSON.stringify({ locale: localeName, key, value }),
            success: function() {
                $('#key').val('');
                $('#value').val('');
                fetchTranslations();
            }
        });
    }

    function showUpdateModal(id, key, value) {
        $('#update_id').val(id);
        $('#update_key').val(key);
        $('#update_value').val(value);
        $('#updateModal').modal('show');
    }

    function updateTranslation() {
        const update_id = $('#update_id').val();
        const key = $('#update_key').val();
        const value = $('#update_value').val();

        $.ajax({
            url: `{{url("/")}}/translations/${update_id}`,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            contentType: 'application/json',
            data: JSON.stringify({ key, value }),
            success: function() {
                $('#updateModal').modal('hide');
                fetchTranslations();
            }
        });
    }

    function deleteTranslation(id) {
        let proceed = confirm('Are you sure?');
        if (proceed) {
            $.ajax({
                url: `{{url("/")}}/translations/${id}`,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function() {
                    fetchTranslations();
                }
            });
        }
    }

    $('#searchInput').on('keyup', function() {
        let filter = $(this).val().toLowerCase();
        $('#translation_list tr').each(function() {
            let key = $(this).find('td').eq(0).text().toLowerCase();
            let value = $(this).find('td').eq(1).text().toLowerCase();
            $(this).toggle(key.includes(filter) || value.includes(filter));
        });
    });

    $(document).ready(function() {
        fetchTranslations();
    });
</script>


@endpush