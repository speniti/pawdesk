<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Schemas;

use App\Enums\Coat;
use App\Enums\Gender;
use App\Enums\Size;
use App\Enums\Species;
use App\Models\Customer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Dati anagrafici')
                    ->icon(Heroicon::OutlinedHeart)
                    ->schema([
                        Select::make('customer_id')
                            ->label('Proprietario')
                            ->relationship(name: 'customer')
                            ->searchable(['first_name', 'last_name'])
                            ->getOptionLabelFromRecordUsing(fn (Customer $record) => "{$record->first_name} {$record->last_name}")
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        DatePicker::make('date_of_birth')
                            ->label('Data di nascita')
                            ->maxDate(now()),
                    ])
                    ->columns(3),

                Section::make('Caratteristiche fisiche')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->schema([
                        Select::make('species')
                            ->label('Specie')
                            ->options(Species::class)
                            ->required(),

                        TextInput::make('breed')
                            ->label('Razza')
                            ->maxLength(255),

                        Select::make('sex')
                            ->label('Sesso')
                            ->options(Gender::class)
                            ->default(Gender::Unknown)
                            ->required(),

                        Select::make('size')
                            ->label('Taglia')
                            ->options(Size::class)
                            ->required(),

                        Select::make('coat')
                            ->label('Manto')
                            ->options(Coat::class),
                    ])
                    ->columns(3),

                Section::make('Note comportamentali')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->schema([
                        Textarea::make('behavioral_notes')
                            ->hiddenLabel()
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Note sanitarie')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->schema([
                        Textarea::make('health_notes')
                            ->hiddenLabel()
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
