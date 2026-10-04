<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader;
use Throwable;

class DatabaseTranslationLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null)
    {
        $fileTranslations = parent::load($locale, $group, $namespace);

        if ($namespace !== null && $namespace !== '*') {
            return $fileTranslations;
        }

        try {
            $cacheKey = "db_translations_{$locale}_{$group}";
            $dbTranslations = Cache::remember($cacheKey, 86400, function () use ($group, $locale) {
                return Translation::getGroupTranslations($group, $locale);
            });

            if (!empty($dbTranslations)) {
                return array_replace_recursive($fileTranslations, $dbTranslations);
            }
        } catch (Throwable $e) {
            return $fileTranslations;
        }

        return $fileTranslations;
    }
}
