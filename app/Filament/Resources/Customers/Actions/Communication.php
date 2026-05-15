<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Actions;

use App\Enums\PreferredChannel;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class Communication extends Action
{
    public static function make(?string $name = 'communication'): static
    {
        return parent::make($name)
            ->label('Comunicazione')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->modalHeading('Preferenze Comunicazione')
            ->modalDescription('Modifica il canale di comunicazione preferito per questo cliente.')
            ->modalIcon(Heroicon::OutlinedChatBubbleLeftRight)
            ->modalAlignment(Alignment::Center)
            ->modalWidth(Width::Medium)
            ->modalSubmitActionLabel('Modifica')
            ->stickyModalFooter()
            ->modalFooterActionsAlignment(Alignment::Center)
            ->fillForm(fn (Customer $record): array => ['preferred_channel' => $record->preferred_channel])
            ->schema([
                ToggleButtons::make('preferred_channel')
                    ->label('Canale preferito')
                    ->hiddenLabel()
                    ->options(PreferredChannel::class)
                    ->default(PreferredChannel::Email)
                    ->inline()
                    ->extraAttributes(['style' => 'justify-content: center'])
                    ->required(),
            ])
            ->action(function (array $data, Customer $record): void {
                $record->preferred_channel = $data['preferred_channel'];
                $record->save();

                Notification::make()
                    ->success()
                    ->title('Canale di comunicazione aggiornato')
                    ->body("Il canale di comunicazione preferito è stato impostato a {$record->preferred_channel->getLabel()}")
                    ->send();
            });
    }
}
