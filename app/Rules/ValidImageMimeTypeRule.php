<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidImageMimeTypeRule implements Rule
{
    protected $type;

    public function __construct($type = 'image')
    {
        $this->type = $type;
    }

    public function passes($attribute, $value)
    {
        if (!$value->isValid()) {
            return false;
        }

        $allowedMimes = $this->type == 'image'
            ? ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp']
            : [
                'image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp',
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ];

        return in_array($value->getMimeType(), $allowedMimes);
    }

    public function message()
    {
        return 'Seçilmiş fayl uyğun formatda deyil.';
    }
}
