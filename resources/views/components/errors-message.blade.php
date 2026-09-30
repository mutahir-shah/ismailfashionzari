@props(['key'])

@if(session()->has($key))
    <div class="alert alert-danger alert-dismissible text-center">
        <button type="button" class="close" data-dismiss="alert">&times;</button>

        @php
            $messages = session($key);
        @endphp

        @if(is_array($messages))
            <ul class="mb-0 text-left">
                @foreach(array_slice($messages, 0, 5) as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>

            @if(count($messages) > 5)
                <small class="text-muted">
                    Showing first 5 errors out of {{ count($messages) }}
                </small>
            @endif
        @else
            {{ $messages }}
        @endif

    </div>
@endif