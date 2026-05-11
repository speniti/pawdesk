<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets\Schemas;

use App\Enums\Coat;
use App\Enums\Sex;
use App\Enums\Size;
use App\Enums\Species;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class PetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dati anagrafici')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Proprietario')
                            ->relationship(name: 'customer', titleAttribute: 'first_name')
                            ->searchable(['first_name', 'last_name'])
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->first_name} {$record->last_name}")
                            ->preload()
                            ->required(),

                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Select::make('species')
                            ->label('Specie')
                            ->options(Species::class)
                            ->required(),

                        TextInput::make('breed')
                            ->label('Razza')
                            ->maxLength(255),

                        Select::make('sex')
                            ->label('Sesso')
                            ->options(Sex::class)
                            ->default(Sex::Unknown)
                            ->required(),

                        DatePicker::make('date_of_birth')
                            ->label('Data di nascita')
                            ->maxDate(now()),
                    ])
                    ->columns(2),

                Section::make('Caratteristiche fisiche')
                    ->schema([
                        Select::make('size')
                            ->label('Taglia')
                            ->options(Size::class)
                            ->required(),

                        Select::make('coat')
                            ->label('Manto')
                            ->options(Coat::class),
                    ])
                    ->columns(2),

                Section::make('Note')
                    ->schema([
                        Textarea::make('behavioral_notes')
                            ->label('Note comportamentali')
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Textarea::make('health_notes')
                            ->label('Note sanitarie')
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
