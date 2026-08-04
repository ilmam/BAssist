<?php
namespace App\Helpers;

class Ui
{

    public static function varset($key, $defaultValue=null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            if ($defaultValue != null) {
                return $defaultValue;
            } else {
                return null;
            }
        }
    }

    public static function keyset($array, $key, $defaultValue=null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            if ($defaultValue != null) {
                return $defaultValue;
            } else {
                return null;
            }
        }
    }

    /**
     * Format strings with default fixes for common cases
     */
    public static function prettify($string)
    {
        //if labels doesn't have trnaslation, just remove prefix 'ui.'
        if (substr($string, 0, 3) === "ui.") {
            $string = substr($string, 3);
        }
        return $string;
    }

    /**
     * Resolve a friendly field label via lang/ui.php, with sensible fallbacks.
     *
     * Examples:
     *  - project_id  → ui.project → "Project"
     *  - status.name → ui.status → "Status"
     *  - need_type   → ui.need_type or "Need Type"
     */
    public static function fieldLabel(string $name): string
    {
        $key = self::labelKey($name);

        if (\Illuminate\Support\Facades\Lang::has('ui.'.$key)) {
            return (string) __('ui.'.$key);
        }

        $pretty = self::prettify('ui.'.$key);

        return ucwords(str_replace(['_', '-'], ' ', $pretty));
    }

    /**
     * Normalize a field/path name into a lang/ui.php lookup key.
     */
    public static function labelKey(string $name): string
    {
        $key = $name;

        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $last = end($parts);

            // Relation display paths like status.name → label the relation.
            if (in_array($last, ['name', 'title', 'category', 'label'], true) && count($parts) >= 2) {
                $key = $parts[count($parts) - 2];
            } else {
                $key = $last;
            }
        }

        if (str_ends_with($key, '_id')) {
            $key = substr($key, 0, -3);
        }

        return $key;
    }


    public static function cleanText($string)
    {
        $chars = ['.', ',', ';', ':', '-', '_', '#', '(',  ')', '[', ']', "'", '"'];
        foreach ($chars as $char) {
            $string = str_replace($char, " ", $string);
        }
        return $string;
    }


    public static function toArray($string, $separator)
    {
        $array = explode($separator, $string);
        foreach($array as $i=>$item) {
            $item = trim($item);
            if ($item == '') {
                unset($array[$i]);
            }
        }
        return $array;
    }

    public static function left($string, $length)
    {
        if (strlen($string) > $length) {
            $newstr = mb_substr($string, 0, $length);
            //return $string;
            return $newstr;
        } else {
            return $string;
        }
    }

    public static function TableActionCol($options)
    {
        $colValue = '';
        $theme = function_exists('ui_theme') ? ui_theme() : 'metronic8';
        $wrapperClass = $theme === 'metronic9' ? 'flex items-center gap-1' : '';
        $openWrapper = $wrapperClass ? '<div class="'.$wrapperClass.'">' : '';
        $closeWrapper = $wrapperClass ? '</div>' : '';

        foreach ($options as $option) {
            if (! isset($option)) {
                continue;
            }

            if (self::keyset($option, 'menu') || is_array($option['menuItems'] ?? null)) {
                $colValue .= self::tableActionSplitButton($option, $theme);
                continue;
            }

            $colValue .= self::tableActionButton($option, $theme);
        }

        return $openWrapper.$colValue.$closeWrapper;
    }

    protected static function tableActionButton(array $option, string $theme): string
    {
        $link = self::keyset($option, 'link');
        $text = self::keyset($option, 'text');
        $icon = self::keyset($option, 'icon');
        $showText = self::keyset($option, 'showText', true);
        $modalUrl = self::keyset($option, 'modalUrl');
        $action = self::keyset($option, 'action');
        $target = self::keyset($option, 'target');
        $title = self::keyset($option, 'title');
        $actionAttr = $action ? ' data-action="'.e($action).'"' : '';
        $targetAttr = $target ? ' target="'.e($target).'" rel="noopener"' : '';
        $titleAttr = $title ? ' title="'.e($title).'"' : '';
        $colValue = '';

        if ($theme === 'metronic9') {
            $buttonClass = self::keyset($option, 'buttonClass', 'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost');
            $iconClass = self::tableActionIcon($icon, $theme);

            if ($modalUrl) {
                $colValue .= '<a href="'.$link.'" class="'.$buttonClass.' js-open-modal" data-modal-url="'.e($modalUrl).'"'.$actionAttr.$targetAttr.$titleAttr.'>';
            } else {
                $colValue .= '<a href="'.$link.'" class="'.$buttonClass.'"'.$actionAttr.$targetAttr.$titleAttr.'>';
            }
            if ($iconClass) {
                $colValue .= '<i class="'.$iconClass.'"></i>';
            }
        } else {
            $buttonClass = self::keyset($option, 'buttonClass', 'btn btn-sm btn-icon btn-light btn-active-light-primary h-25px w-25px');
            $iconClass = self::tableActionIcon($icon, $theme);

            if ($modalUrl) {
                $colValue .= '<a href="'.$link.'" class="'.$buttonClass.' js-open-modal" data-modal-url="'.e($modalUrl).'"'.$actionAttr.$targetAttr.$titleAttr.'>';
            } else {
                $colValue .= '<a href="'.$link.'" class="'.$buttonClass.'"'.$actionAttr.$targetAttr.$titleAttr.'>';
            }
            if ($iconClass) {
                $colValue .= '<i class="'.$iconClass.'"></i>';
            }
        }

        if ($showText) {
            $colValue .= $text;
        }

        return $colValue.'</a>';
    }

    protected static function tableActionSplitButton(array $option, string $theme): string
    {
        $link = self::keyset($option, 'link');
        $icon = self::keyset($option, 'icon');
        $modalUrl = self::keyset($option, 'modalUrl');
        $target = self::keyset($option, 'target');
        $title = self::keyset($option, 'title');
        $menuItems = is_array($option['menuItems'] ?? null) ? $option['menuItems'] : null;
        $iconClass = self::tableActionIcon($icon, $theme);
        $splitClass = self::keyset($option, 'splitClass', $menuItems !== null ? 'action-split-btn' : '');
        $menuAriaLabel = $title !== '' ? $title : 'More actions';
        $primaryModalClass = $modalUrl ? ' js-open-modal' : '';
        $primaryModalAttr = $modalUrl ? ' data-modal-url="'.e($modalUrl).'"' : '';
        $targetAttr = $target ? ' target="'.e($target).'" rel="noopener"' : '';
        $titleAttr = $title ? ' title="'.e($title).'"' : '';
        $wrapperClass = trim('inline-flex items-center '.($splitClass !== '' ? $splitClass : ''));

        if ($theme === 'metronic9') {
            $buttonClass = self::keyset($option, 'buttonClass', 'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost');
            $menuMinWidth = self::keyset($option, 'menuMinWidth', $menuItems !== null ? 'min-w-[240px]' : 'min-w-[160px]');

            // KTUI dropdown API (not Metronic 8 data-kt-menu). Rows are injected by
            // DataTables, so template JS re-inits KTDropdown after each draw.
            $menuHtml = $menuItems !== null
                ? self::tableActionMenuItemsHtml($menuItems, $theme)
                : '<a href="'.e($modalUrl).'" class="kt-dropdown-menu-link js-open-modal" data-modal-url="'.e($modalUrl).'" data-kt-dropdown-dismiss="true">Open</a>'
                    .'<a href="'.$link.'" class="kt-dropdown-menu-link" target="_blank" rel="noopener" data-kt-dropdown-dismiss="true">Open in new page</a>';

            return '<div class="'.$wrapperClass.'">'
                .'<a href="'.$link.'" class="'.$buttonClass.$primaryModalClass.'"'.$primaryModalAttr.$targetAttr.$titleAttr.'>'
                .($iconClass ? '<i class="'.$iconClass.'"></i>' : '')
                .'</a>'
                .'<div class="inline-flex" data-kt-dropdown="true" data-kt-dropdown-trigger="click">'
                .'<button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-dropdown-toggle="true" aria-label="'.e($menuAriaLabel).'">'
                .'<i class="ki-filled ki-down text-xs"></i>'
                .'</button>'
                .'<div class="kt-dropdown-menu '.$menuMinWidth.'" data-kt-dropdown-menu="true">'
                .$menuHtml
                .'</div>'
                .'</div>'
                .'</div>';
        }

        $buttonClass = self::keyset($option, 'buttonClass', 'btn btn-sm btn-icon btn-light btn-active-light-primary h-25px w-25px');
        $menuHtml = $menuItems !== null
            ? self::tableActionMenuItemsHtml($menuItems, $theme)
            : '<li><a class="dropdown-item js-open-modal" data-modal-url="'.e($modalUrl).'" href="'.e($modalUrl).'">Open</a></li>'
                .'<li><a class="dropdown-item" href="'.$link.'" target="_blank" rel="noopener">Open in new page</a></li>';

        return '<div class="btn-group'.($splitClass !== '' ? ' '.$splitClass : '').'">'
            .'<a href="'.$link.'" class="'.$buttonClass.$primaryModalClass.'"'.$primaryModalAttr.$targetAttr.$titleAttr.'>'
            .($iconClass ? '<i class="'.$iconClass.'"></i>' : '')
            .'</a>'
            .'<button type="button" class="'.$buttonClass.' dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" aria-label="'.e($menuAriaLabel).'"></button>'
            .'<ul class="dropdown-menu dropdown-menu-end">'
            .$menuHtml
            .'</ul>'
            .'</div>';
    }

    /**
     * @param  list<array{label: string, link: string, target?: string, modalUrl?: string}>  $items
     */
    protected static function tableActionMenuItemsHtml(array $items, string $theme): string
    {
        $html = '';

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = (string) ($item['label'] ?? '');
            $link = (string) ($item['link'] ?? '');
            if ($label === '' || $link === '') {
                continue;
            }

            $target = (string) ($item['target'] ?? '');
            $modalUrl = (string) ($item['modalUrl'] ?? '');
            $targetAttr = $target !== '' ? ' target="'.e($target).'" rel="noopener"' : '';
            $modalClass = $modalUrl !== '' ? ' js-open-modal' : '';
            $modalAttr = $modalUrl !== '' ? ' data-modal-url="'.e($modalUrl).'"' : '';

            if ($theme === 'metronic9') {
                $html .= '<a href="'.$link.'" class="kt-dropdown-menu-link'.$modalClass.'"'.$modalAttr.$targetAttr.' data-kt-dropdown-dismiss="true">'
                    .e($label)
                    .'</a>';
                continue;
            }

            $html .= '<li><a class="dropdown-item'.$modalClass.'" href="'.$link.'"'.$modalAttr.$targetAttr.'>'
                .e($label)
                .'</a></li>';
        }

        return $html;
    }

    protected static function tableActionIcon(?string $icon, string $theme): string
    {
        if (! $icon) {
            return '';
        }

        if ($theme === 'metronic9') {
            $map = [
                'pencil' => 'ki-filled ki-pencil',
                'trash' => 'ki-filled ki-trash',
                'eye' => 'ki-filled ki-eye',
                'plus' => 'ki-filled ki-plus',
                'file-down' => 'ki-filled ki-file-down',
                'book' => 'ki-filled ki-book',
                'printer' => 'ki-filled ki-printer',
            ];

            foreach ($map as $needle => $class) {
                if (str_contains($icon, $needle)) {
                    return $class;
                }
            }

            return 'ki-filled ki-'.$icon;
        }

        if (str_contains($icon, 'fa ') || str_contains($icon, 'la ') || str_contains($icon, 'bi ')) {
            return $icon;
        }

        return 'fa fa-'.$icon;
    }

    public static function ButtonSet($buttons)
    {
        $htmlStr = '';
        foreach ($buttons as $button) {
            if ($button['type']=='button') {
                $tag= 'button';
            } else {
                $tag= 'a';
            }

            $htmlStr .='<'.$tag .' class="btn ';
            if (isset($button["class"])) {
                $htmlStr .= $button["class"];
            }
            $htmlStr .='"';
            if (isset($button["attrs"])) {
                foreach ($button["attrs"] as $attribute=>$value) {
                    $htmlStr .= ' '.$attribute.'='.$value;
                }
            }
            $htmlStr .= '>';
            if (isset($button["icon"])) {
                $htmlStr .= '<i class="'.$button["icon"].'"></i>';
            }
            if (isset($button["text"])) {
                $htmlStr .= $button["text"];
            }
            $htmlStr .= '</'.$tag.'>';

        }
        return $htmlStr;
    }

    public static function linkify($text)
    {
        $text = html_entity_decode($text);
        $text = " ".$text;
        $text = preg_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
                '<a href="\\1" target=_blank>\\1</a>', $text);
        $text = preg_replace('(((f|ht){1}tps://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
                '<a href="\\1" target=_blank>\\1</a>', $text);
        $text = preg_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
                '\\1<a href="http://\\2" target=_blank>\\2</a>', $text);
        $text = preg_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})',
                '<a href="mailto:\\1" target=_blank>\\1</a>', $text);
        return $text;
    }
}
?>
