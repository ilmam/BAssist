{{--
    Create / edit form as a Livewire component (App\Livewire\EntityForm).
    The fields are the normal <x-form> controls; livewire-forms.js sends the form to
    the component on Save and reports each field as the user leaves it.
--}}
<div>
    @if ($status)
        <div class="{{ ui_theme() === 'metronic8' ? 'alert alert-success mb-5' : 'kt-alert kt-alert-success mb-5' }}" role="status" data-auto-dismiss>{{ $status }}</div>
    @endif

    <x-form
        :id="$inModal ? 'modalForm' : 'form1'"
        :route="model_route_name($model, $recordId === null ? 'store' : 'update')"
        :verb="$recordId === null ? 'POST' : 'PUT'"
        :model="$model"
        :dto="$dto"
        :fields-array="$fields"
        :in-modal="$inModal"
        :livewire="true"
    />
</div>
