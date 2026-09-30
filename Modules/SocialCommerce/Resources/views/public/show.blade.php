<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    
    <!-- Open Graph Metadata -->
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ Str::limit($description, 150) }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:url" content="{{ request()->url() }}">
    
    <link rel="canonical" href="{{ request()->url() }}">

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org/",
      "@@type": "Product",
      "name": "{{ $title }}",
      "image": "{{ $image }}",
      "description": "{{ Str::limit(strip_tags($description), 200) }}",
      "sku": "{{ $product->code }}",
      "offers": {
        "@@type": "Offer",
        "url": "{{ request()->url() }}",
        "priceCurrency": "{{ config('currency_code', 'USD') }}",
        "price": "{{ $price }}",
        "availability": "{{ $product->qty > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}"
      }
    }
    </script>

    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <style>
        body { background-color: #f5f6f8; font-family: Arial, sans-serif; color: #23272b; }
        .product-card { max-width: 560px; margin: 24px auto; background: #fff; border-radius: 8px; border: 1px solid #e9ecef; box-shadow: 0 10px 30px rgba(20, 25, 35, 0.08); overflow: hidden; }
        .product-image-wrap { background: #f8f9fa; aspect-ratio: 1 / 1; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .product-image { width: 100%; height: 100%; object-fit: contain; display: block; }
        .product-info { padding: 24px; }
        .product-title { font-size: 1.45rem; font-weight: 700; line-height: 1.25; margin-bottom: 10px; }
        .product-price { font-size: 1.25rem; color: #28a745; font-weight: 700; margin-bottom: 12px; }
        .product-stock { font-size: 0.9rem; margin-bottom: 14px; font-weight: 600; }
        .product-description { font-size: 0.95rem; color: #606975; margin-bottom: 22px; line-height: 1.6; white-space: pre-line; }
        .btn-whatsapp { background-color: #25D366; color: #fff; font-weight: 600; padding: 12px 20px; border-radius: 8px; width: 100%; text-align: center; display: block; text-decoration: none; transition: background-color 0.2s; margin-bottom: 10px; }
        .btn-whatsapp:hover { background-color: #128C7E; color: #fff; text-decoration: none; }
        .btn-order { background-color: #007bff; color: #fff; font-weight: 600; padding: 12px 20px; border-radius: 8px; width: 100%; text-align: center; display: block; border: none; }
        .btn-order:hover { background-color: #0069d9; color: #fff; }
        .checkout-section { display: {{ $errors->any() || session('error') ? 'block' : 'none' }}; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .checkout-section h5 { font-size: 1rem; font-weight: 700; }
        .form-control { border-radius: 6px; }
        @media (max-width: 575px) {
            .container { padding-left: 0; padding-right: 0; }
            .product-card { margin: 0; min-height: 100vh; border-radius: 0; border-left: 0; border-right: 0; }
            .product-info { padding: 18px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="product-card">
            <div class="product-image-wrap">
                <img src="{{ $image }}" alt="{{ $title }}" class="product-image">
            </div>
            <div class="product-info">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h1 class="product-title">{{ $title }}</h1>
                <div class="product-price">{{ config('currency') }} {{ number_format($price, 2) }}</div>
                
                @if($product->qty > 0)
                    <div class="product-stock text-success">{{ __('db.In Stock') }}</div>
                @else
                    <div class="product-stock text-danger">{{ __('db.Out of Stock') }}</div>
                @endif

                <div class="product-description">
                    {{ $description }}
                </div>
                
                @if($settings->whatsapp_number)
                    <a href="{{ $whatsappLink }}" class="btn-whatsapp" target="_blank">
                        {{ __('db.Order via WhatsApp') }}
                    </a>
                @endif

                @if(isset($canPurchaseOnline) && $canPurchaseOnline && isset($ecommerceUrl))
                    <a href="{{ $ecommerceUrl }}" class="btn-order mb-3" style="background-color: #17a2b8; text-decoration: none;">
                        {{ __('db.Buy online') }}
                    </a>
                @endif

                @if($product->qty > 0)
                    <button type="button" class="btn-order" style="display: {{ $errors->any() || session('error') ? 'none' : 'block' }};" onclick="document.getElementById('checkout-form').style.display='block'; this.style.display='none';">
                        {{ __('db.Buy Now') }}
                    </button>
                    
                    <div class="checkout-section" id="checkout-form">
                        <h5 class="mb-3">{{ __('db.Complete Order (Cash on Delivery)') }}</h5>
                        <form action="{{ route('socialcommerce.public.order', $product->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="source" value="{{ request()->query('source', 'direct') }}">
                            <input type="hidden" name="campaign" value="{{ request()->query('campaign', '') }}">
                            
                            <div class="form-group mb-3">
                                <label>{{ __('db.name') }} *</label>
                                <input type="text" name="customer_name" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('db.Phone') }} *</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('db.Delivery Address') }}</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label>{{ __('db.Quantity') }}</label>
                                <input type="number" name="qty" class="form-control" value="1" min="1" max="{{ $product->qty }}" required>
                            </div>
                            <div class="form-group mb-4">
                                <label>{{ __('db.Order Note (Optional)') }}</label>
                                <input type="text" name="note" class="form-control">
                            </div>
                            <button type="submit" class="btn-order">{{ __('db.Submit Order') }}</button>
                        </form>
                    </div>
                @endif
                
                <div class="text-center mt-4 pt-3 border-top">
                    <small class="text-muted">{{ __('db.Powered by SalePro') }}</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
