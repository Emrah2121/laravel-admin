<?php

namespace App\Models;

use App\Traits\DocumentTrait;
use App\Traits\SpatieLogsActivity;
use App\Traits\TranslationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;

class Blog extends Model
{
    use TranslationTrait, DocumentTrait, SpatieLogsActivity;

    protected $guarded = [];

    protected $casts = [
        'publish_date' => 'date',
    ];

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
        return $this->hasMany(BlogTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(BlogTranslation::class)
            ->where('locale', App::getLocale());
    }

    public function getGalleryImagesAttribute()
    {
        return $this->getDocuments('blog_images')->where('status', '!=', 'main');
    }
}
