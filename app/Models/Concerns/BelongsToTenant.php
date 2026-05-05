<?php

namespace App\Models\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $tenant = Filament::getTenant();

            if ($tenant !== null) {
                $query->where('tenant_id', $tenant->getKey());
            }
        });

        static::creating(function (Model $model) {
            if ($model->tenant_id === null) {
                $tenant = Filament::getTenant();

                if ($tenant !== null) {
                    $model->tenant_id = $tenant->getKey();
                }
            }
        });
    }
}
