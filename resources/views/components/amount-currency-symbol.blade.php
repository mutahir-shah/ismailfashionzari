@php
    $decimal = config('decimal');
    $currency = config('currency');
    $currency_position = config('currency_position');
    
    if (!$currency) $currency = config('currency');
@endphp

@props([
    'amount',
    'currency_symbol' => $currency,
    'currency_position' => $currency_position,
    'decimal' => $decimal,
])

@if (strlen($currency_symbol) > 1)
    @if ($currency_position == 'prefix')
        <span>{{ $currency_symbol }}&nbsp;{{ number_format((float) $amount, $decimal, '.', ',') }}</span>
    @else
        <span>{{ number_format((float) $amount, $decimal, '.', ',') }}&nbsp;{{ $currency_symbol }}</span>
    @endif
@else
    @if ($currency_position == 'prefix')
        <span>{{ $currency_symbol }}{{ number_format((float) $amount, $decimal, '.', ',') }}</span>
    @else
        <span>{{ number_format((float) $amount, $decimal, '.', ',') }}{{ $currency_symbol }}</span>
    @endif
@endif