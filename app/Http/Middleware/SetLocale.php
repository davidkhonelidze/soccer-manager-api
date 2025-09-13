<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language', 'en');

        $supportedLocales = ['en', 'ka'];

        if (!in_array($locale, $supportedLocales)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
