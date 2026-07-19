@php
    $modelName = class_basename($model);
    $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
    $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
    $title = ucfirst($operation).' '.$modelName;
    $route = model_route_name($model, $action);
@endphp

<x-modal-content :title="$title">
            @php
            use App\Facades\Form;

            $formRoute = in_array($verb, ['POST', 'post'], true)
                ? ['route' => $route]
                : ['route' => [$route, $dto->id]];
            $formOpenOptions = array_merge($formRoute, ['id' => 'modalForm', 'files' => true, 'method' => 'post', 'attributes' => ['data-modal-form' => 'true']]);
        @endphp

        {{ Form::open($formOpenOptions) }}
            <div class="">
                @if (! in_array($verb, ['POST', 'post'], true))
                    @method($verb)
                @endif

                @if ($dto->id ?? null)
                    {{ Form::hidden('id', $dto->id) }}
                @endif

                {{ Form::field('text', 'category', $dto->category ?? null, null, null) }}
                {{ Form::field('textarea', 'description', $dto->description ?? null, null, null) }}
            </div>
            <div class="flex justify-end gap-2.5 mt-5">
            <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        {{ Form::close() }}
</x-modal-content>
