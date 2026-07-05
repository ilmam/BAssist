@extends(ui_layout())

@section('main')
    <x-card title="Theme Shell Test ({{ ui_theme() }})">
        <p class="mb-5">
            Active theme: <strong>{{ ui_theme() }}</strong>.
            Switch via <code>UI_THEME=metronic8</code> or <code>UI_THEME=metronic9</code> in your <code>.env</code> file.
        </p>

        <div class="mb-5">
            <x-button color="primary">Primary</x-button>
            <x-button color="secondary">Secondary</x-button>
            <x-button icon="plus" iconOnly="true" color="danger"></x-button>
        </div>

        <x-alert>
            Your pages and components use the same Blade API regardless of which Metronic theme is active.
        </x-alert>
    </x-card>
@endsection
