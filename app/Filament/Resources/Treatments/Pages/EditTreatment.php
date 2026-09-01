<?php

declare(strict_types=1);

namespace App\Filament\Resources\Treatments\Pages;

use App\Filament\Resources\Treatments\TreatmentResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditTreatment extends EditRecord
{
    protected static string $resource = TreatmentResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }
}
