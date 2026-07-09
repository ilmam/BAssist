@if (session('status'))
    <div class="kt-alert kt-alert-success mb-5">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="kt-alert kt-alert-destructive mb-5">
        <ul class="list-disc ps-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
