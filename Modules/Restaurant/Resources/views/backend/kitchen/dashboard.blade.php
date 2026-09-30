@extends('backend.layout.main')
@push('css')
<style>
    nav.navbar a.menu-btn {
        opacity: 0;
    }
    .side-navbar {
        width: 0;
        opacity: 0;
    }
    .page {
        margin-left: 0;
        width: 100%;
    }
    .order-card {
        background-color: #f4f4f4;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .order-card h5 {
        margin-bottom: 10px;
    }
    .order-card .btn {
        width: 100%;
        margin-top: 5px;
    }
    .kds-header-bar {
        background: #fff;
        border-radius: 8px;
        padding: 12px 18px;
        margin-bottom: 15px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    .kds-refresh-controls {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    #kds-toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 99999;
        min-width: 280px;
        max-width: 420px;
        pointer-events: none;
    }
    .kds-toast {
        pointer-events: auto;
        border-radius: 6px;
        padding: 12px 16px;
        margin-bottom: 10px;
        color: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        justify-content: space-between;
        animation: kdsFadeIn 0.25s ease-in-out;
    }
    .kds-toast-success {
        background-color: #28a745;
    }
    .kds-toast-danger {
        background-color: #dc3545;
    }
    .kds-toast-info {
        background-color: #17a2b8;
    }
    @keyframes kdsFadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .ti-spin {
        animation: spin 1s infinite linear;
        display: inline-block;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
@endpush
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
            @endif

            <!-- KDS Header & Controls -->
            <div class="kds-header-bar d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="mb-0 text-dark font-weight-bold">
                    <i class="ti ti-tools-kitchen-2 mr-1 text-primary"></i> {{ __('db.Kitchen Dashboard') }}
                </h4>
                <div class="kds-refresh-controls">
                    <span class="text-muted small font-weight-bold">{{ __('db.Auto Refresh') }}:</span>
                    <select id="kds-refresh-interval" class="form-control form-control-sm" style="width: 120px; display: inline-block;">
                        <option value="0">{{ __('db.Off') }}</option>
                        <option value="5">5 {{ __('db.seconds') }}</option>
                        <option value="10">10 {{ __('db.seconds') }}</option>
                        <option value="15">15 {{ __('db.seconds') }}</option>
                        <option value="30">30 {{ __('db.seconds') }}</option>
                        <option value="60">60 {{ __('db.seconds') }}</option>
                    </select>
                    <button type="button" id="kds-refresh-now-btn" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-refresh mr-1"></i> {{ __('db.Refresh Now') }}
                    </button>
                    <span class="text-muted small ml-1">
                        {{ __('db.Last Updated') }}: <strong id="kds-last-updated">{{ date('H:i:s') }}</strong>
                        <span id="kds-refresh-status" class="badge badge-danger ml-1" style="display: none;" title="{{ __('db.Refresh Failed') }}">
                            <i class="ti ti-alert-triangle"></i> {{ __('db.Refresh Failed') }}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Dynamic Dashboard Content (Updated via AJAX) -->
            <div id="kds-dashboard-content">
                @include('restaurant::backend.kitchen.partials.dashboard-content')
            </div>
        </div>
    </div>
</div>

<!-- Floating Toast Notification Container -->
<div id="kds-toast-container"></div>

<!-- Sale details modal -->
<div id="get-sale-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="container mt-3 pb-2 border-bottom">
                <div class="row">
                    <div class="col-md-6 d-print-none">
                        <button id="print-btn" type="button" class="btn btn-default btn-sm"><i class="ti ti-printer"></i> {{__('db.print')}}</button>
                        <form action="{{ route('sale.sendmail') }}" method="post" class="sendmail-form d-inline-block ml-1">
                            @csrf
                            <input type="hidden" name="sale_id">
                            <button type="submit" class="btn btn-default btn-sm d-print-none"><i class="ti ti-mail"></i> {{__('db.email')}}</button>
                        </form>
                    </div>
                    <div class="col-md-6 d-print-none text-right">
                        <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                </div>
            </div>
            <div id="sale-content" class="modal-body">
            </div>
            <br>
            <table class="table table-bordered product-sale-list">
                <thead>
                    <th>#</th>
                    <th>{{__('db.product')}}</th>
                    <th>{{__('db.Batch No')}}</th>
                    <th>{{__('db.qty')}}</th>
                    <th>{{__('db.Unit Price')}}</th>
                    <th>{{__('db.Subtotal')}}</th>
                </thead>
                <tbody>
                </tbody>
            </table>
            <div id="sale-footer" class="modal-body"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    (function($) {
        "use strict";

        var COOKIE_NAME = 'salepro_kds_refresh_interval';
        var ALLOWED_INTERVALS = [0, 5, 10, 15, 30, 60];
        var DEFAULT_INTERVAL = 15;

        var kdsRefreshTimer = null;
        var kdsRefreshInFlight = false;
        var statusMutationInFlight = false;

        // ── Native Cookie Helpers ───────────────────────────────────────────────
        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
        }

        function resolveRefreshInterval() {
            var cookieVal = getCookie(COOKIE_NAME);
            if (cookieVal !== null && cookieVal !== "") {
                var parsed = parseInt(cookieVal, 10);
                if (ALLOWED_INTERVALS.indexOf(parsed) !== -1) {
                    return parsed;
                }
            }
            return DEFAULT_INTERVAL;
        }

        // ── Lightweight Toast Notification ──────────────────────────────────────
        window.showKdsToast = function(message, type) {
            type = type || 'info';
            var toastClass = 'kds-toast-info';
            var iconClass = 'ti-info-circle';
            if (type === 'success') {
                toastClass = 'kds-toast-success';
                iconClass = 'ti-check';
            } else if (type === 'danger' || type === 'error') {
                toastClass = 'kds-toast-danger';
                iconClass = 'ti-alert-circle';
            }

            var $toast = $('<div class="kds-toast ' + toastClass + '">' +
                '<span><i class="ti ' + iconClass + ' mr-1"></i> ' + message + '</span>' +
                '</div>');

            $('#kds-toast-container').append($toast);

            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        };

        // ── Dashboard AJAX Refresh Logic ────────────────────────────────────────
        function fetchDashboardData(isManual) {
            if (kdsRefreshInFlight || statusMutationInFlight) {
                return;
            }

            kdsRefreshInFlight = true;
            if (isManual) {
                $('#kds-refresh-now-btn').prop('disabled', true).find('i').addClass('ti-spin');
            }

            // Capture active tab before AJAX replacement
            var activeTabId = $('#myTab .nav-item button.active').attr('id');

            $.ajax({
                url: '{{ route("restaurant.kitchen.dashboard.data") }}',
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    kdsRefreshInFlight = false;
                    if (isManual) {
                        $('#kds-refresh-now-btn').prop('disabled', false).find('i').removeClass('ti-spin');
                    }

                    if (response && response.status === 'success') {
                        $('#kds-dashboard-content').html(response.html);
                        $('#kds-last-updated').text(response.updated_at || new Date().toLocaleTimeString());
                        $('#kds-refresh-status').hide();

                        // Restore active tab
                        if (activeTabId && $('#' + activeTabId).length) {
                            $('#myTab .nav-item button').removeClass('active').attr('aria-selected', 'false');
                            $('#myTabContent .tab-pane').removeClass('show active');
                            $('#' + activeTabId).addClass('active').attr('aria-selected', 'true');
                            var targetPane = $('#' + activeTabId).data('target');
                            $(targetPane).addClass('show active');
                        } else {
                            var firstBtn = $('#myTab .nav-item:first-child button');
                            if (firstBtn.length) {
                                firstBtn.addClass('active').attr('aria-selected', 'true');
                                var firstTarget = firstBtn.data('target');
                                $(firstTarget).addClass('show active');
                            }
                        }

                        if (isManual) {
                            showKdsToast('{{ __("db.Kitchen Dashboard data refreshed.") }}', 'success');
                        }
                    } else {
                        if (isManual) {
                            showKdsToast((response && response.message) ? response.message : '{{ __("db.Refresh Failed") }}', 'danger');
                        }
                        $('#kds-refresh-status').show();
                    }
                },
                error: function(xhr, status, error) {
                    kdsRefreshInFlight = false;
                    if (isManual) {
                        $('#kds-refresh-now-btn').prop('disabled', false).find('i').removeClass('ti-spin');
                        showKdsToast('{{ __("db.Refresh Failed") }}', 'danger');
                    }
                    $('#kds-refresh-status').show();
                }
            });
        }

        // ── Timer Scheduling ────────────────────────────────────────────────────
        function startAutoRefreshTimer() {
            stopAutoRefreshTimer();
            var intervalSec = resolveRefreshInterval();
            if (intervalSec > 0 && document.visibilityState !== 'hidden') {
                kdsRefreshTimer = setInterval(function() {
                    fetchDashboardData(false);
                }, intervalSec * 1000);
            }
        }

        function stopAutoRefreshTimer() {
            if (kdsRefreshTimer) {
                clearInterval(kdsRefreshTimer);
                kdsRefreshTimer = null;
            }
        }

        // ── Initialize Interval Dropdown & Timer ────────────────────────────────
        var initialInterval = resolveRefreshInterval();
        $('#kds-refresh-interval').val(initialInterval);
        startAutoRefreshTimer();

        // ── Change Interval Event ───────────────────────────────────────────────
        $('#kds-refresh-interval').on('change', function() {
            var selectedVal = parseInt($(this).val(), 10);
            if (ALLOWED_INTERVALS.indexOf(selectedVal) === -1) {
                selectedVal = DEFAULT_INTERVAL;
                $(this).val(selectedVal);
            }
            setCookie(COOKIE_NAME, selectedVal, 365);
            startAutoRefreshTimer();
        });

        // ── Manual Refresh Now Button ───────────────────────────────────────────
        $('#kds-refresh-now-btn').on('click', function(e) {
            e.preventDefault();
            fetchDashboardData(true);
        });

        // ── Page Visibility API ─────────────────────────────────────────────────
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                stopAutoRefreshTimer();
            } else if (document.visibilityState === 'visible') {
                fetchDashboardData(false);
                startAutoRefreshTimer();
            }
        });

        // ── Action Buttons (Start Preparing, Mark as Ready, Mark as Served) ──────
        $(document).on('click', '.action-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var url = btn.data('url');
            if (!url || btn.prop('disabled')) {
                return;
            }

            statusMutationInFlight = true;
            btn.prop('disabled', true);
            var originalHtml = btn.html();
            btn.html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> ' + originalHtml);

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    statusMutationInFlight = false;
                    if (response && response.status === 'success') {
                        showKdsToast(response.message, 'success');
                        // Silent partial reload without full page reload
                        fetchDashboardData(false);
                    } else {
                        btn.html(originalHtml).prop('disabled', false);
                        showKdsToast((response && response.message) ? response.message : '{{ __("db.Error updating status.") }}', 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    statusMutationInFlight = false;
                    btn.html(originalHtml).prop('disabled', false);
                    var errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : error;
                    showKdsToast(errMsg || '{{ __("db.Error updating status.") }}', 'danger');
                }
            });
        });

        // ── Order Details Modal ─────────────────────────────────────────────────
        $(document).on('click', '.view-sale', function() {
            var sale_id = $(this).val();

            $.ajax({
                url: '{{ url("sales/get-sale") }}/' + sale_id,
                type: 'GET',
                success: function(sale) {
                    saleDetails(sale);
                },
                error: function(xhr, status, error) {
                    showKdsToast('Error loading details: ' + error, 'danger');
                }
            });
        });

        function saleDetails(sale) {
            var htmltext = '<strong>{{__("db.date")}}: </strong>' + sale.date +
                '<br><strong>{{__("db.reference")}}: </strong>' + sale.reference_no +
                '<br><strong>{{__("db.Warehouse")}}: </strong>' + sale.warehouse_name +
                '<br><strong>{{__("db.Sale Status")}}: </strong>' + sale.sale_status;

            if (sale.table_name) {
                htmltext += '<br><strong>{{__("db.table")}}: </strong>' + sale.table_name;
            }

            htmltext += '<br><br><div class="row"><div class="col-md-6"><strong>{{__("db.From")}}:</strong><br>' + sale.biller_name + '<br>' + sale.biller_company_name +
                '</div><div class="col-md-6"><div class="float-right"><strong>{{__("db.To")}}:</strong><br>' + sale.customer_name + '</div></div></div>';

            $('#sale-content').html(htmltext);
            $('.sendmail-form input[name="sale_id"]').val(sale.id);

            $.get('{{ url("sales/product_sale") }}/' + sale.id, function(data) {
                $(".product-sale-list tbody").remove();
                var name_code = data.product || [];
                var qty = data.qty || [];
                var subtotal = data.total || [];
                var batch_no = data.batch_no || [];
                var newBody = $("<tbody>");

                $.each(name_code, function(index) {
                    var newRow = $("<tr>");
                    var cols = '';
                    cols += '<td><strong>' + (index + 1) + '</strong></td>';
                    cols += '<td>' + name_code[index] + '</td>';
                    cols += '<td>' + (batch_no[index] || 'N/A') + '</td>';
                    cols += '<td>' + qty[index] + '</td>';
                    var unitPrice = parseFloat(subtotal[index] / qty[index]).toFixed(2);
                    cols += '<td>' + unitPrice + '</td>';
                    cols += '<td>' + parseFloat(subtotal[index]).toFixed(2) + '</td>';
                    newRow.append(cols);
                    newBody.append(newRow);
                });

                $(".product-sale-list").append(newBody);
                $('#sale-footer').html('<strong>{{__("db.Grand Total")}}: </strong>' + sale.grand_total);
            });
        }

        // ── Modal Send Mail AJAX Submit ─────────────────────────────────────────
        $(document).on('submit', '.sendmail-form', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button');
            btn.prop('disabled', true);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(res) {
                    btn.prop('disabled', false);
                    showKdsToast('{{ __("db.Mail sent successfully") }}', 'success');
                },
                error: function(xhr) {
                    btn.prop('disabled', false);
                    showKdsToast('{{ __("db.Failed to send email") }}', 'danger');
                }
            });
        });

    })(jQuery);
</script>
@endpush
