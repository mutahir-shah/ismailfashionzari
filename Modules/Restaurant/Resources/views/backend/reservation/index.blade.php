@push('css')
    @include('backend.layout.partials.datatable_css')
<style>
.ui-timepicker-container{position:absolute;overflow:hidden;box-sizing:border-box;}.ui-timepicker,.ui-timepicker-viewport{box-sizing:content-box;height:205px;display:block;margin:0}.ui-timepicker{list-style:none;padding:0 1px;text-align:center}.ui-timepicker-viewport{padding:0;overflow:auto;overflow-x:hidden}.ui-timepicker-standard{font-family:Verdana,Arial,sans-serif;font-size:1.1em;background-color:#FFF;border:1px solid #AAA;color:#222;margin:0;padding:2px}.ui-timepicker-standard a{border:1px solid transparent;color:#222;display:block;padding:.2em .4em;text-decoration:none}.ui-timepicker-standard .ui-state-hover{background-color:#DADADA;border:1px solid #999;font-weight:400;color:#212121}.ui-timepicker-standard .ui-menu-item{margin:0;padding:0}.ui-timepicker-corners,.ui-timepicker-corners .ui-corner-all{-moz-border-radius:4px;-webkit-border-radius:4px;border-radius:4px}.ui-timepicker-hidden{display:none}.ui-timepicker-no-scrollbar .ui-timepicker{border:none}/*# sourceMappingURL=jquery.timepicker.min.css.map */
</style>
@endpush

@extends('backend.layout.main') @section('content')
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div> 
@endif
<section>
    <div class="container-fluid"> 
        <div class="row">
            <div class="col-md-12">

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible text-center mar-bot-30"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <ul>
                        @foreach($errors->all() as $error)
                        <li>{{ $error}}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(session()->has('message'))
                  <div class="alert alert-{{session('type')}} alert-dismissible text-center mar-bot-30"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session('message') }}</div> 
                @endif
                <button class="btn btn-primary" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">{{ __('db.Add Reservation') }}</button>
                <div class="collapse mt-3" id="collapseExample">
                    <div class="card">
                        <div class="card-body">
                            <form action="" method="post" class="form-signin" enctype='multipart/form-data'>
                                @csrf
                                <div class="row">
                                    <div class="col-sm-4 mb-3">
                                        <label>{{ __('db.date') }} *</label><br>
                                        <input class="form-control date" type="text" required  id="date" name="date">
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label>{{ __('db.Time') }} *</label><br>
                                        <input class="form-control time" type="text" required id="time" name="time">
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label>{{ __('db.Number of Person') }} *</label><br>
                                        <input class="form-control" type="text" required id="person" name="person">
                                    </div>
                                </div>
                                <div class="row reservation-details d-none">
                                    <div class="col-sm-3 mb-3">
                                        <label>{{ __('db.name') }} *</label><br>
                                        <input class="form-control" type="text" required id="name" name="name">
                                    </div>
                                    <div class="col-sm-3 mb-3">
                                        <label>{{ __('db.Phone') }} *</label><br>
                                        <input class="form-control" type="text" required id="phone" name="phone">
                                    </div>
                                    <div class="col-sm-3 mb-3">
                                        <label>{{ __('db.email') }}</label><br>
                                        <input class="form-control" type="text" id="email" name="email">
                                    </div>
                                    <div class="col-sm-3 mb-3">
                                        <label>{{ __('db.table') }}</label><br>
                                        <select id="table" class="form-control selectpicker" name="table_id">
                                        </select>
                                    </div>
                                </div>
                                <div class="row"> 
                                    <div class="col-sm-12 mt-2">
                                        <input type="hidden" name="reservationid" value=""/>
                                        <button class="btn btn-warning mt-1" id="check_availability">{{ __('db.Check Availability') }}</button>
                                        <button class="btn btn-success mt-1 d-none" id="book" type="submit">{{ __('db.Book Table') }}</button>
                                        <button class="btn btn-success mt-1 d-none" id="edit" type="submit">{{ __('db.Save Reservation') }}</button>
                                    </div>                                    
                                </div>                               
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>  

    @if(!empty($reservations))
    <div class="table-responsive">
        <table id="reservation_table" class="table " style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.customer')}}</th>
                    <th>{{__('db.date')}}</th>
                    <th>{{__('db.Time')}}</th>
                    <th>{{__('db.table')}}</th>
                    <th>{{__('db.Number of Person')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($reservations as $reservation)
                <tr>
                    <td class="not-exported"></td>
                    <td>
                        {{ $reservation->name }}<br>
                        {{ $reservation->phone }}<br>
                        {{ $reservation->email ?? '' }}
                    </td>
                    <td>{{ $reservation->date }}</td>
                    <td>{{ $reservation->time }}</td>
                    <td>{{ $reservation->table }}</td>
                    <td>{{ $reservation->person }}</td>
                    <td class="not-exported">
                        <a data-id="{{$reservation->id}}" class="btn btn-primary btn-sm open-EditDialog" href="#">
                            <i class="ti ti-pencil"></i>
                        </a>&nbsp;&nbsp;
                        <a href="{{ url('restaurant/reservation/delete/') }}/{{ $reservation->id }}" onclick="return confirmDelete()" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</section>

<div id="editModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-name">{{__('db.Edit Reservation')}}</h5>
                <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><span
                            aria-hidden="true">×</span></button>
            </div>

            <div class="modal-body">
                <form id="edit-link-form" method="post" action="{{route('restaurant.reservation.update')}}" class="form-horizontal">

                    @csrf
                    <div class="row">

                        <div class="col-sm-3">
                            <label>{{ __('db.name') }} *</label><br>
                            <input id="name" class="form-control" required type="text" name="name">
                        </div>
                        <div class="col-sm-3 mb-3">
                            <label>{{ __('db.Phone') }} *</label><br>
                            <input class="form-control" type="text" required name="phone">
                        </div>
                        <div class="col-sm-3 mb-3">
                            <label>{{ __('db.email') }}</label><br>
                            <input class="form-control" type="text" name="email">
                        </div>
                        <div class="col-sm-3 mb-3">
                            <label>{{ __('db.table') }}</label><br>
                            <select id="table" class="form-control selectpicker" name="table_id">
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label></label><br>
                                <input type="hidden" name="reservationid" value=""/>
                                <input type="submit" name="action_button" id="edit-page" class="btn btn-success"
                                        value="{{__('db.Save')}}">
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script>
{!! file_get_contents(Module::find('Restaurant')->getPath(). "/assets/js/jquery.timepicker.min.js") !!}
</script>
<script type="text/javascript">
    "use strict";

    $('.time').timepicker({
        timeFormat: 'h:mm p',
        interval: 30,
        minTime: '10',
        maxTime: '10:00pm',
        defaultTime: '11',
        startTime: '10:00',
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    function confirmDelete() {
      if (confirm("Are you sure want to delete?")) {
          return true;
      }
      return false;
    }

    $('#reservation_table').DataTable( {
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
            "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 1, 2]
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
                text: '<i name="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                },
                customize: function(doc) {
                    for (var i = 1; i < doc.content[1].table.body.length; i++) {
                        if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                            var imagehtml = doc.content[1].table.body[i][0].text;
                            var regex = /<img.*?src=['"](.*?)['"]/;
                            var src = regex.exec(imagehtml)[1];
                            var tempImage = new Image();
                            tempImage.src = src;
                            var canvas = document.createElement("canvas");
                            canvas.width = tempImage.width;
                            canvas.height = tempImage.height;
                            var ctx = canvas.getContext("2d");
                            ctx.drawImage(tempImage, 0, 0);
                            var imagedata = canvas.toDataURL("image/png");
                            delete doc.content[1].table.body[i][0].text;
                            doc.content[1].table.body[i][0].image = imagedata;
                            doc.content[1].table.body[i][0].fit = [30, 30];
                        }
                    }
                },
            },
            {
                extend: 'csv',
                text: '<i name="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                data = regex.exec(data)[1];                 
                            }
                            return data;
                        }
                    }
                },
            },
            {
                extend: 'print',
                text: '<i name="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                },
            },
            {
                extend: 'colvis',
                text: '<i name="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    });

    $(document).ready(function() {

        $('#book').on('click', function(e) {
            e.preventDefault();

            var form = $(this).closest('form');
            var isValid = true;

            // Remove previous validation messages
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').remove();

            // Check required fields
            form.find('[required]').each(function () {
                var field = $(this);
                var value = field.val();

                // Handle empty values
                if (!value || value.trim() === '') {
                    isValid = false;

                    field.addClass('is-invalid');

                    field.after(
                        '<div class="invalid-feedback d-block">This field is required.</div>'
                    );
                }
            });

            // Stop submission if invalid
            if (!isValid) {
                return false;
            }

            form.attr('action', "{{ route('restaurant.reservation.store') }}");
            form.submit();
        });

        $('#edit').on('click', function(e) {
            e.preventDefault();
            var form = $(this).closest('form');

            var isValid = true;

            // Remove previous validation messages
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').remove();

            // Check required fields
            form.find('[required]').each(function () {
                var field = $(this);
                var value = field.val();

                // Handle empty values
                if (!value || value.trim() === '') {
                    isValid = false;

                    field.addClass('is-invalid');

                    field.after(
                        '<div class="invalid-feedback d-block">This field is required.</div>'
                    );
                }
            });

            // Stop submission if invalid
            if (!isValid) {
                return false;
            }

            form.attr('action', "{{ route('restaurant.reservation.update') }}");
            form.submit();
        });

        $('.open-EditDialog').on('click', function() {
            var url = "reservation/edit/"
            var id = $(this).data('id').toString();
            url = url.concat(id);
            $("input[name='reservationid']").val($(this).data('id'));
            $.get(url, function(data) {
                $("#name").val(data['name']); 
                $("#phone").val(data['phone']); 
                $("#email").val(data['email']);  
                $("#date").val(data['date']); 
                $("#time").val(data['time']);   
                $("#person").val(data['person']);  
                $('#collapseExample').addClass('show');    
                checkAvailability('edit');    
            });
        });
    });

    $('#check_availability').on('click', function(e) {
        e.preventDefault();
        checkAvailability('add');
    });

    function checkAvailability(type) {
        var date = $("input[name=date]").val();

        var time = $("input[name=time]").val();

        var person = $("input[name=person]").val();

        $.ajax({
            type:'POST',
            url:"{{ route('restaurant.reservation.check') }}",
            data:{date:date, time:time, person:person, type:type},
            success:function(data){
                if(data.success == true){
                    $('.reservation-details').removeClass('d-none');
                    if(type == 'edit') {
                        $('#edit').removeClass('d-none');
                    } else {
                        $('#book').removeClass('d-none');
                    }
                    const select = $('#table');
                    data.tables.forEach(item => {
                        const optionText = `${item.table} at ${item.name} ( 👤 ${item.number_of_person} )`;
                        const option = $('<option></option>') // Create an <option> element
                            .val(item.id) // Set the value attribute to 'id'
                            .text(optionText); // Set the text content

                        select.append(option); // Append the <option> to the select element
                        select.selectpicker('refresh');
                    });
                }else if(data.success == false){
                    $('.reservation-details, #book, #edit').addClass('d-none');
                    $('.collapse').append('<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+data.message+'</div>');
                }
            }
        });
    }

    $(document).ready(function() {
        $('.open-TableDialog').on('click', function() {
            var id = $(this).data('id').toString();
            $("input[name='reservation_id']").val($(this).data('id'));

        });
    });
</script>
@endpush
