<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets;

use App\Filament\Resources\Pets\Pages\CreatePet;
use App\Filament\Resources\Pets\Pages\EditPet;
use App\Filament\Resources\Pets\Pages\ListPets;
use App\Filament\Resources\Pets\Schemas\PetForm;
use App\Filament\Resources\Pets\Tables\PetsTable;
use App\Models\Pet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PetResource extends Resource
{
    protected static bool $isScopedToTenant = true;

    protected static ?string $model = Pet::class;

    protected static ?string $modelLabel = 'Pet';

    protected static UnitEnum|string|null $navigationGroup = 'Gestione';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 30;

    protected static ?string $pluralModelLabel = 'Pets';

    public static function form(Schema $schema): Schema
    {
        return PetForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPets::route('/'),
            'create' => CreatePet::route('/create'),
            'edit' => EditPet::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        return PetsTable::configure($table);
    }
}
