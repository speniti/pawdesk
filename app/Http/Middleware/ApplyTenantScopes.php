<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next)
    {
        // The BelongsToTenant trait handles scoping for models that use it.
        // Add additional global scopes here for models that do NOT use the trait
        // but still need tenant isolation during Filament panel requests.

        return $next($request);
    }
}
