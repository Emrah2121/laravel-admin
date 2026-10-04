<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\SetLocaleRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AdminLangController extends Controller
{
    public function __invoke(SetLocaleRequest $request)
    {
        try {
            $code = $request->validated('lang');

            app()->setLocale($code);
            Session::put('locale', $code);

            return back()->withSuccess(trans('backend.messages.success.lang'));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return back()->withError(trans('backend.messages.error.lang'));
        }
    }
}
