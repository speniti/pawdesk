<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Pages;

use App\Filament\Resources\Pets\PetResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditPet extends EditRecord
{
    protected static string $resource = PetResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                DeleteAction::make(),
            ])
                ->label('Altre Azioni')
                ->hiddenLabel()
                ->tooltip('Altre Azioni')
                ->icon('heroicon-m-ellipsis-vertical'),
        ];
    }
}
