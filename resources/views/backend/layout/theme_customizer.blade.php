<!-- Theme Customizer Panel -->
<div id="theme-customizer" class="theme-customizer shadow-lg d-print-none">
    <div class="customizer-header d-flex justify-content-between align-items-center p-3 border-bottom">
        <h5 class="mb-0">{{ __('Theme Settings') }}</h5>
        <button id="close-customizer" class="btn btn-sm btn-light"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6l-12 12"></path><path d="M6 6l12 12"></path></svg></button>
    </div>
    
    <div class="customizer-body p-3">
        <!-- Mode -->
        <div class="mb-4">
            <label class="font-weight-bold">{{ __('Mode') }}</label>
            <div class="d-flex justify-content-between">
                <button class="btn btn-outline-secondary theme-mode-btn flex-fill mr-2 {{ ($theme ?? 'light') == 'light' ? 'active' : '' }}" data-val="light"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"></path></svg> Light</button>
                <button class="btn btn-outline-secondary theme-mode-btn flex-fill {{ ($theme ?? 'light') == 'dark' ? 'active' : '' }}" data-val="dark"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px"><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"></path></svg> Dark</button>
            </div>
        </div>

        <!-- Fonts -->
        <div class="mb-4">
            <label class="font-weight-bold">{{ __('Typography (Font)') }}</label>
            <select class="form-control selectpicker theme-font-select">
                <option value="inter" {{ ($theme_font ?? 'inter') == 'inter' ? 'selected' : '' }}>Inter (Default, Modern)</option>
                <option value="nunito" {{ ($theme_font ?? 'inter') == 'nunito' ? 'selected' : '' }}>Nunito (Friendly, Soft)</option>
                <option value="fira" {{ ($theme_font ?? 'inter') == 'fira' ? 'selected' : '' }}>Fira Code (Technical, Monospace)</option>
                <option value="roboto" {{ ($theme_font ?? 'inter') == 'roboto' ? 'selected' : '' }}>Roboto (Clean, Professional)</option>
                <option value="poppins" {{ ($theme_font ?? 'inter') == 'poppins' ? 'selected' : '' }}>Poppins (Geometric, Bold)</option>
                <option value="lato" {{ ($theme_font ?? 'inter') == 'lato' ? 'selected' : '' }}>Lato (Elegant, Balanced)</option>
                <option value="outfit" {{ ($theme_font ?? 'inter') == 'outfit' ? 'selected' : '' }}>Outfit (Contemporary)</option>
            </select>
        </div>

        <!-- Primary Color -->
        <div class="mb-4">
            <label class="font-weight-bold">{{ __('Primary Color') }}</label>
            <div class="d-flex flex-wrap color-swatches">
                @php
                    $colors = [
                        '#7c5cc4', '#34cea7', '#2196f3', '#ffc107', '#ff7588', '#288b46', '#e74c3c', '#34495e',
                        '#9b59b6', '#1abc9c', '#e67e22', '#3498db', '#f39c12', '#d35400', '#c0392b', '#8e44ad', '#2c3e50'
                    ];
                @endphp
                @foreach($colors as $col)
                    <div class="color-swatch {{ $theme_color == $col ? 'active' : '' }}" data-val="{{ $col }}" style="background-color: {{ $col }};"></div>
                @endforeach
            </div>
            <div class="mt-2">
                <small>Or choose custom:</small>
                <input type="color" id="custom-theme-color" class="form-control p-1" value="{{ $theme_color }}">
            </div>
        </div>

    </div>
</div>

<!-- Floating Gear Icon -->
<button id="open-customizer" class="btn btn-primary shadow-lg d-print-none">
    <svg xmlns="http://www.w3.org/2000/svg" class="spin-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"></path><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path></svg>
</button>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    :root {
        --theme-color: {{ $theme_color }};
    }
    
    @if($theme_font == 'inter')
        body { font-family: 'Inter', sans-serif !important; }
    @elseif($theme_font == 'nunito')
        body { font-family: 'Nunito', sans-serif !important; }
    @elseif($theme_font == 'fira')
        body { font-family: 'Fira Code', monospace !important; }
    @elseif($theme_font == 'roboto')
        body { font-family: 'Roboto', sans-serif !important; }
    @elseif($theme_font == 'poppins')
        body { font-family: 'Poppins', sans-serif !important; }
    @elseif($theme_font == 'lato')
        body { font-family: 'Lato', sans-serif !important; }
    @elseif($theme_font == 'outfit')
        body { font-family: 'Outfit', sans-serif !important; }
    @endif

    /* Panel Styles */
    .theme-customizer {
        position: fixed;
        top: 0;
        right: -320px;
        width: 320px;
        height: 100vh;
        background: #fff;
        z-index: 1050;
        transition: right 0.3s ease;
        overflow-y: auto;
    }
    body.dark-mode .theme-customizer {
        background: #283046;
        color: #d0d2d6;
    }
    .theme-customizer.show {
        right: 0;
    }
    #open-customizer {
        position: fixed;
        bottom: 20%;
        right: 30px;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        z-index: 1040;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    html[dir="rtl"] #open-customizer {
        right: auto;
        left: 30px;
    }
    .spin-icon {
        animation: spin 3s linear infinite;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    
    .color-swatches {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .color-swatch {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid transparent;
        transition: transform 0.1s;
    }
    .color-swatch:hover {
        transform: scale(1.1);
    }
    .color-swatch.active {
        border-color: #000;
        transform: scale(1.1);
    }
    body.dark-mode .color-swatch.active {
        border-color: #fff;
    }
</style>

<script>
$(document).ready(function() {
    let settings = {
        theme_color: '{{ $theme_color }}',
        theme_font: '{{ $theme_font }}',
        theme: '{{ $theme }}'
    };

    const fontFamilies = {
        'inter': "'Inter', sans-serif",
        'nunito': "'Nunito', sans-serif",
        'fira': "'Fira Code', monospace",
        'roboto': "'Roboto', sans-serif",
        'poppins': "'Poppins', sans-serif",
        'lato': "'Lato', sans-serif",
        'outfit': "'Outfit', sans-serif"
    };

    function applyVisuals() {
        // Apply color
        document.documentElement.style.setProperty('--theme-color', settings.theme_color);
        
        // Apply mode instantly
        if(settings.theme === 'dark') {
            $('body').addClass('dark-mode');
        } else {
            $('body').removeClass('dark-mode');
        }

        // Apply font instantly
        if (fontFamilies[settings.theme_font]) {
            document.body.style.setProperty('font-family', fontFamilies[settings.theme_font], 'important');
        }
    }

    function saveSettings() {
        // Instantly apply visually without reload
        applyVisuals();

        $.ajax({
            url: '{{ route("themeSetting.update") }}',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                theme_color: settings.theme_color,
                theme_font: settings.theme_font,
                theme: settings.theme
            },
            success: function(response) {
                // Done silently
            },
            error: function(xhr) {
                console.error("Theme save failed", xhr.responseText);
            }
        });
    }

    // Toggle Customizer Panel
    $('#open-customizer').on('click', function() {
        $('#theme-customizer').addClass('show');
    });
    $('#close-customizer').on('click', function() {
        $('#theme-customizer').removeClass('show');
    });

    // Theme Mode
    $('.theme-mode-btn').on('click', function() {
        $('.theme-mode-btn').removeClass('active');
        $(this).addClass('active');
        settings.theme = $(this).data('val');
        saveSettings();
    });

    // Font Select
    $('.theme-font-select').on('change', function() {
        settings.theme_font = $(this).val();
        saveSettings();
    });

    // Color Swatch
    $('.color-swatch').on('click', function() {
        $('.color-swatch').removeClass('active');
        $(this).addClass('active');
        settings.theme_color = $(this).data('val');
        $('#custom-theme-color').val(settings.theme_color);
        saveSettings();
    });

    // Custom Color Picker
    $('#custom-theme-color').on('change', function() {
        settings.theme_color = $(this).val();
        $('.color-swatch').removeClass('active');
        saveSettings();
    });
});
</script>
