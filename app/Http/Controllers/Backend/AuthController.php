<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\LoginRequest;
use App\Models\SecuritySetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function loginForm(Request $request)
    {
        $security = SecuritySetting::current();
        $retryAfter = $security->loginRetryAfter($request->ip());

        return view('backend.login.index', compact('security', 'retryAfter'));
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only(['email', 'password']);
            $remember = $request->boolean('remember');

            if (auth('admin')->attempt($credentials, $remember)) {
                $request->clearFailedAttempts();

                return redirect(route('backend.dashboard'))->withSuccess(trans('backend.messages.success.login'));
            }

            $request->registerFailedAttempt();

            return back()->withWarning(trans('backend.messages.warning.login'));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return back()->withError(trans('backend.messages.error.login'));
        }
    }

    public function logout()
    {
        try {
            auth('admin')->logout();

            return redirect(route('backend.login.form'))->withSuccess(trans('backend.messages.success.logout'));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return back()->withError(trans('backend.messages.error.logout'));
        }
    }
}
