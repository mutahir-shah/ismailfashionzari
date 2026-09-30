@extends('backend.layout.main') @section('content')

<div class="container-fluid mt-5">

   <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="#qr-code" role="tab"
                data-toggle="tab">{{ __('db.QR Code') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#settings" role="tab"
                data-toggle="tab">{{ __('db.catalogue settings') }}</a>
        </li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade show active" id="qr-code">

            @include('backend.qr-menu.includes')

        </div>
        <div role="tabpanel" class="tab-pane fade" id="settings">
            <div class="card p-4 mb-4">
                <label>{{ __('db.out_of_stock_products') }}</label>
                <div>
                    <label><input type="radio" name="show_stock_out_product" value="1" {{ $qr_catelog_setting->show_stock_out_product == 1 ? 'checked' : '' }}> {{ __('db.show') }}</label>
                    <label class="ml-3"><input type="radio" name="show_stock_out_product" value="0" {{ $qr_catelog_setting->show_stock_out_product == 0 ? 'checked' : '' }}> {{ __('db.hide') }}</label>
                </div>

                <button class="btn btn-success mt-3" onclick="saveSettings()">{{ __('db.Save') }}</button>

                <script>
                    function saveSettings() {
                        let stock_status = $('input[name="show_stock_out_product"]:checked').val();
                        $.ajax({
                            url: "{{ route('qr.saveSettings') }}",
                            method: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                show_stock_out_product: stock_status
                            },
                            success: function(res) {
                                if (res.success) {
                                    alert(res.message);
                                }
                            }
                        });
                    }
                </script>

                {{-- <!-- WHATSAPP -->
                <div class="card p-4">

                    <div class="form-check mb-2">
                        <input type="checkbox" id="enable_whatsapp" checked>
                        <label>{{ __('db.enable_whatsapp_ordering') }}</label>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.WhatsApp Number') }}</label>
                        <input type="text" id="whatsapp_number" class="form-control" placeholder="e.g. +1234567890">
                    </div>

                    <button class="btn btn-success mt-2" onclick="saveWhatsapp()">{{ __('db.Save') }}</button>
                </div> --}}
            </div>
        </div>
    </div>

</div>


@endsection
