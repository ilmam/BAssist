@extends(ui_layout())

@section('main')
    <div class="space-y-5">
        <x-card title="{{ $screen->title }}">
            @if ($screen->realizes)
                <p class="text-sm text-muted-foreground mb-5">{{ __('ui.screen_realizes') }}: <strong>{{ $screen->realizes }}</strong></p>
            @endif

            @include('pages.screens.partials.mockup', ['salt' => $salt])
        </x-card>
    </div>
@endsection
