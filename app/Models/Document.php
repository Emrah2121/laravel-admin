<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $guarded = [];

    protected $table = 'documents';

    public function manipulationable()
    {
        return $this->morphTo();
    }

    protected function document(): Attribute
    {
        return new Attribute(
            get: fn ($value) => Storage::disk('documents')->url($value)
        );
    }
}
