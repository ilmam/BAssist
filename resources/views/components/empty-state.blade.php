{{--
    Teaching empty state: what this is, why it matters, what to do next.
    <x-empty-state icon="people" :title="__('ui.empty_title', ['entity' => 'Stakeholders'])" :hint="$hint">
        <x-slot:actions> … buttons … </x-slot:actions>
    </x-empty-state>
--}}
@props(['icon' => 'questionnaire-tablet', 'title' => null, 'hint' => null, 'compact' => false])

<div {{ $attributes->class(['ba-empty', 'ba-empty--compact' => $compact]) }}>
    <div class="ba-empty__icon" aria-hidden="true"><i class="ki-filled ki-{{ $icon }}"></i></div>
    @if ($title)
        <div class="ba-empty__title">{{ $title }}</div>
    @endif
    @if ($hint)
        <p class="ba-empty__hint">{{ $hint }}</p>
    @endif
    {{ $slot }}
    @isset($actions)
        <div class="ba-empty__actions">{{ $actions }}</div>
    @endisset
</div>
