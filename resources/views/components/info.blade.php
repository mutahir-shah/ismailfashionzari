@props([
    'title' => 'Info',
    'type' => 'primary',
    'size' => 'regular' // options: 'small', 'regular'
])

@php
    $iconClass = $size === 'small' ? 'info-icon-sm' : 'info-icon';
@endphp
<span class="ti ti-info-circle {{ $iconClass }}" data-toggle="tooltip" title="{{ $title }}" style="color:#004085;display:inline-block;font-size: 15px;cursor: help;"></span>
