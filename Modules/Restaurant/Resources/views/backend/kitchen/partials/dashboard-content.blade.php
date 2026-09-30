@if(!empty($isUnassigned) && $isUnassigned)
    <div class="alert alert-warning text-center mt-4">
        <i class="ti ti-alert-triangle mr-2"></i>
        {{ __('db.You do not have an assigned kitchen or service staff role. Please contact your administrator.') }}
    </div>
@else
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist" style="border-bottom:none">
        @if(!empty($kitchen_list) && count($kitchen_list) > 0)
            @foreach($kitchen_list as $key => $kitchen)
            <li class="nav-item" role="presentation">
                <button class="btn btn-primary @if($key == 0) active @endif mr-2"
                        id="kitchen-tab-{{ $kitchen->id }}"
                        data-toggle="tab"
                        data-target="#kitchen-{{ $kitchen->id }}"
                        type="button"
                        role="tab"
                        aria-controls="kitchen-{{ $kitchen->id }}"
                        aria-selected="{{ $key == 0 ? 'true' : 'false' }}">
                    {{ $kitchen->name }}
                </button>
            </li>
            @endforeach
        @endif

        @if(!empty($isAdmin) || !empty($isWaiter))
        <li class="nav-item" role="presentation">
            <button class="btn btn-warning @if(empty($kitchen_list) || count($kitchen_list) == 0) active @endif mr-2 font-weight-bold"
                    id="ready-tab"
                    data-toggle="tab"
                    data-target="#ready-tab-pane"
                    type="button"
                    role="tab"
                    aria-controls="ready-tab-pane"
                    aria-selected="{{ (empty($kitchen_list) || count($kitchen_list) == 0) ? 'true' : 'false' }}">
                <i class="ti ti-bell mr-1"></i> {{ __('db.Ready for Service') }} ({{ $readySalesData->count() }})
            </button>
        </li>
        @endif
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="myTabContent">
        @if(!empty($kitchen_list) && count($kitchen_list) > 0)
            @foreach($kitchen_list as $key => $kitchen)
            <div class="tab-pane fade @if($key == 0) show active @endif"
                id="kitchen-{{ $kitchen->id }}"
                role="tabpanel"
                aria-labelledby="kitchen-tab-{{ $kitchen->id }}">
                <div class="row">
                    <!-- Kitchen Items Cards -->
                    @foreach($salesData->where('kitchen_id', $kitchen->id) as $sale)
                    @php
                        $sale_date = \Carbon\Carbon::parse($sale->sale_date)->format($dateFormat);
                    @endphp
                    <div class="col-md-3 mt-4">
                        <div class="order-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Order #{{ $sale->sale_id }}</h5>
                                <span class="badge badge-info">{{ $sale->warehouse }}</span>
                            </div>
                            <p class="mb-1"><strong>{{ __('db.Placed at') }}:</strong> {{ $sale_date }} {{ \Carbon\Carbon::parse($sale->sale_date)->format('H:i') }}</p>
                            <div class="alert alert-secondary p-2 mb-2">
                                <p class="mb-1" style="font-size: 1.1em;"><strong>{{ (int)$sale->total_quantity }}x {{ $sale->product_name }}</strong></p>
                                @if($sale->modifier_labels)
                                <p class="mb-0 text-muted" style="font-size: 0.9em;"><i class="ti ti-check-box"></i> {{ $sale->modifier_labels }}</p>
                                @endif
                            </div>
                            <p class="mb-1"><strong>{{ __('db.Item Status') }}:</strong>
                                @if($sale->kitchen_status == 1)
                                <span class="badge badge-warning text-dark">{{ __('db.Preparing') }}</span>
                                @else
                                <span class="badge badge-secondary">{{ __('db.Pending') }}</span>
                                @endif
                            </p>
                            <p class="mb-1"><strong>{{ __('db.Customer') }}:</strong> {{ $sale->customer_name }}</p>
                            @if(!empty($sale->table_name))<p class="mb-1"><strong>{{ __('db.Table') }}:</strong> {{ $sale->table_name }}</p>@endif
                            @if(!empty($sale->waiter_name))<p class="mb-2"><strong>{{ __('db.Waiter') }}:</strong> {{ $sale->waiter_name }}</p>@endif

                            @if($sale->kitchen_status == 0)
                            <button class="btn btn-primary action-btn" data-url="{{ route('restaurant.sale.status.preparing', $sale->product_sale_id) }}">
                                <i class="ti ti-flame mr-1"></i> {{ __('db.Start Preparing') }}
                            </button>
                            @elseif($sale->kitchen_status == 1)
                            <button class="btn btn-success action-btn" data-url="{{ route('restaurant.sale.status.cooked', $sale->product_sale_id) }}">
                                <i class="ti ti-check mr-1"></i> {{ __('db.Mark as Ready') }}
                            </button>
                            @endif

                            <button class="btn btn-info view-sale" data-toggle="modal" data-target="#get-sale-details" value="{{$sale->sale_id}}">
                                <i class="ti ti-eye mr-1"></i> {{ __('db.Order details') }}
                            </button>
                        </div>
                    </div>
                    @endforeach

                    @if ($salesData->where('kitchen_id', $kitchen->id)->isEmpty())
                    <div class="col-12">
                        <div style="height:50vh" class="d-flex justify-content-center align-items-center">
                            <div class="text-center text-muted">
                                <i class="ti ti-tools-kitchen-2 mb-3" style="font-size: 80px; color: #ccc;"></i>
                                <p style="font-size: 1.1rem;">{{ __('db.No active orders for this kitchen station') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        @endif

        @if(!empty($isAdmin) || !empty($isWaiter))
        <div class="tab-pane fade @if(empty($kitchen_list) || count($kitchen_list) == 0) show active @endif"
             id="ready-tab-pane"
             role="tabpanel"
             aria-labelledby="ready-tab">
            <div class="row">
                <!-- Ready for Service Cards -->
                @foreach($readySalesData as $readySale)
                @php
                    $sale_date = \Carbon\Carbon::parse($readySale->sale_date)->format($dateFormat);
                    $items = $readyItemsBySale[$readySale->sale_id] ?? collect();
                @endphp
                <div class="col-md-3 mt-4">
                    <div class="order-card" style="border: 2px solid #ffc107;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 text-dark">Order #{{ $readySale->sale_id }}</h5>
                            <span class="badge badge-warning text-dark">{{ __('db.Ready for Service') }}</span>
                        </div>
                        <p class="mb-1"><strong>{{ __('db.Placed at') }}:</strong> {{ $sale_date }} {{ \Carbon\Carbon::parse($readySale->sale_date)->format('H:i') }}</p>
                        <p class="mb-1"><strong>{{ __('db.Warehouse') }}:</strong> {{ $readySale->warehouse }}</p>
                        @if(!empty($readySale->table_name))<p class="mb-1"><strong>{{ __('db.Table') }}:</strong> <span class="badge badge-dark">{{ $readySale->table_name }}</span></p>@endif
                        @if(!empty($readySale->waiter_name))<p class="mb-1"><strong>{{ __('db.Waiter') }}:</strong> {{ $readySale->waiter_name }}</p>@endif
                        <p class="mb-2"><strong>{{ __('db.Customer') }}:</strong> {{ $readySale->customer_name }}</p>

                        <div class="alert alert-light p-2 mb-2 border">
                            <strong class="d-block mb-1 text-muted" style="font-size: 0.85em;">{{ __('db.Items') }}:</strong>
                            @foreach($items as $it)
                            <div class="mb-1" style="font-size: 0.95em;">
                                <strong>{{ (int)$it->qty }}x</strong> {{ $it->product_name }}
                                @if($it->modifier_labels)
                                <div class="text-muted small ml-3"><i class="ti ti-check-box"></i> {{ $it->modifier_labels }}</div>
                                @endif
                            </div>
                            @endforeach
                        </div>

                        <button class="btn btn-warning action-btn font-weight-bold" data-url="{{ route('restaurant.sale.status.served', $readySale->sale_id) }}">
                            <i class="ti ti-check mr-1"></i> {{ __('db.Mark as served') }}
                        </button>
                        <button class="btn btn-info view-sale" data-toggle="modal" data-target="#get-sale-details" value="{{$readySale->sale_id}}">
                            <i class="ti ti-eye mr-1"></i> {{ __('db.Order details') }}
                        </button>
                    </div>
                </div>
                @endforeach

                @if ($readySalesData->isEmpty())
                <div class="col-12">
                    <div style="height:50vh" class="d-flex justify-content-center align-items-center">
                        <div class="text-center text-muted">
                            <i class="ti ti-bell-off mb-3" style="font-size: 80px; color: #ccc;"></i>
                            <p style="font-size: 1.1rem;">{{ __('db.No orders currently ready for service') }}</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
@endif
