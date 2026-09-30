@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<style>
    .sc-catalog-head { gap: 12px; flex-wrap: wrap; }
    .sc-catalog-head h4 { margin: 0; font-size: 1.1rem; font-weight: 600; }
    .sc-catalog-filter { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .sc-product-cell { min-width: 220px; }
    .sc-product-name { font-weight: 600; color: #34395e; }
    .sc-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #eef0f4; background: #f8f9fa; }
    .sc-link-row { min-width: 260px; }
    .sc-share-actions { display: flex; gap: 4px; flex-wrap: wrap; }
    .sc-share-actions .btn, .sc-publish-actions .btn { min-width: 38px; }
    .sc-publish-actions form { margin: 0; }
    .sc-empty { padding: 28px 12px; color: #6c757d; }
    @media (max-width: 767px) {
        .sc-catalog-filter, .sc-catalog-filter select { width: 100%; }
    }
</style>

<section>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center sc-catalog-head">
                        <h4>{{ __('db.Social Commerce Catalog') }}</h4>
                        <form action="{{ route('socialcommerce.catalog') }}" method="GET" class="sc-catalog-filter">
                            <label class="mb-0">{{ __('db.Filter') }}:</label>
                            <select name="filter" class="form-control" onchange="this.form.submit()">
                                <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>{{ __('db.All Active Products') }}</option>
                                <option value="published" {{ $filter == 'published' ? 'selected' : '' }}>{{ __('db.Published') }}</option>
                                <option value="unpublished" {{ $filter == 'unpublished' ? 'selected' : '' }}>{{ __('db.Not Published') }}</option>
                                <option value="missing_image" {{ $filter == 'missing_image' ? 'selected' : '' }}>{{ __('db.Missing Image') }}</option>
                                <option value="out_of_stock" {{ $filter == 'out_of_stock' ? 'selected' : '' }}>{{ __('db.Out of Stock') }}</option>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.Product Name') }}</th>
                                        <th>{{ __('db.Code/SKU') }}</th>
                                        <th>{{ __('db.Price') }}</th>
                                        <th>{{ __('db.Stock') }}</th>
                                        <th>{{ __('db.Social Status') }}</th>
                                        <th>{{ __('db.Public Link') }}</th>
                                        <th>{{ __('db.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        @php
                                            $isPublished = in_array($product->id, $publishedIds);
                                            $scSetting = $settings->get($product->id);
                                            $hasImage = !empty($product->image) && $product->image !== 'zummXD2dvAtI.png';
                                            $isValid = $hasImage && $product->price > 0 && !empty($product->name);
                                        @endphp
                                        <tr>
                                            <td class="sc-product-cell">
                                                @if($hasImage)
                                                    @php $images = explode(',', $product->image); @endphp
                                                    <img src="{{ asset('images/product/'.$images[0]) }}" class="sc-thumb mr-2" alt="{{ $product->name }}">
                                                @else
                                                    <span class="badge badge-warning mr-2">{{ __('db.No Image') }}</span>
                                                @endif
                                                <span class="sc-product-name">{{ $product->name }}</span>
                                            </td>
                                            <td>{{ $product->code }}</td>
                                            <td>{{ number_format((float)$product->price, config('decimal', 2), '.', '') }}</td>
                                            <td>
                                                @if($product->qty > 0)
                                                    <span class="badge badge-success">{{ $product->qty }} {{ __('db.in stock') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('db.Out of Stock') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($isPublished)
                                                    <span class="badge badge-success mb-2 d-inline-block">{{ __('db.Published') }}</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ __('db.Not Published') }}</span>
                                                @endif
                                                @if(!$isValid)
                                                    <br><small class="text-danger">{{ __('db.Missing requirements') }}</small>
                                                @endif
                                            </td>
                                            <td class="sc-link-row">
                                                @if($isPublished)
                                                    @php
                                                        $publicLink = $product->social_commerce_url;
                                                        $shareText = ($scSetting->social_title ?: $product->name) . ' - ' . ($scSetting->social_description ?: strip_tags($product->product_details));
                                                        $whatsAppUrl = 'https://wa.me/?' . http_build_query(['text' => $shareText . ' ' . $publicLink], '', '&', PHP_QUERY_RFC3986);
                                                        $facebookUrl = 'https://www.facebook.com/sharer/sharer.php?' . http_build_query(['u' => $publicLink], '', '&', PHP_QUERY_RFC3986);
                                                        $telegramUrl = 'https://t.me/share/url?' . http_build_query(['url' => $publicLink, 'text' => $shareText], '', '&', PHP_QUERY_RFC3986);
                                                    @endphp
                                                    <div class="input-group input-group-sm mb-2">
                                                        <input type="text" class="form-control" value="{{ $publicLink }}" readonly aria-label="{{ __('db.Public Link') }}">
                                                            <button class="btn btn-outline-secondary sc-copy-btn" type="button" title="{{ __('db.Copy link') }}" aria-label="{{ __('db.Copy link') }}" data-url="{{ $publicLink }}">
                                                                <i class="ti ti-copy"></i>
                                                            </button>
                                                    </div>
                                                    <div class="sc-share-actions">
                                                        <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-success" title="{{ __('db.Share on WhatsApp') }}"><i class="ti ti-brand-whatsapp"></i></a>
                                                        <a href="{{ $facebookUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary" title="{{ __('db.Share on Facebook') }}"><i class="ti ti-brand-facebook"></i></a>
                                                        <a href="{{ $telegramUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-info" title="{{ __('db.Share on Telegram') }}"><i class="ti ti-brand-telegram"></i></a>
                                                        <button type="button" class="btn btn-sm btn-dark sc-qr-btn" data-url="{{ route('socialcommerce.qr', $product->id) }}" title="{{ __('db.View QR Code') }}" aria-label="{{ __('db.View QR Code') }}"><i class="ti ti-qrcode"></i></button>
                                                        <a href="{{ $publicLink }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="{{ __('db.View product') }}"><i class="ti ti-external-link"></i></a>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="sc-publish-actions">
                                                @if($isPublished)
                                                    <form action="{{ route('socialcommerce.togglePublish', $product->id) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="unpublish" value="1">
                                                        <button type="submit" class="btn btn-sm btn-danger">{{ __('db.Unpublish') }}</button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('socialcommerce.togglePublish', $product->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary" {{ !$isValid ? 'disabled' : '' }}>{{ __('db.Publish') }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">{{ __('db.No products found for this filter') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            {{ $products->appends(['filter' => $filter])->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.sc-copy-btn');
        if (btn) {
            copySocialCommerceUrl(btn);
            return;
        }

        var qrBtn = e.target.closest('.sc-qr-btn');
        if (qrBtn && qrBtn.dataset.url) {
            window.open(qrBtn.dataset.url, '_blank', 'noopener,width=400,height=400');
        }
    });

    function copySocialCommerceUrl(btn) {
        
        var url = btn.getAttribute('data-url');
        if (!url) return;
        
        var successMsg = @json(__('db.Link copied'));
        var errorMsg = @json(__('db.Unable to copy link'));
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(function() {
                alert(successMsg);
            }).catch(function(err) {
                fallbackCopyTextToClipboard(url, successMsg, errorMsg);
            });
        } else {
            fallbackCopyTextToClipboard(url, successMsg, errorMsg);
        }
    }

    function fallbackCopyTextToClipboard(text, successMsg, errorMsg) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                alert(successMsg);
            } else {
                alert(errorMsg);
            }
        } catch (err) {
            alert(errorMsg);
        }
        document.body.removeChild(textArea);
    }
</script>
@endpush
@endsection
