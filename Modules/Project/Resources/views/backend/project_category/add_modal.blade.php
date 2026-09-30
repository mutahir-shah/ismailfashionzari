<!-- add project category modal -->
<div id="addProjectCategoryModal" tabindex="-1" role="dialog" aria-labelledby="projectCategoryModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('project_categories.store') }}" method="post" id="project-category-form">
                @csrf
                <div class="modal-header">
                    <h5 id="projectCategoryModalLabel" class="modal-title">{{__('db.Add Category')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <div class="form-group">
                        <label>{{__('db.name')}} *</label>
                        <input type="text" name="name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="is_active" value="1">
                        <button type="button" class="btn btn-primary project-category-submit-btn">{{__('db.submit')}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.project-category-submit-btn').off('click').on("click", function() {
            var form = $("#project-category-form");

            $.ajax({
                type:'POST',
                url:'{{route("project_categories.store")}}',
                data: form.serialize(),
                success:function(response) {
                    if(response.id) {
                        var key = response.id;
                        var value = response.name;
                        
                        var targetSelects = ['project_category_id', 'edit_project_category_id'];
                        targetSelects.forEach(function(nameAttr) {
                            var $select = $('select[name="'+nameAttr+'"]');
                            if($select.length) {
                                $select.append('<option value="'+ key +'">'+ value +'</option>');
                                $select.val(key).trigger('change');
                            }
                        });
                        
                        $('.selectpicker').selectpicker('refresh');
                        $("#addProjectCategoryModal").modal('hide');
                        $("#project-category-form")[0].reset();
                    } else if(response.errors) {
                        alert(response.errors.join('\n'));
                    } else {
                        alert('Something went wrong!');
                    }
                },
                error:function(xhr) {
                    var msg = 'Error adding project category. Make sure all required fields are filled correctly.';
                    if (xhr.responseText) {
                        try {
                            var err = JSON.parse(xhr.responseText);
                            if (err.errors) {
                                msg = Object.values(err.errors).flat().join('\n');
                            } else if (err.message) {
                                msg = err.message;
                            }
                        } catch(e) {}
                    }
                    alert(msg);
                }
            });
        });
    });
</script>
@endpush
