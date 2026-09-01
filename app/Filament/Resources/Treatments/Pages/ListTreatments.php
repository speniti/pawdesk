<?php

declare(strict_types=1);

namespace App\Filament\Resources\Treatments\Pages;

use App\Filament\Resources\Treatments\TreatmentResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTreatments extends ListRecords
{
    protected static string $resource = TreatmentResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['appointment', 'pet', 'customer']);
    }
}
