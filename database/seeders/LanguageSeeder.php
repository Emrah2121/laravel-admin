<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['name' => 'Azərbaycan', 'code' => 'az', 'default' => true, 'status' => true],
            ['name' => 'English', 'code' => 'en', 'default' => false, 'status' => true],
            ['name' => 'Русский', 'code' => 'ru', 'default' => false, 'status' => true],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], $language);
        }
    }
}
