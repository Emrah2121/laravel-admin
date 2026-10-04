<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Throwable;

class ImportTranslationsCommand extends Command
{
    protected $signature = 'translations:import';

    protected $description = 'Import language files from lang/ into the database translations table';

    public function handle()
    {
        $this->info('Scanning language files...');

        $langPath = lang_path();

        if (!File::isDirectory($langPath)) {
            $this->error("Lang directory not found at {$langPath}");

            return 1;
        }

        $collected = [];

        foreach (File::directories($langPath) as $dir) {
            $locale = basename($dir);

            if ($locale === 'vendor') {
                continue;
            }

            foreach (File::files($dir) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $group = $file->getFilenameWithoutExtension();

                try {
                    $translations = include $file->getRealPath();

                    if (is_array($translations)) {
                        foreach (Arr::dot($translations) as $key => $val) {
                            if (is_string($val) || is_numeric($val)) {
                                $collected[$group][$key][$locale] = (string) $val;
                            }
                        }
                    }
                } catch (Throwable $e) {
                    $this->warn("Could not parse file {$file->getFilename()}: " . $e->getMessage());
                }
            }
        }

        foreach (File::files($langPath) as $file) {
            if ($file->getExtension() === 'json') {
                $locale = $file->getFilenameWithoutExtension();
                $content = json_decode(File::get($file->getRealPath()), true);

                if (is_array($content)) {
                    foreach ($content as $key => $val) {
                        if (is_string($val) || is_numeric($val)) {
                            $collected['*'][$key][$locale] = (string) $val;
                        }
                    }
                }
            }
        }

        $this->info('Saving to database...');
        $count = 0;

        foreach ($collected as $group => $keys) {
            foreach ($keys as $key => $localesValues) {
                $translation = Translation::where('group', $group)->where('key', $key)->first()
                    ?? new Translation(['group' => $group, 'key' => $key]);

                $translation->value = array_merge($localesValues, $translation->value ?? []);
                $translation->save();
                $count++;
            }
        }

        Translation::clearTranslationCache();

        $this->info("Successfully imported {$count} translation keys into the database!");

        return 0;
    }
}
