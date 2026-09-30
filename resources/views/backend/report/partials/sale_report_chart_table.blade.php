<?php 
    $color = '#733686';
    $color_rgba = 'rgba(115, 54, 134, 0.8)';
?>
<div class="card-body">
    <canvas id="sale-report-chart" data-color="{{$color}}" data-color_rgba="{{$color_rgba}}" data-soldqty="{{json_encode($sold_qty)}}" data-datepoints="{{json_encode($date_points)}}" data-label1="{{__('db.Sold Qty')}}"></canvas>
</div>
