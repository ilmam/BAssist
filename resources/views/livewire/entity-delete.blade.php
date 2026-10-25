{{-- Delete confirmation as a Livewire component (App\Livewire\EntityDelete). --}}
<div>
    <div class="space-y-6">
        @if ($error)
            <div class="{{ ui_theme() === 'metronic8' ? 'alert alert-danger' : 'kt-alert kt-alert-destructive' }}" role="alert">{{ $error }}</div>
        @endif

        @if ($dto)
            <p class="text-sm text-secondary-foreground">{{ __('livewire.delete_confirm') }}</p>

            <x-details-view :model="$model" :dto="$dto" :fields="$fields" />
        @endif
    </div>

    <div class="flex justify-end gap-2.5 mt-5">
        <x-modal-dismiss :text="$dto ? 'Cancel' : 'Close'" />
        @if ($dto)
            <x-button type="button" color="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete</x-button>
        @endif
    </div>
</div>
