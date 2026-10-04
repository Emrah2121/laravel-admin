<?php

namespace App\Services\CrudGenerator;

use RuntimeException;

class CrudGeneratorException extends RuntimeException
{
    public array $errors = [];

    public static function withErrors(array $errors): self
    {
        $exception = new self(implode(PHP_EOL, array_merge(...array_values(array_map(fn ($messages) => (array) $messages, $errors)))));
        $exception->errors = $errors;

        return $exception;
    }
}
