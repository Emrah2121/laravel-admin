<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    public const DEFAULT_GEMINI_MODEL = 'gemini-3.8-flash';

    protected $fillable = [
        'gemini_api_key',
        'gemini_model',
        'pexels_api_key',
    ];

    protected $hidden = ['gemini_api_key', 'pexels_api_key'];

    protected $casts = [
        'gemini_api_key' => 'encrypted',
        'pexels_api_key' => 'encrypted',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'gemini_model' => self::DEFAULT_GEMINI_MODEL,
        ]);
    }

    public function hasGeminiKey(): bool
    {
        return filled($this->gemini_api_key);
    }

    public function hasPexelsKey(): bool
    {
        return filled($this->pexels_api_key);
    }
}
