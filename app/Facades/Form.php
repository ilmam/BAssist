<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\HtmlString open(array $options = [])
 * @method static \Illuminate\Support\HtmlString close()
 * @method static \Illuminate\Support\HtmlString label(string $name, ?string $value = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString text(string $name, $value = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString textarea(string $name, $value = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString select(string $name, array $list, $selected = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString checkbox(string $name, $value = 1, $checked = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString radio(string $name, $value = null, $checked = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString hidden(string $name, $value = null, array $options = [])
 * @method static \Illuminate\Support\HtmlString file(string $name, array $options = [])
 * @method static void component(string $name, string $view, array $params)
 *
 * @see \App\Support\FormBuilder
 */
class Form extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'form.builder';
    }
}
