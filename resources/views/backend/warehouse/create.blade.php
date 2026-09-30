@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<x-validation-error fieldName="name" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="#" data-toggle="modal" data-target="#addWarehouse" class="btn btn-info add-warehouse-btn"><i class="ti ti-plus"></i> {{__('db.Add Warehouse')}}</a>
        <a href="#" data-toggle="modal" data-target="#importWarehouse" class="btn btn-primary add-warehouse-btn"><i class="ti ti-copy"></i> {{__('db.Import Warehouse')}}</a>
    </div>
    <div class="table-responsive">
        <table id="warehouse-table" class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>{{__('db.Warehouse')}}</th>
                    <th>{{__('db.Phone Number')}}</th>
                    <th>{{__('db.email')}}</th>
                    <th>{{__('db.Address')}}</th>
                    <th>{{__('db.POS Type')}}</th>
                    <th>{{__('db.Number of Product')}}</th>
                    <th>{{__('db.Stock Quantity')}}</th>
                    <th>{{__('db.action')}}</th>
                </tr>
            </thead>
        </table>
    </div>
</section>

@include('backend.warehouse.add-warehouse')

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('warehouse.update', 1) }}" method="POST">
            @csrf
            @method('PUT')
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title"> {{__('db.Update Warehouse')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
          <div class="form-group">
            <input type="hidden" name="warehouse_id">
            <label>{{__('db.name')}} *</label>
            <input type="text" placeholder="{{ __('db.Type WareHouse Name') }}" name="name" required="required" class="form-control">
          </div>
          <div class="form-group">
            <label>{{__('db.Phone Number')}} *</label>
            <input type="text" name="phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label>{{__('db.email')}}</label>
            <input type="email" name="email" placeholder="example@example.com" class="form-control">
          </div>
          <div class="form-group">
            <label>{{__('db.Address')}} *</label>
            <textarea class="form-control" rows="3" name="address" required></textarea>
          </div>
          <div class="form-group">
              <label><strong>{{__('db.POS Type')}}</strong></label>
              <select name="pos_type" class="form-control selectpicker">
                  <option value="regular">{{__('db.Regular POS')}}</option>
                  <option value="restaurant">{{__('db.Restaurant POS')}}</option>
                  <option value="both">{{__('db.Regular + Restaurant')}}</option>
              </select>
          </div>
          <div class="form-group">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
          </div>
      </div>
      </form>
    </div>
  </div>
</div>

<div id="importWarehouse" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('warehouse.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Import Warehouse')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
         <p>{{__('db.The correct column order is')}} (name*, phone, email, address*) {{__('db.and you must follow this')}}.</p>
        <div class="row">
              <div class="col-md-6">
                  <div class="form-group">
                      <label>{{__('db.Upload CSV File')}} *</label>
                      <input type="file" name="file" class="form-control" required>
                  </div>
              </div>
              <div class="col-md-6">
                  <div class="form-group">
                      <label> {{__('db.Sample File')}}</label>
                      <a href="sample_file/sample_warehouse.csv" class="btn btn-info btn-block btn-md"><i class="ti ti-download"></i>  {{__('db.Download')}}</a>
                  </div>
              </div>
        </div>
        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
      </div>
      </form>
    </div>
  </div>
</div>

<!-- QR Modal -->
<div class="modal fade" id="qrModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @include('backend.qr-menu.includes')
        </div>
    </div>
</div>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #warehouse-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        numberOfWarehouse = <?php echo json_encode($numberOfWarehouse)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", gen_setting()->package_id)}}',
            success: function(data) {
                if(data['number_of_warehouse'] > 0 && data['number_of_warehouse'] <= numberOfWarehouse) {
                    $("a.add-warehouse-btn").addClass('d-none');
                }
            }
        });
    @endif

    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    $(document).ready(function() {

        $(document).on('click', '.open-EditWarehouseDialog', function() {
            var url = "warehouse/"
            var id = $(this).data('id').toString();
            url = url.concat(id).concat("/edit");

            $.get(url, function(data) {
                $("#editModal input[name='name']").val(data['name']);
                $("#editModal input[name='phone']").val(data['phone']);
                $("#editModal input[name='email']").val(data['email']);
                $("#editModal textarea[name='address']").val(data['address']);
                $("#editModal input[name='warehouse_id']").val(data['id']);
                $("#editModal select[name='pos_type']").val(data['pos_type'] || 'regular').selectpicker('refresh');
            });
        });

        // QR Generate
        $(document).on('click', '.btn-generate-qr', function() {
            const id = $(this).data('id');
            const type = $(this).data('type');

            $('#qrModal')
                .data('qr-target-id', id)
                .data('qr-target-type', type)
                .modal('show');

        });

        $('#qrModal').on('hidden.bs.modal', function() {
            $(this).removeData('qr-target-id').removeData('qr-target-type');
        });

        // QR View Modal
        $(document).on('click', '.btn-view-qr', function() {
            var id = $(this).data('id');
            let type = 'warehouse';
            $.get('{{url("qr/show") }}/' + id, function(data) {
                if(data.success) {
                    $('#qr-image').attr('src', data.image_url);
                    $('#qr-url').val(data.redirect);
                    $('#qrModal').modal('show');
                }
            });
        });
    });

    $('#warehouse-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('warehouse.data') }}"
        },
    
        columns: [
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function () {
                    return '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                }
            },
            {data: 'name', name: 'name'},
            {data: 'phone', name: 'phone'},
            {data: 'email', name: 'email'},
            {data: 'address', name: 'address'},
            {data: 'pos_type', name: 'pos_type'},
            {data: 'number_of_product', name: 'number_of_product'},
            {data: 'stock_qty', name: 'stock_qty'},
            {
                data: 'action',
                orderable: false,
                searchable: false
            }
        ],
    
        rowCallback: function(row, data) {
            $(row).attr('data-id', data.id);
        },
    
        order: [],
    
        language: {
            lengthMenu: '_MENU_ {{ __("db.records per page") }}',
            info: '<small>{{ __("db.Showing") }} _START_ - _END_ (_TOTAL_)</small>',
            search: '{{ __("db.Search") }}',
            paginate: {
                previous: '<i class="ti ti-chevron-left"></i>',
                next: '<i class="ti ti-chevron-right"></i>'
            }
        },
    
        columnDefs: [
            {
                orderable: false,
                targets: [0, 7]
            }
        ],
    
        select: {
            style: 'multi',
            selector: 'td:first-child'
        },
    
        lengthMenu: [
            [10,25,50,100],
            [10,25,50,100]
        ],
    
        dom: '<"row"lfB>rtip',
    
        buttons: [
            // keep your existing export buttons here
        ]
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $( "#select_all" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("tbody input[type='checkbox']").prop('checked', true);
        }
        else {
            $("tbody input[type='checkbox']").prop('checked', false);
        }
    });

    $("#export").on("click", function(e){
        e.preventDefault();
        var warehouse = [];
        $(':checkbox:checked').each(function(i){
        warehouse[i] = $(this).val();
        });
        $.ajax({
        type:'POST',
        url:'/exportwarehouse',
        data:{

                warehouseArray: warehouse
            },
        success:function(data){
            alert('Exported to CSV file successfully! Click Ok to download file');
            window.location.href = data;
        }
        });
    });
</script>
@endpush
