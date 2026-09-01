<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Maps overlay (/modal/...) paths to the matching full-page resource URL.
 */
class ModalUrl
{
    /**
     * @return string|null Path without leading slash, or null when this is not a known overlay URL.
     */
    public static function canonicalPath(string $path): ?string
    {
        $path = trim($path, '/');

        if (preg_match('#^([^/]+)/modal/create$#', $path, $match)) {
            return $match[1].'/create';
        }

        if (preg_match('#^([^/]+)/modal/quick-create$#', $path, $match)) {
            return $match[1].'/create';
        }

        if (preg_match('#^([^/]+)/modal/(\d+)/view$#', $path, $match)) {
            return $match[1].'/'.$match[2];
        }

        if (preg_match('#^([^/]+)/modal/(\d+)/edit$#', $path, $match)) {
            return $match[1].'/'.$match[2].'/edit';
        }

        if (preg_match('#^([^/]+)/modal/(\d+)/delete$#', $path, $match)) {
            return $match[1].'/'.$match[2];
        }

        if (preg_match('#^([^/]+)/modal/(\d+)$#', $path, $match)) {
            return $match[1].'/'.$match[2];
        }

        return null;
    }

    public static function canonicalUrl(Request $request): ?string
    {
        $canonical = self::canonicalPath($request->path());
        if ($canonical === null) {
            return null;
        }

        $query = $request->getQueryString();

        return url($canonical).($query ? '?'.$query : '');
    }
}
