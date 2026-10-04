<?php

namespace App\Models;

use App\Traits\SpatieLogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use SpatieLogsActivity;

    protected $guarded = [];

    public $with = ['translations'];

    public function translations()
    {
        return $this->hasMany(SettingTranslation::class);
    }

    public function translation()
    {
        return $this->hasOne(SettingTranslation::class)
            ->where('locale', app()->getlocale());
    }

    public function translate($code)
    {
        return $this->translations->where('locale', $code)->first();
    }
}
