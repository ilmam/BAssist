{{--
    Shell of a record view. One component for both containers so the title, header
    actions and footer cannot drift:
      full page  → card,  footer = Back to list + extra buttons
      pop-up     → modal, footer = previous/next + extra buttons + Close
    Usage: <x-record-view :model="$model" :dto="$dto" :in-modal="true"> body
           <x-slot:footer> extra buttons </x-slot:footer> </x-record-view>
--}}
@props([
    'model',
    'dto',
    'inModal' => false,
    'title' => null,
    'size' => 'full',
    'backUrl' => null,
    'backLabel' => null,
    'pageOnly' => false,
])
@php
    $modelName = class_basename($model);
    $resolvedTitle = $title ?? record_title($modelName, $dto);
@endphp
@if ($inModal)
    <x-modal-content :title="$resolvedTitle" :size="$size">
        <x-slot:actions>
            <x-record-actions :model="$modelName" :dto="$dto" :in-modal="true" />
        </x-slot:actions>

        {{ $slot }}

        <x-slot:footer>
            @include('pages.partials.modal-record-nav')
            {{ $footer ?? '' }}
            <x-modal-dismiss :text="__('ui.close')" />
        </x-slot:footer>
    </x-modal-content>
@else
    <x-card :title="$resolvedTitle">
        @isset($titleAside)
            <x-slot:titleAside>{{ $titleAside }}</x-slot:titleAside>
        @endisset
        <x-slot:toolbar>
            <x-record-actions :model="$modelName" :dto="$dto" :page-only="$pageOnly" />
        </x-slot:toolbar>

        {{ $slot }}

        <x-slot:footer>
            <x-button type="link" href="{{ $backUrl ?? model_route($modelName, 'index') }}" color="outline">{{ $backLabel ?? __('ui.back_to_list') }}</x-button>
            {{ $footer ?? '' }}
        </x-slot:footer>
    </x-card>
@endif
