<?php

namespace App\Traits;

trait TranslationTrait
{
    public function translate($code)
    {
        return $this->translations->where('locale', $code)->first();
    }
}
