/* Custom Layout Scripts extracted for caching and optimization */

// Preloader functions (used in POS/top-head)
function myFunction() {
    setTimeout(showPage, 150);
}

function showPage() {
    var loader = document.getElementById("loader");
    if(loader) {
        loader.style.display = "none";
    }
    var content = document.getElementById("content");
    if(content) {
        content.style.display = "block";
    }
    $("#lims_productcodeSearch").focus();
}

document.addEventListener("DOMContentLoaded", function () {
    const menu = document.getElementById("side-main-menu");
    if (!menu) return;

    const KEY = "salepro_menu_scroll";

    /* ---------- Restore Scroll ---------- */
    const saved = localStorage.getItem(KEY);
    if (saved !== null) {
        menu.scrollTop = parseInt(saved);
    }

    /* ---------- Save Scroll ---------- */
    menu.addEventListener("scroll", function () {
        localStorage.setItem(KEY, menu.scrollTop);
    });

    /* ---------- Center Active Link ---------- */
    const active = menu.querySelector("a.active");

    if (active) {
        // If inside collapsed dropdown, open it first
        let parentCollapse = active.closest(".collapse");
        if (parentCollapse) {
            parentCollapse.classList.add("show");
        }

        // Let browser handle perfect centering
        setTimeout(() => {
            active.scrollIntoView({
                behavior: "auto",
                block: "center",
                inline: "nearest"
            });
        }, 50);
    }

    /* Clear scroll when manually clicking menu */
    document.querySelectorAll('#side-main-menu a').forEach(function(link){
        link.addEventListener('click', function(){
            localStorage.removeItem('salepro_menu_scroll');
        });
    });

    // find any active link inside collapsed menus
    document.querySelectorAll('#side-main-menu ul.collapse').forEach(function(submenu){
        const activeChild = submenu.querySelector('a.active');
        if(activeChild){
            // 1) open the collapse
            submenu.classList.add('show');
            // 2) mark the parent toggle as expanded
            const parentToggle = document.querySelector('a[href="#'+submenu.id+'"]');
            if(parentToggle){
                parentToggle.setAttribute('aria-expanded', 'true');
                parentToggle.classList.remove('collapsed');
            }
        }
    });
});

$(document).ready(function() {
    // Hide alerts after 7 seconds
    $("div.alert").delay(7000).slideUp(750);

    // Sidebar layout toggle
    $('#toggle-btn').on('click', function() {
        let sidebar = $('nav.side-navbar');
        if (sidebar.hasClass('shrink')) {
            localStorage.setItem("layout", "wide");
        } else {
            localStorage.setItem("layout", "shrink");
        }
    });

    // Restore sidebar layout
    let layout = localStorage.getItem("layout");
    let sidebar = $('nav.side-navbar');
    let page = $('.page');
    if (layout == "shrink") {
        sidebar.addClass('shrink');
        page.addClass('active');
    } else if (layout == "wide") {
        sidebar.removeClass('shrink');
        page.removeClass('active');
    }

    if ($(window).outerWidth() > 1199) {
        $('nav.side-navbar').removeClass('shrink');
    }
});

// Setup AJAX CSRF Token
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Theme Switcher Logic
if (window.SaleproConfig) {
    var theme = window.SaleproConfig.theme;
    if (theme == 'dark') {
        $('body').addClass('dark-mode');
        $('#switch-theme i').addClass('ti ti-brightness-down');
    } else {
        $('body').removeClass('dark-mode');
        $('#switch-theme i').addClass('ti ti-brightness-up');
    }
    
    $('#switch-theme').click(function() {
        var url;
        if (theme == 'light') {
            theme = 'dark';
            url = window.SaleproConfig.switchThemeDarkUrl;
            $('body').addClass('dark-mode');
            $('#switch-theme i').removeClass('ti ti-brightness-up').addClass('ti ti-brightness-down');
        } else {
            theme = 'light';
            url = window.SaleproConfig.switchThemeLightUrl;
            $('body').removeClass('dark-mode');
            $('#switch-theme i').removeClass('ti ti-brightness-down').addClass('ti ti-brightness-up');
        }

        if(url) {
            $.get(url, function(data) {
                console.log('theme changed to ' + theme);
            });
        }
    });

    var alert_product = window.SaleproConfig.alertProduct;

    // Currency Formatting Config
    window.appConfig = {
        currency: window.SaleproConfig.currency,
        currency_position: window.SaleproConfig.currency_position,
        decimal: window.SaleproConfig.decimal
    };
}

function confirmDelete() {
    if (confirm("Are you sure want to delete?")) {
        return true;
    }
    return false;
}

function formatCurrency(amount) {
    if(typeof window.appConfig === 'undefined') return amount;
    
    let formatted = parseFloat(amount).toFixed(window.appConfig.decimal);
    formatted = formatted.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    if (window.appConfig.currency_position === 'prefix') {
        return window.appConfig.currency + '\u00A0' + formatted;
    }
    return formatted + '\u00A0' + window.appConfig.currency;
}

$(document).ready(function() {

    $("li#notification-icon").on("click", function(argument) {
        $.get('notifications/mark-as-read', function(data) {
            if(typeof alert_product !== 'undefined') {
                $("span.notification-number").text(alert_product);
            }
        });
    });

    // Modal Triggers
    $("a#add-expense").click(function(e) { e.preventDefault(); $('#expense-modal').modal(); });
    $("a#add-income").click(function(e) { e.preventDefault(); $('#income-modal').modal(); });
    $("a#send-notification").click(function(e) { e.preventDefault(); $('#notification-modal').modal(); });
    $("a#add-account").click(function(e) { e.preventDefault(); $('#account-modal').modal(); });
    $("a#account-statement").click(function(e) { e.preventDefault(); $('#account-statement-modal').modal(); });
    
    // Form submission links
    $("a#profitLoss-link").click(function(e) { e.preventDefault(); $("#profitLoss-report-form").submit(); });
    $("a#report-link").click(function(e) { e.preventDefault(); $("#product-report-form").submit(); });
    $("a#purchase-report-link").click(function(e) { e.preventDefault(); $("#purchase-report-form").submit(); });
    $("a#sale-report-link").click(function(e) { e.preventDefault(); $("#sale-report-form").submit(); });
    $("a#sale-report-chart-link").click(function(e) { e.preventDefault(); $("#sale-report-chart-form").submit(); });
    $("a#payment-report-link").click(function(e) { e.preventDefault(); $("#payment-report-form").submit(); });
    $("a#due-report-link").click(function(e) { e.preventDefault(); $("#customer-due-report-form").submit(); });
    $("a#supplier-due-report-link").click(function(e) { e.preventDefault(); $("#supplier-due-report-form").submit(); });

    // Additional Modal Triggers
    $("a#warehouse-report-link").click(function(e) { e.preventDefault(); $('#warehouse-modal').modal(); });
    $("a#user-report-link").click(function(e) { e.preventDefault(); $('#user-modal').modal(); });
    $("a#biller-report-link").click(function(e) { e.preventDefault(); $('#biller-modal').modal(); });
    $("a#customer-report-link").click(function(e) { e.preventDefault(); $('#customer-modal').modal(); });
    $("a#customer-group-report-link").click(function(e) { e.preventDefault(); $('#customer-group-modal').modal(); });
    $("a#supplier-report-link").click(function(e) { e.preventDefault(); $('#supplier-modal').modal(); });

    // Datepicker
    if($.fn.datepicker) {
        $('.date').datepicker({
            format: "dd-mm-yyyy",
            autoclose: true,
            todayHighlight: true
        });
    }

    // Selectpicker
    if($.fn.selectpicker) {
        $('select').selectpicker({
            style: 'btn-link',
        });
    }

    // Expense Category Logic
    $('select[name="expense_category_id"]').on('changed.bs.select', function() {
        let cat = $(this).val();

        if (cat == "0") {
            $('#employee-fields').show();
            $("#expense-type").prop("disabled", false);
            let options = `
            <option value="expense_load">Expense</option>
            <option value="advance">Advance</option>
        `;
            $("#expense-type").html(options).selectpicker('refresh');
        } else {
            $('#employee-fields').hide();
            $("#expense-employee").val('').selectpicker('refresh');
            $("#expense-type").html('<option value="">Select Type</option>')
                .prop("disabled", true)
                .selectpicker('refresh');
        }
    });

    // Daterangepicker Setup
    if($.fn.daterangepicker && window.SaleproConfig) {
        
        var start = moment().subtract(365, 'days');
        var end = moment();

        if(window.SaleproConfig.starting_date) {
            start = moment(window.SaleproConfig.starting_date, 'YYYY-MM-DD');
        }
        if(window.SaleproConfig.ending_date) {
            end = moment(window.SaleproConfig.ending_date, 'YYYY-MM-DD');
        }

        function setDates(start, end) {
            var starting_date = start.format('YYYY-MM-DD');
            var ending_date = end.format('YYYY-MM-DD');
            var title = starting_date + ' To ' + ending_date;
            
            $('.daterangepicker-field').val(title);
            $('input[name="starting_date"],input[name="start_date"]').val(starting_date);
            $('input[name="ending_date"],input[name="end_date"]').val(ending_date);
        }

        var isRtl = window.SaleproConfig.isRtl || false;

        $('.daterangepicker-field').daterangepicker({
            startDate: start,
            endDate: end,
            autoUpdateInput: false,
            autoApply: true,
            opens: isRtl ? 'right' : 'left',
            locale: {
                cancelLabel: 'Clear'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [
                    moment().subtract(1, 'month').startOf('month'),
                    moment().subtract(1, 'month').endOf('month')
                ],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'All Time': [moment('2000-01-01'), moment()]
            }
        });

        $('.daterangepicker-field').on('apply.daterangepicker', function (ev, picker) {
            setDates(picker.startDate, picker.endDate);
        });

        $('.daterangepicker-field').on('focus', function() {
            $(this).data('daterangepicker').show();
        });

        // set initial value on page load
        setDates(start, end);
    }
});

// Globally fix Bootstrap modal "Blocked aria-hidden on an element because its descendant retained focus" error
$(document).on('hide.bs.modal', '.modal', function () {
    if (document.activeElement) {
        document.activeElement.blur();
    }
});
