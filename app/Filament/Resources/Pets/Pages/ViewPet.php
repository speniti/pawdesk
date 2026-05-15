<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Pages;

use App\Filament\Resources\Pets\PetResource;
use App\Filament\Resources\Pets\Widgets\PetStats;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewPet extends ViewRecord
{
    protected static string $resource = PetResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderWidgets(): array
    {
        return [PetStats::class];
    }
}
