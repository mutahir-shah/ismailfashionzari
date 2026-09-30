@php
    $toast = session('toast_notify');
    if (!$toast) return;

    $type = $toast['type'] ?? 'success';

    $bgClass = match($type) {
        'success' => 'bg-success text-white',
        'error' => 'bg-danger text-white',
        'warning' => 'bg-warning text-dark',
        default => 'bg-primary text-white',
    };
@endphp

<div id="toast-component"
     class="shadow rounded {{ $bgClass }} mx-auto mb-3"
     style="width: 100%; max-width: 600px;">

    <div class="d-flex align-items-center justify-content-between px-3 py-2">

        <div class="flex-grow-1 fs-6">
            {{ $toast['message'] }}

            @if(!empty($toast['action']))
                <a href="{{ $toast['action']['url'] }}"
                   class="text-secondary fw-bold ms-2 link-underline-warning">
                    {{ $toast['action']['label'] }}
                </a>
            @endif
        </div>

        <div class="d-flex justify-content-end">
            <button type="button"
                    onclick="closeToast()"
                    class="btn text-dark border-0 shadow-none p-0 ms-3"
                    style="font-size: 14px;">
                ✕
            </button>
        </div>

    </div>
</div>

<script>
    function closeToast() {
        const toast = document.getElementById('toast-component');

        if (!toast) return;

        toast.style.transition = 'opacity 0.3s ease';
        toast.style.opacity = 0;

        setTimeout(() => {
            toast.remove();
        }, 300);
    }
</script>