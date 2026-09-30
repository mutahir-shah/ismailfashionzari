<div class="card p-4 mb-4">
    <h4 class="card-title">{{ __('db.Catalogue QR') }}</h4>
    <hr>
    <div class="row"> 
        <!-- QR Code Generation Form -->        
        <div class="col-md-6">

            @if(in_array('restaurant',explode(',',gen_setting()->modules)) && isset($tables))
            <div class="form-group mb-3">
                <label>{{ __('db.table') }}</label>
                <select id="table_id" class="form-control">
                    @foreach($tables as $table)
                        <option value="{{ $table->id }}">{{ $table->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="form-group mb-3">
                <label>{{ __('db.Warehouse') }}</label>
                <select id="location_id" class="form-control">
                    @foreach($lims_warehouse_list as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="form-group mb-3">
                <label>{{ __('db.Title') }}</label>
                <input type="text" id="qr_title" class="form-control" value="My Shop Name">
            </div>

            <div class="form-group mb-3">
                <label>{{ __('db.Subtitle') }}</label>
                <input type="text" id="qr_subtitle" class="form-control" value="Product Catalogue">
            </div>

            <div class="form-group mb-3">
                <label>{{ __('db.QR Code Color') }}</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" style="padding:0;">
                            <input type="color" class="hex-color-picker" id="theme_color_picker" value="#374b5e" data-target-input="theme_color" style="width: 42px; height: 38px; border: none;" />
                        </span>
                    </div>
                    <input type="text" id="qr_color" name="theme_color" value="#374b5e" required class="form-control hex-color-input" placeholder="#374b5e" data-picker-id="theme_color_picker" />
                </div>
                <small class="text-muted">Use HEX like <code>#6366F1</code></small>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" id="show_logo" class="form-check-input" checked>
                <label class="form-check-label">{{ __('db.Show Logo') }}</label>
            </div>

            <button class="btn btn-primary generateQR" onclick="generateQR()">{{ __('db.Generate QR Code') }}</button>
        </div>
        <div class="col-md-6">
            <div class="card p-5 text-center">
                <h4 id="preview_title">My Shop Name</h4>
                <p id="preview_subtitle">Product Catalogue</p>

                <div id="qrcode" class="mb-3 mt-3">
                    <div style="margin: 0 auto;width: 200px;height: 200px;background: #fff;padding: 10px;border: 1px solid #ccc; border-radius:15px">
                        <div style="opacity:0.3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-qrcode"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M7 17l0 .01" /><path d="M14 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M7 7l0 .01" /><path d="M4 15a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1l0 -4" /><path d="M17 7l0 .01" /><path d="M14 14l3 0" /><path d="M20 14l0 .01" /><path d="M14 14l0 3" /><path d="M14 20l3 0" /><path d="M17 17l3 0" /><path d="M20 17l0 3" /></svg>
                        </div>
                    </div>
                </div>

                <div class="form-group d-none mt-3" id="qr_actions">
                    <div class="input-group">
                        <input type="text" id="qr_link" class="form-control text-center" readonly>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" onclick="copyQrUrl()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
                            </button>
                        </div>
                        <div class="input-group-append">
                            <a id="qr_open" href="#" target="_blank" class="btn btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-external-link"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-center">
                    <button class="btn btn-success mt-3 mx-1 d-flex align-items-center" onclick="downloadQR()" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                        <span class="ml-1">{{ __('db.Download QR') }}</span>
                    </button>
                    <button class="btn btn-primary mt-3 mx-1 d-flex align-items-center" onclick="printQR()" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-printer"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><path d="M7 15a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2l0 -4" /></svg>
                        <span class="ml-1">{{ __('db.print') }}</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- End of QR Code Generation Form -->
    </div>
</div>

@push('scripts')
<script>

function generateQR(type = null, id = null) {
    const modal = $('#qrModal');
    const rowType = type || modal.data('qr-target-type');
    const rowId = id || modal.data('qr-target-id');
    const hasExplicitTarget = rowType && rowId;

    @if(in_array('restaurant',explode(',',gen_setting()->modules)) && isset($tables))
        type = hasExplicitTarget ? rowType : 'table';
        id = hasExplicitTarget ? rowId : $('#table_id').val();
    @else
        type = hasExplicitTarget ? rowType : 'warehouse';
        id = hasExplicitTarget ? rowId : $('#location_id').val();
    @endif

    let color = $('#qr_color').val();
    let title = $('#qr_title').val();
    let subtitle = $('#qr_subtitle').val();
    let showLogo = $('#show_logo').is(':checked') ? 1 : 0;

    // UI preview
    $('#preview_title').text(title);
    $('#preview_subtitle').text(subtitle);

    let btn = $('.generateQR');
    btn.text('Generating...').prop('disabled', true);

    $.ajax({
        url: `{{url('/')}}/qr/generate/${type}/${id}`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            color: color,
            show_logo: showLogo
        },
        success: function(res) {

            if (res.success) {

                // Show QR
                $('#qrcode').html(`
                    <img src="${res.qr_url}?t=${new Date().getTime()}" 
                         style="width:220px;height:220px;">
                `);

                // Show link
                $('#qr_link').val(res.url);
                $('#qr_open').attr('href', res.url);
                $('#qr_actions').removeClass('d-none');
                // Enable download
                $('.btn-success')
                    .prop('disabled', false)
                    .attr('data-url', res.qr_url);

            } else {
                alert('Failed to generate QR');
            }

        },
        error: function() {
            alert('Server error');
        },
        complete: function() {
            btn.text('Generate QR Code').prop('disabled', false);
        }
    });
}

// Download QR
function downloadQR() {
    let url = $('.btn-success').attr('data-url');
    if (!url) return alert('Generate QR first');

    let link = document.createElement('a');
    link.href = url;
    link.download = 'qr.png';
    link.click();
}

function copyQrUrl() {
    var copyText = document.getElementById("qr_link");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Copied: " + copyText.value);
}

function printQR() {

    // Clone the card
    let qrImg = document.querySelector('#qrcode img')?.src;

    let content = `
        <h3>${$('#preview_title').text()}</h3>
        <p>${$('#preview_subtitle').text()}</p>
        <img src="${qrImg}" />
    `;

    let printWindow = window.open('', '', 'width=600,height=700');

    printWindow.document.write(`
        <html>
        <head>
            <title>Print QR</title>
            <style>
                body {
                    text-align: center;
                    font-family: Arial, sans-serif;
                    padding: 20px;
                }
                h4 { margin-bottom: 5px; }
                p { margin-top: 0; color: #555; }
                img {
                    width: 220px;
                    height: 220px;
                }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);

    printWindow.document.close();

    // Wait for content load
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
}

// Sync UI inputs
$('#qr_title').on('input', function() {
    $('#preview_title').text($(this).val());
});

$('#qr_subtitle').on('input', function() {
    $('#preview_subtitle').text($(this).val());
});

$('#theme_color_picker').on('input', function() {
    $('#qr_color').val($(this).val());
});

$('#qr_color').on('input', function() {
    $('#theme_color_picker').val($(this).val());
});

</script>
@endpush
