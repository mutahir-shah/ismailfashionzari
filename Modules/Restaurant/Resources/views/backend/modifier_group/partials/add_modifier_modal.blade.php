<div id="addModifierModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-plus"></i> {{ __('db.Add Modifier') }}</h5>
                <button type="button" data-dismiss="modal" class="close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="add-modifier-form">
                    @csrf
                    <input type="hidden" name="modifier_group_id" id="add_modifier_group_id">
                    <div class="form-group">
                        <label>{{ __('db.Modifier Name') }} <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="name" required autofocus>
                        <small class="text-muted">{{ __('db.Pricing is set per-product when you assign this group to products.') }}</small>
                    </div>
                    <div class="form-group">
                        <label>{{ __('db.Sort Order') }}</label>
                        <input class="form-control" type="number" name="sort_order" value="0" min="0" style="width:120px">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('db.Cancel') }}</button>
                <button type="button" id="submit-add-modifier" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> {{ __('db.Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#submit-add-modifier').on('click', function() {
            var form = $('#add-modifier-form');
            var groupId = $('#add_modifier_group_id').val();
            var url = "{{ url('restaurant/modifier-group') }}/" + groupId + "/modifiers/store-ajax";
            
            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize(),
                success: function(response) {
                    $('#addModifierModal').modal('hide');
                    if (typeof modifiersTable !== 'undefined') {
                        // Reload data table if we're on the modifiers list page and using AJAX Datatables
                        modifiersTable.ajax.reload();
                    } else {
                        // Otherwise, just reload the page
                        location.reload();
                    }
                },
                error: function(response) {
                    alert('Error saving modifier.');
                }
            });
        });
    });
</script>
@endpush
