<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy\Schemas\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;

class OpeningHoursDaySection
{
    public static function make(string $key, string $label): Section
    {
        return Section::make($label)
            ->collapsed()
            ->schema([
                Repeater::make("opening_hours.$key")
                    ->label($label)
                    ->hiddenLabel()
                    ->live()
                    ->table([
                        TableColumn::make('Apertura')->markAsRequired(),
                        TableColumn::make('Chiusura')->markAsRequired(),
                    ])
                    ->compact()
                    ->schema([
                        TimePicker::make('open')
                            ->label('Apertura')
                            ->required()
                            ->seconds(false)
                            ->format('H:i'),

                        TimePicker::make('close')
                            ->label('Chiusura')
                            ->required()
                            ->seconds(false)
                            ->format('H:i')
                            ->after('open'),
                    ])
                    ->addActionLabel('Aggiungi fascia oraria')
                    ->reorderable(false),
            ])
            ->columns(1);
    }
}
