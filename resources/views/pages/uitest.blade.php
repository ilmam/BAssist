@extends(ui_layout())

@section('main')
        @php
            //edit
            $route = 'test';
            $method = "GET";
            $id = "id";
            $fields = $formFields ?? [];
        @endphp
        {{-- @include('partial/form') --}}
        <x-form :route="$route" :id="$id" :method="$method" :fields="$fields"></x-form>
        @php


            //list
            $data = $dto->getFields(withPrefix: true, onlyHeaders: false);
            $columns = array_keys($data);


            $model = 'Post';

            $additionalParameters = $children = [];

            $modelName = class_basename($model);

            $name = strtolower($modelName);

            $routeParameters = [];
            $routeParameters["modelName"]=$modelName;
            foreach($additionalParameters as $param=>$value) {
                $routeParameters['model'] = $param;
                $routeParameters['value'] = $value;
            }

            //extra columns
            // foreach($children as $child) {
            //     $columns[] = [
            //         'custom'=>true, 'name'=>$child, 'style'=>'width: 100px',
            //         'buttons'=>[
            //             ['action'=>'link', 'icon'=>'fas fa-list', 'link'=>"/admin/$modelName/{id}/$child", 'text'=>__("ui.".$child."s"), 'class'=>'btn btn-sm btn-primary'],
            //         ]
            //     ];
            // }
            // $columns[] = [
            //     'custom'=>true, 'name'=>'actions', 'style'=>'width: 100px',
            //     'buttons'=>[
            //         ['action'=>'edit', 'icon'=>'la la-pencil', 'link'=>"/admin/$modelName/{id}/edit"],
            //         ['action'=>'delete', 'icon'=>'la la-trash', 'link'=>"/admin/$modelName/{id}/delete"],
            //     ]
            // ];
            $options = [
                'columns'=>$columns,
                'exclude' => ['category.description'],
                'keys'=>['id','id'],
                'tableClass'=>'table-hover table-striped',
                'dataRoute'=>"api.$model.index",
                "modelName"=>$name,
                'dataRoutParameters'=>$routeParameters
            ];

            $placeholder = ['placeholder'=>'Search..'];

        @endphp

        <x-card class="" id="" title="Hello World">
            <x-slot:toolbar>
                @@
            </x-slot>
            <x-slot:footer>
                ------------
            </x-slot>
            <x-datatable
                :options="$options"
            ></x-datatable>
        </x-card>

@endsection