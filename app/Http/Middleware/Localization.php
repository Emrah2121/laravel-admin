<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class Localization
{
    public function handle(Request $request, Closure $next)
    {
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        } else {
            $locale = Language::where('default', 1)->first();
            if ($locale) {
                App::setLocale($locale->code);
                Session::put('locale', $locale->code);
            }
        }

        return $next($request);
    }
}
