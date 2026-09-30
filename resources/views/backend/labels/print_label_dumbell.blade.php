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
        /* Total width and height of the entire dumbbell sticker strip */
        size: {{ $barcode_details->width }}in {{ $barcode_details->height }}in;
        margin: 0;
    }

    .single-label {
        width: {{ $barcode_details->width }}in;
        height: {{ $barcode_details->height }}in;
        box-sizing: border-box;
        overflow: hidden;
        
        /* Set up side-by-side sections for the dumbbell shape */
        display: flex;
        flex-direction: row;
        justify-content: space-between; 
        align-items: center;
        
        page-break-after: always;
        break-after: page;
    }

    /* Left Wing - Where your print data goes */
    .label-left {
        width: 42%; /* Adjust based on your exact sticker proportions */
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        line-height: 1.1;
        padding: 0.02in;
        box-sizing: border-box;
    }

    /* Middle Bridge (Blank Spacer) */
    .label-bridge {
        width: 16%; /* The narrow middle strip */
        height: 100%;
    }

    /* Right Wing (Blank or duplicate info) */
    .label-right {
        width: 42%; 
        height: 100%;
        box-sizing: border-box;
        /* Kept blank as per your image, but ready if you want to mirror text later */
    }

    /* Enforce text boundaries so long names don't break the layout */
    .truncate-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        width: 100%;
        display: block;
    }

    img.barcode-img {
        max-width: 100%; 
        height: {{ $barcode_details->height * 0.35 }}in; 
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
    
    <div class="label-left">

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

        {{-- Barcode --}}
        <img class="barcode-img" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($label['sub_sku'], $label['barcode_type'], 2, 40) }}">

        <div style="font-size: 8px; font-family: monospace;">
            {{ $label['sub_sku'] }}
        </div>

    </div>

    <div class="label-bridge"></div>

    <div class="label-right"></div>

</div>

@endforeach

</body>
</html>