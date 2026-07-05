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

            if (self::keyset($option, 'menu')) {
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
        $colValue = '';

        if ($theme === 'metronic9') {
            $buttonClass = self::keyset($option, 'buttonClass', 'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost');
            $iconClass = self::tableActionIcon($icon, $theme);

            if ($modalUrl) {
                $colValue .= '<a href="'.e($modalUrl).'" class="'.$buttonClass.' js-open-modal" data-modal-url="'.e($modalUrl).'">';
            } else {
                $colValue .= '<a href="'.$link.'" class="'.$buttonClass.'">';
            }
            if ($iconClass) {
                $colValue .= '<i class="'.$iconClass.'"></i>';
            }
        } else {
            $buttonClass = self::keyset($option, 'buttonClass', 'btn btn-sm btn-icon btn-light btn-active-light-primary h-25px w-25px');
            $iconClass = self::tableActionIcon($icon, $theme);

            if ($modalUrl) {
                $colValue .= '<a href="'.e($modalUrl).'" class="'.$buttonClass.' js-open-modal" data-modal-url="'.e($modalUrl).'">';
            } else {
                $colValue .= '<a href="'.$link.'" class="'.$buttonClass.'">';
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
        $iconClass = self::tableActionIcon($icon, $theme);

        if ($theme === 'metronic9') {
            $buttonClass = self::keyset($option, 'buttonClass', 'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost');

            return '<div class="inline-flex items-center">'
                .'<a href="'.e($modalUrl).'" class="'.$buttonClass.' js-open-modal" data-modal-url="'.e($modalUrl).'">'
                .($iconClass ? '<i class="'.$iconClass.'"></i>' : '')
                .'</a>'
                .'<div class="inline-flex" data-kt-menu="true" data-kt-menu-placement="bottom-end">'
                .'<button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-menu-trigger="click">'
                .'<i class="ki-filled ki-down text-xs"></i>'
                .'</button>'
                .'<div class="kt-menu kt-menu-default kt-menu-dropdown min-w-[160px]">'
                .'<div class="kt-menu-item"><a href="'.e($modalUrl).'" class="kt-menu-link js-open-modal" data-modal-url="'.e($modalUrl).'"><span class="kt-menu-title">Open</span></a></div>'
                .'<div class="kt-menu-item"><a href="'.e($modalUrl).'" class="kt-menu-link" target="_blank" rel="noopener"><span class="kt-menu-title">Open in new page</span></a></div>'
                .'</div>'
                .'</div>'
                .'</div>';
        }

        $buttonClass = self::keyset($option, 'buttonClass', 'btn btn-sm btn-icon btn-light btn-active-light-primary h-25px w-25px');

        return '<div class="btn-group">'
            .'<a href="'.e($modalUrl).'" class="'.$buttonClass.' js-open-modal" data-modal-url="'.e($modalUrl).'">'
            .($iconClass ? '<i class="'.$iconClass.'"></i>' : '')
            .'</a>'
            .'<button type="button" class="'.$buttonClass.' dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"></button>'
            .'<ul class="dropdown-menu dropdown-menu-end">'
            .'<li><a class="dropdown-item js-open-modal" data-modal-url="'.e($modalUrl).'" href="'.e($modalUrl).'">Open</a></li>'
            .'<li><a class="dropdown-item" href="'.e($modalUrl).'" target="_blank" rel="noopener">Open in new page</a></li>'
            .'</ul>'
            .'</div>';
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
