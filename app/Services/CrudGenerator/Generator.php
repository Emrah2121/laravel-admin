<?php

namespace App\Services\CrudGenerator;

interface Generator
{
    /**
     * @return FileOperation[]
     */
    public function operations(ModuleSpec $spec): array;
}
