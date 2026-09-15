<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateService extends CreateRecord
{
    #[Override]
    protected static bool $canCreateAnother = false;

    protected static string $resource = ServiceResource::class;
}
