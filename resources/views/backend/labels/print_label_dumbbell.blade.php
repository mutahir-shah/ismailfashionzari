<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

<style>
html, body {
    margin: 0 !important;
    padding: 0 !important;
    background-color: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

@page {
    margin: 0;
    /* FIXED: Added explicit space separating width and height variables */
    size: {{ $barcode_details->paper_width }}in {{ $barcode_details->paper_height }}in;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
}

.label {
    width: {{ $barcode_details->width }}in;
    height: {{ $barcode_details->height }}in;

    display: flex;
    flex-direction: row; /* Explicitly ensure horizontal layout */
    align-items: center;
    justify-content: space-between;

    overflow: hidden;
    padding: 0.5mm 1mm; /* Reduced top/bottom padding slightly to prevent overflow */

    page-break-after: always;
    break-after: page;
}

.left,
.right {
    width: 44%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    overflow: hidden;
}

.center {
    width: 12%;
    height: 100%;
}

.business {
    font-size: 6px;
    font-weight: bold;
    line-height: 1.1;
    margin-bottom: 1px;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product {
    font-size: 7px;
    font-weight: bold;
    line-height: 1.1;
    max-height: 16px;
    overflow: hidden;
    margin-bottom: 1px;
    width: 100%;
    text-overflow: ellipsis;
}

.brand {
    font-size: 6px;
    line-height: 1.1;
    margin-bottom: 1px;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.price {
    font-size: 7px;
    font-weight: bold;
    line-height: 1.1;
    margin-bottom: 1px;
    width: 100%;
}

.barcode {
    width: 100%;
    max-height: 14px; /* Scaled down slightly to guarantee it doesn't push the SKU off-screen */
    margin: 1px auto;
    display: block;
    object-fit: contain;
}

.sku {
    font-size: 6px;
    line-height: 1.1;
    word-break: break-all;
    width: 100%;
}
</style>
</head>

<body onload="window.print()">

@foreach($labels as $label)
<div class="label">

    {{-- LEFT WING --}}
    <div class="left">
        @if(!empty($print['business_name']))
            <div class="business">{{ $business_name }}</div>
        @endif

        @if(!empty($print['name']))
            <div class="product">{{ $label['product_actual_name'] }}</div>
        @endif

        @if(!empty($print['brand_name']))
            <div class="brand">{{ $label['brand_name'] }}</div>
        @endif

        @if(!empty($print['price']))
            <div class="price">
                @if(!empty($print['promo_price']) && $label['product_promo_price'] != 'null')
                    <span style="text-decoration:line-through;">{{ format_currency($label['product_price']) }}</span>
                    {{ format_currency($label['product_promo_price']) }}
                @else
                    {{ format_currency($label['product_price']) }}
                @endif
            </div>
        @endif

        {{-- Note: Ensure 20px height in the backend generator matches your CSS limits --}}
        <img class="barcode" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($label['sub_sku'], $label['barcode_type'], 1.5, 20) }}">
        <div class="sku">{{ $label['sub_sku'] }}</div>
    </div>

    {{-- CENTER (non-adhesive tail) --}}
    <div class="center"></div>

    {{-- RIGHT WING --}}
    <div class="right">
        @if(!empty($print['business_name']))
            <div class="business">{{ $business_name }}</div>
        @endif

        @if(!empty($print['name']))
            <div class="product">{{ $label['product_actual_name'] }}</div>
        @endif

        @if(!empty($print['brand_name']))
            <div class="brand">{{ $label['brand_name'] }}</div>
        @endif

        @if(!empty($print['price']))
            <div class="price">
                @if(!empty($print['promo_price']) && $label['product_promo_price'] != 'null')
                    <span style="text-decoration:line-through;">{{ format_currency($label['product_price']) }}</span>
                    {{ format_currency($label['product_promo_price']) }}
                @else
                    {{ format_currency($label['product_price']) }}
                @endif
            </div>
        @endif

        <img class="barcode" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($label['sub_sku'], $label['barcode_type'], 1.5, 20) }}">
        <div class="sku">{{ $label['sub_sku'] }}</div>
    </div>

</div>
@endforeach

</body>
</html>