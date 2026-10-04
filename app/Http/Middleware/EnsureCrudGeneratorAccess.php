<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrudGeneratorAccess
{
    public function handle(Request $request, Closure $next)
    {
        // A disabled generator must look like it does not exist, even for developers.
        abort_unless(config('crud-generator.enabled'), Response::HTTP_NOT_FOUND);

        abort_unless(admin()?->isDeveloper(), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
