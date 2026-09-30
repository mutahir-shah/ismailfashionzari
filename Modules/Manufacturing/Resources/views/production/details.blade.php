<div class="product-production-list">
    <p><strong>{{ __('db.date') }}:</strong> {{ $production_details->created_at }}</p>
    <p><strong>{{ __('db.reference') }}:</strong> {{ $production_details->reference_no }}</p>
    <p><strong>{{ __('db.status') }}:</strong> {{ $production_details->status }}</p>
    <p><strong>{{ __('db.Warehouse') }}:</strong> {{ $production_details->warehouse->name ?? '' }}</p>

    @if ($production_details->document)
        <p><strong>{{ __('db.Attach Document') }}:</strong>
            <a href="{{ asset('documents/production/' . $production_details->document) }}" target="_blank">Download</a>
        </p>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('db.Product') }}</th>
                <th>{{ __('db.qty') }}</th>
                <th>{{ __('db.Received') }}</th>
                <th>{{ __('db.Unit Cost') }}</th>
                <th>{{ __('db.Tax') }}</th>
                <th>{{ __('db.Subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $product_list = explode(',', $production_details->product_list);
            $wastage_percent = explode(',', $production_details->wastage_percent);
            $qty_list = explode(',', $production_details->qty_list);
            $variant_list = explode(',', $production_details->variant_list);
            $price_list = explode(',', $production_details->price_list);
            $id = $production_details->product_id;
            ?>
            @foreach ($product_list as $index => $item)
                <tr>
                    <?php
                    $product = App\Models\Product::find($id);
                    $combo_unit = App\Models\Unit::query()->where('id', $product->unit_id)->orWhere('base_unit', $product->unit_id)->get()->unique('id');


                    ?>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $product->name ?? '' }}</td>
                    {{-- <td>{{ $item->qty }} {{ $item->unit }}</td>
                    <td>{{ $item->received_qty }} {{ $item->unit }}</td>
                    <td>{{ number_format($item->net_unit_cost, 2) }}</td>
                    <td>{{ $item->tax }} ({{ $item->tax_rate }}%)</td>
                    <td>{{ $item->subtotal }}</td> --}}
                </tr>
            @endforeach
            <tr>
                <td colspan="5"><strong>{{ __('db.Total') }}</strong></td>
                <td>{{ $production_details->total_tax }}</td>
                <td>{{ $production_details->total_cost }}</td>
            </tr>
            <tr>
                <td colspan="6"><strong>{{ __('db.Shipping Cost') }}</strong></td>
                <td>{{ $production_details->shipping_cost }}</td>
            </tr>
            <tr>
                <td colspan="6"><strong>{{ __('db.grand total') }}</strong></td>
                <td>{{ $production_details->grand_total }}</td>
            </tr>
        </tbody>
    </table>

    <p><strong>{{ __('db.note') }}</strong>: {{ $production_details->note }}</p>
    <p><strong>{{ __('db.Created By') }}</strong>: {{ $production_details->createdBy->name ?? '' }}</p>
</div>
