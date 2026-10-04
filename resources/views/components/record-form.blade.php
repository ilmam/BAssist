{{--
    Shell of a custom create / edit form. One component for both containers, so an
    entity writes its fields once (pages/{entity}/partials/form-content.blade.php):
      full page → form card, Cancel returns to cancel-url
      pop-up    → modal, Cancel closes it, header carries the "open as page" permalink
    Slots: default = fields, after = content below the form (outside the <form>).
--}}
@props([
    'model',
    'dto' => null,
    'title',
    'formRoute',
    'inModal' => false,
    'size' => 'full',
    'cancelUrl' => null,
])
@php
    $modelName = class_basename($model);
    $cancelUrl = $cancelUrl ?: model_route($modelName, 'index');
    $hasAfter = isset($after) && trim((string) $after) !== '';
@endphp
@if ($inModal)
    <x-modal-content :title="$title" :size="$size">
        <x-slot:actions>
            <x-open-as-page :href="record_form_page_url($modelName, $dto)" />
        </x-slot:actions>

        {{ \App\Facades\Form::open(array_merge($formRoute, [
            'id' => 'modalForm',
            'files' => true,
            'method' => 'post',
            'attributes' => ['data-modal-form' => 'true'],
        ])) }}
            <div class="space-y-6">
                {{ $slot }}
            </div>

            <div class="flex justify-end gap-2.5 mt-5">
                <x-button type="button" color="outline" data-kt-modal-dismiss="true">{{ __('ui.cancel') }}</x-button>
                <x-button type="submit" color="primary">{{ __('ui.save') }}</x-button>
            </div>
        {{ \App\Facades\Form::close() }}

        @if ($hasAfter)
            <div class="mt-6 border-t border-border pt-6">{{ $after }}</div>
        @endif
    </x-modal-content>
@else
    <x-form-card :title="$title">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $cancelUrl }}" icon="arrow-left" iconOnly="true" color="ghost" size="sm" activeColor="primary"></x-button>
        </x-slot>

        {{ \App\Facades\Form::open(array_merge($formRoute, ['id' => 'form1', 'files' => true, 'method' => 'post'])) }}
            <x-form-card-body class="space-y-8" data-ui-container>
                {{ $slot }}
            </x-form-card-body>

            <x-form-card-footer>
                <x-button type="link" href="{{ $cancelUrl }}" color="outline">{{ __('ui.cancel') }}</x-button>
                <x-button type="submit" color="primary">{{ __('ui.save') }}</x-button>
            </x-form-card-footer>
        {{ \App\Facades\Form::close() }}

        @if ($hasAfter)
            <x-form-card-body>{{ $after }}</x-form-card-body>
        @endif
    </x-form-card>
@endif
