<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ProfileRequest;
use App\Http\Requests\Backend\SecuritySettingRequest;
use App\Models\SecuritySetting;
use Exception;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function profileForm()
    {
        $security = admin()->isDeveloper() ? SecuritySetting::current() : null;

        return view('backend.profile.index', compact('security'));
    }

    public function profile(ProfileRequest $request)
    {
        try {
            $data = $request->validated();

            if (empty($data['password'])) {
                unset($data['password']);
            }

            admin()->update($data);

            return redirect(route('backend.dashboard'))->withSuccess(trans('backend.messages.success.profile'));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect(route('backend.dashboard'))->withError(trans('backend.messages.error.profile'));
        }
    }

    public function security(SecuritySettingRequest $request)
    {
        $securityTab = route('backend.profile.form') . '#kt_profile_security';

        try {
            $data = $request->validated();

            // Stored keys are never shown back, so a blank input keeps the current value.
            $data = array_filter($data, fn ($value) => $value !== null);

            SecuritySetting::current()->update($data);

            return redirect($securityTab)->withSuccess(trans('backend.messages.success.update'));
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect($securityTab)->withError(trans('backend.messages.error.update'));
        }
    }
}
