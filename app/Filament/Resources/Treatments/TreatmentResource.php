<?php

declare(strict_types=1);

namespace App\Filament\Resources\Treatments;

use App\Filament\Resources\Treatments\Pages\EditTreatment;
use App\Filament\Resources\Treatments\Pages\ListTreatments;
use App\Filament\Resources\Treatments\Pages\ViewTreatment;
use App\Filament\Resources\Treatments\Schemas\TreatmentForm;
use App\Filament\Resources\Treatments\Schemas\TreatmentInfolist;
use App\Filament\Resources\Treatments\Tables\TreatmentsTable;
use App\Models\Treatment;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TreatmentResource extends Resource
{
    protected static bool $isScopedToTenant = true;

    protected static ?string $model = Treatment::class;

    protected static ?string $modelLabel = 'Trattamento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 35;

    protected static ?string $pluralModelLabel = 'Trattamenti';

    public static function form(Schema $schema): Schema
    {
        return TreatmentForm::configure($schema);
    }

    /**
     * Treatments are system-generated on appointment completion:
     * no create page is registered, creation happens only via TreatmentService.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTreatments::route('/'),
            'view' => ViewTreatment::route('/{record}'),
            'edit' => EditTreatment::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewTreatment::class,
            EditTreatment::class,
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TreatmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TreatmentsTable::configure($table);
    }
}
