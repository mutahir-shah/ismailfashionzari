<div class="col-md-6 offset-md-3 mt-3 mb-3">
    <div class="row">
        <div class="col-md-6">
            <span>Total {{__('db.Items')}}</span>
            <h2><strong>{{number_format((float)$total_item, gen_setting()->decimal, '.', '')}}</strong></h2>
        </div>
        <div class="col-md-6">
            <span>Total {{__('db.Quantity')}}</span>
            <h2><strong>{{number_format((float)$total_qty, gen_setting()->decimal, '.', '')}}</strong></h2>
        </div>
    </div>
</div>

<div class="col-md-5 offset-md-3 mt-2">
    <div class="pie-chart">
        @php
            if(gen_setting()->theme == 'default.css'){
                $color = '#733686';
                $color_rgba = 'rgba(115, 54, 134, 0.8)';
            }
            elseif(gen_setting()->theme == 'green.css'){
                $color = '#2ecc71';
                $color_rgba = 'rgba(46, 204, 113, 0.8)';
            }
            elseif(gen_setting()->theme == 'blue.css'){
                $color = '#3498db';
                $color_rgba = 'rgba(52, 152, 219, 0.8)';
            }
            elseif(gen_setting()->theme == 'dark.css'){
                $color = '#34495e';
                $color_rgba = 'rgba(52, 73, 94, 0.8)';
            }
        @endphp
        <canvas id="pieChart" data-color="{{$color}}" data-color_rgba="{{$color_rgba}}" data-price={{$total_price}} data-cost={{$total_cost}} width="10" height="10" data-label1="{{__('db.Stock Value by Price')}}" data-label2="{{__('db.Stock Value by Cost')}}" data-label3="{{__('db.Estimate Profit')}}"> </canvas>
    </div>
</div>
