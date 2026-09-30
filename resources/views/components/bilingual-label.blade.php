@props([
    'en' => '',
    'ar' => '',
    'sep' => '/'
])

<span {{ $attributes->merge(['class' => 'invoice-label']) }}>
    <span class="invoice-label-en">{{ $en }}</span>
    @if($sep !== false && $sep !== '')
        <span class="invoice-label-separator">{{ $sep }}</span>
    @endif
    <span class="invoice-label-ar" lang="ar" dir="rtl">{{ $ar }}</span>
</span>
