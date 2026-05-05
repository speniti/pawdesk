<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return $next($request);
        }

        User::addGlobalScope(
            'tenant',
            fn (Builder $query) => $query->whereBelongsTo($tenant),
        );

        return $next($request);
    }
}
