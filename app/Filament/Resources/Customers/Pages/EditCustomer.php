<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\Actions\Communication;
use App\Filament\Resources\Customers\Actions\SendPrivacyPolicy;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                SendPrivacyPolicy::make(),
                Communication::make(),
                DeleteAction::make(),
            ])
                ->label('Altre Azioni')
                ->hiddenLabel()
                ->tooltip('Altre Azioni')
                ->icon('heroicon-m-ellipsis-vertical'),
        ];
    }
}
