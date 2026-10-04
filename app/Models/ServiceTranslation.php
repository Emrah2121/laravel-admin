<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTranslation extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = sanitize_html($value);
    }
}
