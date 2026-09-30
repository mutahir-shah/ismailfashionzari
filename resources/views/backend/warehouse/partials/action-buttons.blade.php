<div class="btn-group">
    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
        <li>
            <button type="button" data-id="{{$warehouse->id}}" class="open-EditWarehouseDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="ti ti-edit"></i> {{__('db.edit')}}
        </button>
        </li>
        @if(\Auth::user()->role_id <= 2)
            @if($warehouse->qr_code_id)
                <li>
                    <button type="button" data-id="{{$warehouse->qr_code_id}}" class="btn btn-link btn-view-qr">
                        <i class="fa fa-qrcode"></i> View QR</button>
                </li>
                <li>
                    <a href="{{ url('qr/download/'.$warehouse->qr_code_id) }}" class="btn btn-link"><i class="ti ti-download"></i> Download QR</a>
                </li>
            @else
                <li>
                    <button type="button" data-id="{{$warehouse->id}}" data-type="warehouse" class="btn btn-link btn-generate-qr"><i class="fa fa-qrcode"></i> Generate QR</button>
                </li>
            @endif
        @endif
        <li class="divider"></li>
        <form action="{{ route('warehouse.destroy', $warehouse->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <li>
                <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> {{__('db.delete')}}</button>
            </li>
        </form>
    </ul>
</div>