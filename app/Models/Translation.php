<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    protected static function booted()
    {
        static::saved(function ($translation) {
            static::clearTranslationCache();
        });

        static::deleted(function ($translation) {
            static::clearTranslationCache();
        });
    }

    public static function clearTranslationCache(): void
    {
        $groups = static::select('group')->distinct()->pluck('group')->toArray();
        $groups[] = '*';
        $locales = ['az', 'en', 'ru'];

        try {
            $dbLocales = Language::pluck('code')->toArray();
            $locales = array_unique(array_merge($locales, $dbLocales));
        } catch (\Throwable $e) {
            // fallback if languages table is not reachable
        }

        foreach ($groups as $grp) {
            foreach ($locales as $loc) {
                Cache::forget("db_translations_{$loc}_{$grp}");
            }
        }
    }

    public static function getGroupTranslations(string $group, string $locale): array
    {
        $translations = static::where('group', $group)->get();
        $results = [];

        foreach ($translations as $translation) {
            $val = $translation->value[$locale] ?? null;
            if ($val !== null) {
                if ($group === '*') {
                    $results[$translation->key] = $val;
                } else {
                    Arr::set($results, $translation->key, $val);
                }
            }
        }

        return $results;
    }

    public function getTranslation(string $locale, ?string $default = ''): ?string
    {
        return $this->value[$locale] ?? $default;
    }
}
