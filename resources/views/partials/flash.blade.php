@if (session('error'))
    <div class="flash flash--error" role="alert">
        <span class="flash__icon" aria-hidden="true"></span>
        <div class="flash__body">{{ session('error') }}</div>
    </div>
@endif

@if (session('success'))
    <div class="flash flash--success" role="status">
        <span class="flash__icon" aria-hidden="true"></span>
        <div class="flash__body">{{ session('success') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="flash flash--error" role="alert">
        <span class="flash__icon" aria-hidden="true"></span>
        <div class="flash__body">
            @if ($errors->count() === 1)
                {{ $errors->first() }}
            @else
                <ul class="flash__list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
