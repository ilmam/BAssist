@extends(ui_layout())

@section('main')
    @php
        $modelName = class_basename($model);
        $verb = in_array($operation, ['insert', 'create'], true) ? 'POST' : 'PUT';
        $action = in_array($operation, ['insert', 'create'], true) ? 'store' : 'update';
        $title = ucfirst($operation).' '.$modelName;
        $route = model_route_name($model, $action);
        $cancelRoute = model_route_name($model, 'index');
    @endphp

    <x-form-card :title="$title">
        <x-slot:toolbar>
            <x-button type="link" href="{{ $cancelRoute }}" icon="arrow-left" iconOnly="true" color="light" activeColor="primary"></x-button>
        </x-slot:toolbar>
        @php
            use App\Facades\Form;

            $formRoute = in_array($verb, ['POST', 'post'], true)
                ? ['route' => $route]
                : ['route' => [$route, $dto->id]];
            $formOpenOptions = array_merge($formRoute, ['id' => 'form1', 'files' => true, 'method' => 'post']);
        @endphp

        {{ Form::open($formOpenOptions) }}
            <div class="kt-card-body border-t border-border p-5 lg:p-7.5">
                @if (! in_array($verb, ['POST', 'post'], true))
                    @method($verb)
                @endif

                @if ($dto->id ?? null)
                    {{ Form::hidden('id', $dto->id) }}
                @endif

                {{ Form::field('text', 'category', $dto->category ?? null, null, null) }}
                {{ Form::field('textarea', 'description', $dto->description ?? null, null, null) }}
            </div>
            <div class="kt-card-footer flex justify-end gap-2.5 border-t border-border p-5 lg:p-7.5">
            <x-button type="link" href="{{ $cancelRoute }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">Save</x-button>
            </div>
        {{ Form::close() }}
    </x-form-card>
@endsection
