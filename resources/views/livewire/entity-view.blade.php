{{-- Details of a record in the view pop-up (App\Livewire\EntityView); refreshes when the record changes. --}}
<div wire:poll.20s="poll">
    @if ($dto)
        <x-details-view :model="$model" :dto="$dto" :fields="$fields" />
    @else
        <div class="{{ ui_theme() === 'metronic8' ? 'alert alert-warning' : 'kt-alert kt-alert-warning' }}" role="alert">{{ __('livewire.record_gone') }}</div>
    @endif
</div>
