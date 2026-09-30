<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

<style>
/* Reset default browser margins */
html, body {
    margin: 0 !important;
    padding: 0 !important;
    background-color: #fff;
}

@media print {
    @page {
        size: {{ $barcode_details->width }}in {{ $barcode_details->height }}in;
        margin: 0;
    }

    .single-label {
        width: {{ $barcode_details->width }}in;
        height: {{ $barcode_details->height }}in;
        box-sizing: border-box;
        overflow: hidden;
        padding: 0.04in; /* Tighter padding for micro labels */
        
        /* Flexbox handles the vertical distribution perfectly across all sizes */
        display: flex;
        flex-direction: column;
        justify-content: space-between; 
        align-items: center;
        
        page-break-after: always;
        break-after: page;
    }

    .label-inner {
        width: 100%;
        text-align: center;
        line-height: 1.1; /* Keeps text lines compact */
    }

    /* Enforce text boundaries so long names don't break the height layout */
    .truncate-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        width: 100%;
        display: block;
    }

    img.barcode-img {
        max-width: 100%; /* Prevents horizontal bleeding */
        height: {{ $barcode_details->height * 0.35 }}in; /* Scaled down slightly to fit text */
        display: block;
        margin: 1px auto;
        object-fit: contain;
    }
}
</style>
</head>

<body onload="window.print()">

@foreach($labels as $label)

<div class="single-label">
    <div class="label-inner">

        {{-- Business Name --}}
        @if(!empty($print['business_name']))
            <div class="truncate-text" style="font-size: {{ $print['business_name_size'] ?? 10 }}px; font-weight: bold;">
                {{ $business_name }}
            </div>
        @endif

        {{-- Product Name --}}
        @if(!empty($print['name']))
            <div class="truncate-text" style="font-size: {{ $print['name_size'] ?? 9 }}px;">
                {{ $label['product_actual_name'] }}
            </div>
        @endif

        {{-- Brand --}}
        @if(!empty($print['brand_name']))
            <div class="truncate-text" style="font-size: {{ $print['brand_name_size'] ?? 9 }}px;">
                {{ $label['brand_name'] }}
            </div>
        @endif

        {{-- Price --}}
        @if(!empty($print['price']))
            <div style="font-size: {{ $print['price_size'] ?? 11 }}px; font-weight: bold; margin: 1px 0;">
                {{ format_currency($label['product_price']) }}
            </div>
        @endif

        {{-- Barcode - Kept strictly on one line to avoid string corruption --}}
        <img class="barcode-img" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($label['sub_sku'], $label['barcode_type'], 2, 40) }}">

        <div style="font-size: 8px; font-family: monospace;">
            {{ $label['sub_sku'] }}
        </div>

    </div>
</div>

@endforeach

</body>
</html>