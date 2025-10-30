<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Disable redirects for unauthenticated requests by returning null.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
