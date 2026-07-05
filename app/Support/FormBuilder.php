<?php

namespace App\Support;

use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;

class FormBuilder
{
    /** @var array<string, array{view: string, params: array<int, string>}> */
    protected array $components = [];

    public function open(array $options = []): HtmlString
    {
        $method = strtoupper($options['method'] ?? 'POST');
        $files = ! empty($options['files']);
        $attributes = $options['attributes'] ?? [];
        $action = $this->resolveAction($options);

        $id = $options['id'] ?? null;
        unset($options['method'], $options['files'], $options['attributes'], $options['route'], $options['url'], $options['id']);
        $attributes['method'] = $this->getMethod($method);
        $attributes['action'] = $action;

        if ($files) {
            $attributes['enctype'] = 'multipart/form-data';
        }

        if ($id) {
            $attributes['id'] = $id;
        }

        $html = '<form'.$this->attributes($attributes).'>';

        if ($method !== 'GET') {
            $html .= csrf_field();
        }

        if (! in_array($method, ['GET', 'POST'], true)) {
            $html .= method_field($method);
        }

        return new HtmlString($html);
    }

    public function close(): HtmlString
    {
        return new HtmlString('</form>');
    }

    public function label(string $name, ?string $value = null, array $options = []): HtmlString
    {
        $value = $value ?? ucfirst(str_replace('_', ' ', $name));

        return new HtmlString('<label for="'.$this->escape($name).'"'.$this->attributes($options).'>'.$this->escape($value).'</label>');
    }

    public function text(string $name, $value = null, array $options = []): HtmlString
    {
        return $this->input('text', $name, $value, $options);
    }

    public function textarea(string $name, $value = null, array $options = []): HtmlString
    {
        $options = $this->mergeName($name, $options);
        $value = $this->escape($value);

        return new HtmlString('<textarea'.$this->attributes($options).'>'.$value.'</textarea>');
    }

    public function select(string $name, array $list, $selected = null, array $options = []): HtmlString
    {
        $options = $this->mergeName($name, $options);
        $html = '<select'.$this->attributes($options).'>';

        foreach ($list as $value => $label) {
            $html .= '<option value="'.$this->escape($value).'"'.$this->selected($value, $selected).'>'.$this->escape($label).'</option>';
        }

        $html .= '</select>';

        return new HtmlString($html);
    }

    public function checkbox(string $name, $value = 1, $checked = null, array $options = []): HtmlString
    {
        if ($checked) {
            $options['checked'] = 'checked';
        }

        return $this->input('checkbox', $name, $value, $options);
    }

    public function radio(string $name, $value = null, $checked = null, array $options = []): HtmlString
    {
        if ($checked) {
            $options['checked'] = 'checked';
        }

        return $this->input('radio', $name, $value, $options);
    }

    public function hidden(string $name, $value = null, array $options = []): HtmlString
    {
        return $this->input('hidden', $name, $value, $options);
    }

    public function file(string $name, array $options = []): HtmlString
    {
        return $this->input('file', $name, null, $options);
    }

    public function component(string $name, string $view, array $params): void
    {
        $this->components[$name] = [
            'view' => $view,
            'params' => $params,
        ];
    }

    public function __call(string $method, array $arguments): HtmlString
    {
        if (! isset($this->components[$method])) {
            throw new \BadMethodCallException("Form component [{$method}] is not defined.");
        }

        $component = $this->components[$method];
        $data = [];

        foreach ($component['params'] as $index => $param) {
            $data[$param] = $arguments[$index] ?? null;
        }

        $data = array_merge($this->formFieldVars($data), $data);

        return new HtmlString(View::make(ui_form_view($component['view']), $data)->render());
    }

    protected function formFieldVars(array $data): array
    {
        $name = (string) ($data['name'] ?? '');
        $attributes = $data['attributes'] ?? [];

        return [
            'horizontal' => \App\Helpers\Ui::keyset($attributes, 'layout') === null || ($attributes['layout'] ?? 'h') === 'h',
            'labelText' => \App\Helpers\Ui::prettify(__('ui.'.$name)),
        ];
    }

    protected function input(string $type, string $name, $value = null, array $options = []): HtmlString
    {
        $options = $this->mergeName($name, $options);
        $options['type'] = $type;

        if (! in_array($type, ['file', 'checkbox', 'radio'], true)) {
            $options['value'] = $this->escape($value);
        } elseif ($type !== 'file') {
            $options['value'] = $this->escape($value);
        }

        return new HtmlString('<input'.$this->attributes($options).'>');
    }

    protected function resolveAction(array $options): string
    {
        if (isset($options['route'])) {
            $route = $options['route'];

            return is_array($route) ? route(...$route) : route($route);
        }

        if (isset($options['url'])) {
            return url($options['url']);
        }

        return url()->current();
    }

    protected function getMethod(string $method): string
    {
        return $method === 'GET' ? 'GET' : 'POST';
    }

    protected function mergeName(string $name, array $options): array
    {
        $options['name'] = $name;
        $options['id'] = $options['id'] ?? $name;

        return $options;
    }

    protected function attributes(array $attributes): string
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $html[] = $this->escape($key);
                continue;
            }

            $html[] = $this->escape($key).'="'.$this->escape($value).'"';
        }

        return $html ? ' '.implode(' ', $html) : '';
    }

    protected function selected($value, $selected): string
    {
        return (string) $value === (string) $selected ? ' selected' : '';
    }

    protected function escape($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
