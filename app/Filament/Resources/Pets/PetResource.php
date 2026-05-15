<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pets;

use App\Filament\Resources\Pets\Pages\CreatePet;
use App\Filament\Resources\Pets\Pages\EditPet;
use App\Filament\Resources\Pets\Pages\ListPets;
use App\Filament\Resources\Pets\Pages\ViewPet;
use App\Filament\Resources\Pets\RelationManagers\AppointmentsRelationManager;
use App\Filament\Resources\Pets\Schemas\PetForm;
use App\Filament\Resources\Pets\Schemas\PetInfolist;
use App\Filament\Resources\Pets\Tables\PetsTable;
use App\Filament\Resources\Pets\Widgets\PetStats;
use App\Models\Pet;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PetResource extends Resource
{
    protected static bool $isScopedToTenant = true;

    protected static ?string $model = Pet::class;

    protected static ?string $modelLabel = 'Pet';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 20;

    protected static ?string $pluralModelLabel = 'Pets';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return PetForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPets::route('/'),
            'create' => CreatePet::route('/create'),
            'view' => ViewPet::route('/{record}'),
            'edit' => EditPet::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewPet::class,
            EditPet::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            AppointmentsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            PetStats::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return PetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PetsTable::configure($table);
    }
}
