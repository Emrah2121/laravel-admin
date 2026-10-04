<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

class Log extends Activity
{
    public function getDescriptionAttribute($value): string
    {
        return ucfirst($value);
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = strtolower($value);
    }
}
