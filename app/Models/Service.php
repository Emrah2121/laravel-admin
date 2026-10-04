<?php

namespace App\Models;

use App\Traits\DocumentTrait;
use App\Traits\SpatieLogsActivity;
use App\Traits\TranslationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;

class Service extends Model
{
    use TranslationTrait, DocumentTrait, SpatieLogsActivity;

    protected $guarded = [];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ServiceTranslation::class)
            ->where('locale', App::getLocale());
    }

    public function getImageImagesAttribute()
    {
        return $this->getDocuments('service_image');
    }
}
