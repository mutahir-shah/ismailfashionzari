@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <!-- Trigger the modal with a button -->
         @can('categories-add')
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#category-modal"><i class="ti ti-plus"></i> {{__("db.Add Category")}}</button>&nbsp;
        @endcan
        @can('categories-import')
            <button class="btn btn-primary" data-toggle="modal" data-target="#importCategory"><i class="ti ti-copy"></i> {{__('db.Import Category')}}</button>
        @endcan
    </div>
    <div class="table-responsive">
        <table id="category-table" class="table" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.category')}}</th>
                    <th>{{__('db.Parent Category')}}</th>
                    <th>{{__('db.Number of Product')}}</th>
                    <th>{{__('db.Stock Quantity')}}</th>
                    <th>{{__('db.Stock Worth') . '(' . __('db.Price') . '/' . __('db.Cost') . ')'}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
        </table>
    </div>
</section>

<!-- Edit Modal -->
<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="" method="POST" enctype="multipart/form-data" id="editCategoryForm">
            @csrf
            @method('PUT')
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Update Category')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>{{__('db.name')}} *</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-control" placeholder="{{ __('db.Type category name') }}">
                <x-validation-error fieldName="name" />
            </div>
            <input type="hidden" name="category_id">
            <div class="col-md-6 form-group">
                <label>{{__('db.Image')}}</label>
                <input type="file" name="image" class="form-control">
                <x-validation-error fieldName="image" />
            </div>
            <div class="col-md-6 form-group">
                <label>{{__('db.Parent Category')}}</label>
                <select name="parent_id" class="form-control selectpicker" id="parent">
                    <option value="">No {{__('db.parent')}}</option>
                    @foreach (get_active_categories() as $category)
                    <option value="{{$category->id}}">{{$category->name}}</option>
                    @endforeach
                </select>
                <x-validation-error fieldName="parent_id" />
            </div>
            @if (\Schema::hasColumn('categories', 'woocommerce_category_id'))
            <div class="col-md-6 form-group mt-4">
                <h5><input name="is_sync_disable" type="checkbox" id="is_sync_disable" value="1">&nbsp; {{__('db.Disable Woocommerce Sync')}}</h5>
                <x-validation-error fieldName="is_sync_disable" />
            </div>
            @endif
            @if(in_array('restaurant',explode(',',gen_setting()->modules)))
            <div class="col-md-12 mt-3">
                <h6><strong>{{ __('For Website') }}</strong></h6>
                <hr>
            </div>
            <div class="col-md-12 form-group">
                <br>
                <input type="checkbox" name="featured" id="featured" value="1"> <label>{{ __('List on website') }}</label>
            </div>
            @endif
            @if(in_array('ecommerce',explode(',',gen_setting()->modules)))
            <div class="col-md-12 mt-3">
                <h6><strong>{{ __('For Website') }}</strong></h6>
                <hr>
            </div>

            <div class="col-md-6 form-group">
                <label>{{ __('Icon') }}</label>
                <input type="file" name="icon" class="form-control">
            </div> 
            <div class="col-md-6 form-group">
                <br>
                <input type="checkbox" name="featured" id="featured" value="1"> <label>{{ __('List on category dropdown') }}</label>
            </div>
            @endif
        </div>
        @if(in_array('ecommerce',explode(',',gen_setting()->modules)))
        <div class="row">
            <div class="col-md-12 mt-3">
                <h6><strong>{{ __('For SEO') }}</strong></h6>
                <hr>
            </div>
            <div class="col-md-12 form-group">
                <label>{{ __('Meta Title') }}</label>
                <input type="text" name="page_title" value="{{ old('page_title') }}" class="form-control" placeholder="{{ __('db.Meta Title') }}">
            </div>
            <div class="col-md-12 form-group">
                <label>{{ __('Meta Description') }}</label>
                <input type="text" name="short_description" value="{{ old('short_description') }}" class="form-control" placeholder="{{ __('db.Meta Description') }}">
            </div>
        </div>
        @endif

        <div class="form-group">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Import Modal -->
<div id="importCategory" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('category.import') }}" method="POST" enctype="multipart/form-data">
          @csrf
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">{{__('db.Import Category')}}</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
        </div>
        <div class="modal-body">
            <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
           <p>{{__('db.The correct column order is')}} (name*, parent_category) {{__('db.and you must follow this')}}.</p>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{__('db.Upload CSV File')}} *</label>
                        <input type="file" name="file" class="form-control" required>
                        <x-validation-error fieldName="file" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label> {{__('db.Sample File')}}</label>
                        <a href="sample_file/sample_category.csv" class="btn btn-info btn-block btn-md"><i class="ti ti-download"></i>  {{__('db.Download')}}</a>
                    </div>
                </div>
            </div>
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
        </div>
        </form>
      </div>
    </div>
</div>


@endsection
@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    function confirmDelete() {
      if (confirm("If you delete category all products under this category will also be deleted. Are you sure want to delete?")) {
          return true;
      }
      return false;
    }

    var category_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(document).on("click", ".open-EditCategoryDialog", function(){
        $("#editModal input[name='is_sync_disable']").prop("checked", false);
        $("#editModal input[name='featured']").prop("checked", false);
        var url ="category/";
        var id = $(this).data('id').toString();
        url = url.concat(id).concat("/edit");
        $.get(url, function(data){
            $("#editModal input[name='name']").val(data['name']);
            $("#editModal select[name='parent_id']").val(data['parent_id']);
            $("#editModal input[name='category_id']").val(data['id']);

            var updateUrl = "category/" + data['id'];
            $("#editCategoryForm").attr('action', updateUrl);

            if (data['is_sync_disable']) {
                $("#editModal input[name='is_sync_disable']").prop("checked", true);
            }
            if (data['featured']) {
                $("#editModal input[name='featured']").prop("checked", true);
            }
            $("#editModal input[name='page_title']").val(data['page_title']);
            $("#editModal input[name='short_description']").val(data['short_description']);
            $('.selectpicker').selectpicker('refresh');
        });
    });

    $('#category-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"category/category-data",
            dataType: "json",
            type:"post"
        },
        "createdRow": function( row, data, dataIndex ) {
            $(row).attr('data-id', data['id']);
        },
        "columns": [
            {"data": "key"},
            {"data": "name"},
            {"data": "parent_id"},
            {"data": "number_of_product"},
            {"data": "stock_qty"},
            {"data": "stock_worth"},
            {"data": "options"},
        ],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        order:[['2', 'asc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 1, 3, 4, 5, 6]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],

        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                footer:true
            },
            {
                text: '<i title="delete" class="ti ti-x"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        category_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                category_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(category_id.length && confirm("If you delete category all products under this category will also be deleted. Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'category/deletebyselection',
                                data:{
                                    categoryIdArray: category_id
                                },
                                success:function(data){
                                    dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                }
                            });
                        }
                        else if(!category_id.length)
                            alert('No category is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );

</script>
@endpush
