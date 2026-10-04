<?php

use App\Models\Language;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

if (!function_exists('locale')) {
    function locale()
    {
        return app()->getlocale();
    }
}

if (!function_exists('short_name')) {
    function short_name()
    {
        $array = explode(' ', admin()->name);

        if (count($array) < 2) {
            return strtoupper(substr($array[0], 0, 1));
        }

        return strtoupper(substr($array[0], 0, 1)) . strtoupper(substr($array[1], 0, 1));
    }
}

if (!function_exists('settings')) {
    function settings($type = null)
    {
        $settings = Cache::remember('settings', 1800, function () {
            return Setting::with('translations')->get()->map(function ($setting) {
                return [
                    'name' => $setting->name,
                    'translations' => $setting->translations->map(fn ($translation) => [
                        'locale' => $translation->locale,
                        'content' => $translation->content,
                    ])->all(),
                ];
            })->all();
        });

        foreach ($settings as $setting) {
            if ($setting['name'] == $type) {
                $translation = collect($setting['translations'])->firstWhere('locale', locale());

                return $translation['content'] ?? '';
            }
        }

        return '';
    }
}

if (!function_exists('setting_text')) {
    function setting_text($key)
    {
        return strip_tags(settings($key));
    }
}

if (!function_exists('admin')) {
    function admin()
    {
        return auth('admin')->user();
    }
}

if (!function_exists('notify')) {
    function notify($type, $message)
    {
        if (is_array($message)) {
            $message = implode(', ', $message);
        }

        return "Swal.fire(
                {
                    icon:  '$type',
                    title: '$message',
                    showConfirmButton: false,
                    timer: 3000
                });";
    }
}

if (!function_exists('confirm')) {
    function confirm()
    {
        return "Swal.fire(
                {
                    title: '" . trans('backend.mixins.are_you_sure') . "',
                    text:  '" . trans('backend.mixins.wont_revert') . "',
                    icon:  'warning',
                    showCancelButton:   true,
                    buttonsStyling: false,
                    confirmButtonText:  '" . trans('backend.buttons.delete') . "',
                    cancelButtonText:   '" . trans('backend.buttons.cancel') . "',
                    customClass: {
                        confirmButton: 'btn fw-bold btn-danger',
                        cancelButton: 'btn fw-bold btn-active-light-primary'
                    }
                })";
    }
}

if (!function_exists('confirm_update')) {
    function confirm_update()
    {
        return "Swal.fire(
                {
                    title: '" . trans('backend.mixins.are_you_sure') . "',
                    text:  '" . trans('backend.mixins.wont_update') . "',
                    icon:  'warning',
                    showCancelButton:   true,
                    buttonsStyling: false,
                    confirmButtonText:  '" . trans('backend.buttons.update') . "',
                    cancelButtonText:   '" . trans('backend.buttons.cancel') . "',
                    customClass: {
                        confirmButton: 'btn fw-bold btn-primary',
                        cancelButton: 'btn fw-bold btn-active-light-primary'
                    }
                })";
    }
}

if (!function_exists('badge')) {
    function badge($value)
    {
        if ($value == 1) {
            return '<span class="badge badge-light-success">' . trans('backend.mixins.active') . '</span>';
        }

        return '<span class="badge badge-light-danger">' . trans('backend.mixins.passive') . '</span>';
    }
}

if (!function_exists('image_url')) {
    function image_url($name)
    {
        return asset("uploads/$name");
    }
}

if (!function_exists('current_lang')) {
    function current_lang()
    {
        $langs = active_langs();

        $current = $langs->firstWhere('code', locale());

        return $current?->name ?? '';
    }
}

if (!function_exists('current_lang_code')) {
    function current_lang_code()
    {
        return locale();
    }
}

if (!function_exists('active_langs')) {
    function active_langs()
    {
        $languages = Cache::rememberForever('active_langs', function () {
            return Language::active()->get(['id', 'name', 'code'])->map->only(['id', 'name', 'code'])->all();
        });

        return collect($languages)->map(fn ($language) => (object) $language);
    }
}

if (!function_exists('default_lang')) {
    function default_lang()
    {
        return optional(Language::default()->first())->code ?? config('app.locale');
    }
}

if (!function_exists('translation')) {
    function translation($model)
    {
        return $model?->translations->where('locale', locale())->first();
    }
}

if (!function_exists('translation_first')) {
    function translation_first($model)
    {
        $translations = $model?->translations;

        return $translations
            ? $translations->where('locale', locale())->first() ?? $translations->first()
            : null;
    }
}

if (!function_exists('sanitize_html')) {
    function sanitize_html($html)
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Quill output: alignment classes, list types, base64 images and video iframes.
        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowAttribute('class', '*')
            ->allowAttribute('data-list', 'li')
            ->allowElement('iframe', ['src', 'class', 'frameborder', 'allowfullscreen'])
            ->forceAttribute('iframe', 'sandbox', 'allow-scripts allow-same-origin allow-popups allow-presentation')
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowMediaSchemes(['http', 'https', 'data'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->withMaxInputLength(-1);

        return (new HtmlSanitizer($config))->sanitize($html);
    }
}
