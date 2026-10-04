<?php

namespace App\Traits;

use App\Models\Document;

trait DocumentTrait
{
    public function getFirstImageAttribute()
    {
        return $this->files->where('status', '!=', 'main')->first()?->document
            ?? $this->files->first()?->document;
    }

    public function getCoverImageAttribute()
    {
        return $this->files->where('status', 'main')->first()?->document
            ?? $this->files->first()?->document;
    }

    public function getDocuments($collection)
    {
        return $this->files->where('collection_name', $collection);
    }

    public function getDocument($collection)
    {
        return $this->files->where('collection_name', $collection)->first()?->document;
    }

    public function files()
    {
        return $this->morphMany(Document::class, 'manipulationable');
    }

    public function filesFromCollection($collection)
    {
        return $this->morphMany(Document::class, 'manipulationable')
            ->where('collection_name', $collection);
    }
}
