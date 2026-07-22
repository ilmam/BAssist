<?php

namespace App\Providers;

use App\Support\FormBuilder;
use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('form.builder', fn () => new FormBuilder);
    }

    public function boot(): void
    {
        $form = $this->app->make('form.builder');

        $form->component('bsText', 'text', ['name', 'value', 'attributes']);
        $form->component('bsSelect', 'select', ['name', 'value', 'list', 'attributes']);
        $form->component('bsKtSelect', 'kt-select', ['name', 'value', 'list', 'attributes']);
        $form->component('bsCheckbox', 'checkbox', ['name', 'value', 'list', 'attributes']);
        $form->component('bsRadio', 'radio', ['name', 'value', 'list', 'attributes']);
        $form->component('bsTextarea', 'textarea', ['name', 'value', 'attributes']);
        $form->component('bsFile', 'file', ['name', 'value', 'attributes']);
        $form->component('bsTree', 'tree', ['name', 'value', 'textValue', 'attributes']);
        $form->component('bsImage', 'image', ['name', 'path', 'file', 'attributes']);
        $form->component('bsDropzone', 'dropzone', ['name', 'path', 'file', 'attributes']);
        $form->component('field', 'field', ['type', 'name', 'value', 'list', 'attributes']);
    }
}
