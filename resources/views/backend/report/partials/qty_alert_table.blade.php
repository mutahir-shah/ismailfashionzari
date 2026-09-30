<div class="table-responsive mb-4">
    <table id="report-table" class="table table-hover" style="width: 100%">
        <thead>
            <tr>
                <th class="not-exported"></th>
                <th>{{__('db.Image')}}</th>
                <th>{{__('db.Product Name')}}</th>
                <th>{{__('db.Product Code')}}</th>
                <th>{{__('db.Quantity')}}</th>
                <th>{{__('db.Alert Quantity')}}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lims_product_data as $key=>$product)
            <tr>
                <td>{{$key}}</td>
                <td>
                <?php
                    $images = explode(",", $product->image);
                    $product->base_image = $images[0];
                ?>
                    <img src="{{url('images/product',$product->base_image)}}" height="80" width="80">
                </td>
                <td>{{$product->name}}</td>
                <td>{{$product->code}}</td>
                <td>{{number_format((float)($product->qty), gen_setting()->decimal, '.', '')}}</td>
                <td>{{number_format((float)($product->alert_quantity), gen_setting()->decimal, '.', '')}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
