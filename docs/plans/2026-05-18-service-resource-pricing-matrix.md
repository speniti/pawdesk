# Service Resource & Pricing Matrix Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Creare una Filament Resource completa per gestire i servizi (grooming, bath, trimming, etc.) con una matrice prezzi per taglia, permettendo il CRUD completo, filtri per categoria/tipo pelo/stato, e gestione stato archived senza soft deletes.

**Architecture:** Seguiamo il pattern esistente delle Filament Resources (PetResource come riferimento) con struttura organizzata in sottocartelle (Pages, Schemas, Tables, Widgets, RelationManagers). La ServiceResource usa tenant-scoping, validazione custom per durata/prezzo, e un custom field per la matrice prezzi per taglia (JSON).

**Tech Stack:** Laravel 13, Filament v5, PHP 8.5, Pest v4, MySQL

---

## Task 1: Creare Enum ServiceCategory

**Files:**
- Create: `app/Enums/ServiceCategory.php`

**Step 1: Creare l'enum ServiceCategory**

```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ServiceCategory: string implements HasLabel
{
    case Grooming = 'grooming';
    case Bath = 'bath';
    case Trimming = 'trimming';
    case Wellness = 'wellness';
    case Specialty = 'specialty';

    public function getLabel(): string
    {
        return match ($this) {
            self::Grooming => 'Grooming',
            self::Bath => 'Bagno',
            self::Trimming => 'Tosatura',
            self::Wellness => 'Benessere',
            self::Specialty => 'Specialità',
        };
    }
}
```

**Step 2: Esegui Pint per formattare**

Run: `vendor/bin/pint app/Enums/ServiceCategory.php`
Expected: File formattato senza errori

**Step 3: Commit**

```bash
git add app/Enums/ServiceCategory.php
git commit -m "feat: add ServiceCategory enum"
```

---

## Task 2: Creare struttura directory ServiceResource

**Files:**
- Create directory: `app/Filament/Resources/Services/`
- Create subdirectories: `Pages/`, `Schemas/`, `Tables/`

**Step 1: Creare le directory**

Run: `mkdir -p app/Filament/Resources/Services/{Pages,Schemas,Tables}`
Expected: Directory create senza errori

**Step 2: Commit**

```bash
git add app/Filament/Resources/Services
git commit -m "feat: create ServiceResource directory structure"
```

---

## Task 3: Creare ServiceForm schema

**Files:**
- Create: `app/Filament/Resources/Services/Schemas/ServiceForm.php`

**Step 1: Scrivi il test per la validazione della duration**

```php
<?php

use App\Models\Service;
use function Pest\Laravel\assertDatabaseHas;

it('validates duration_minutes must be greater than zero', function () {
    $service = Service::factory()->make(['duration_minutes' => 0]);

    Livewire::test(\App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => $service->name,
            'duration_minutes' => 0,
            'base_price' => $service->base_price,
            'category' => $service->category,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasFormErrors(['duration_minutes' => 'required|min:1']);
});
```

**Step 2: Esegui il test per verificare che fallisca**

Run: `php artisan test --compact --filter=duration_minutes`
Expected: FAIL (il form e la validazione non esistono ancora)

**Step 3: Scrivi il test per la validazione del base_price**

```php
<?php

it('validates base_price must be zero or positive', function () {
    $service = Service::factory()->make(['base_price' => -100]);

    Livewire::test(\App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => $service->name,
            'duration_minutes' => $service->duration_minutes,
            'base_price' => -100,
            'category' => $service->category,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasFormErrors(['base_price' => 'required|min:0']);
});
```

**Step 4: Esegui il test per verificare che fallisca**

Run: `php artisan test --compact --filter=base_price`
Expected: FAIL

**Step 5: Crea il ServiceForm con validazione completa**

```php
<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use App\Enums\Size;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Number;

class ServiceForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informazioni generali')
                    ->description('Dettagli principali del servizio')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete(false),

                        Textarea::make('description')
                            ->label('Descrizione')
                            ->maxLength(2000)
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Select::make('category')
                                    ->label('Categoria')
                                    ->options(ServiceCategory::class)
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Select::make('coat')
                                    ->label('Tipo di pelo')
                                    ->options(Coat::class)
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('duration_minutes')
                                    ->label('Durata (minuti)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(480)
                                    ->suffix('min')
                                    ->autocomplete(false),

                                TextInput::make('base_price')
                                    ->label('Prezzo base')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('€')
                                    ->autocomplete(false)
                                    ->formatStateUsing(fn ($state): string => (string) ($state / 100))
                                    ->dehydrateStateUsing(fn ($state): int => (int) ($state * 100)),

                                Toggle::make('combinable')
                                    ->label('Combinabile')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Select::make('status')
                            ->label('Stato')
                            ->options(ServiceStatus::class)
                            ->default(ServiceStatus::Active)
                            ->required()
                            ->live(),
                    ]),

                Section::make('Prezzi per taglia')
                    ->description('Override del prezzo base per ogni taglia. Lascia vuoto per usare il prezzo base.')
                    ->schema([
                        Repeater::make('size_prices')
                            ->label('')
                            ->schema([
                                Select::make('size')
                                    ->label('Taglia')
                                    ->options(Size::class)
                                    ->required()
                                    ->live()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpan(1),

                                TextInput::make('price')
                                    ->label('Prezzo')
                                    ->suffix('€')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->autocomplete(false)
                                    ->formatStateUsing(fn ($state): string => (string) ($state / 100))
                                    ->dehydrateStateUsing(fn ($state): int => (int) ($state * 100))
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => Size::from($state['size'])->getLabel() ?? null)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }
}
```

**Step 6: Esegui Pint**

Run: `vendor/bin/pint app/Filament/Resources/Services/Schemas/ServiceForm.php`
Expected: File formattato

**Step 7: Esegui i test**

Run: `php artisan test --compact --filter=ServiceForm`
Expected: I test di validazione ora PASS (il form applicherà le regole min:1 e min:0)

**Step 8: Commit**

```bash
git add tests/Feature/Filament/ServiceFormTest.php app/Filament/Resources/Services/Schemas/ServiceForm.php
git commit -m "feat: add ServiceForm with validation and size prices matrix"
```

---

## Task 4: Creare ServicesTable configuration

**Files:**
- Create: `app/Filament/Resources/Services/Tables/ServicesTable.php`

**Step 1: Scrivi il test per il filtro default (solo active)**

```php
<?php

use App\Models\Service;
use App\Enums\ServiceStatus;
use function Pest\Laravel\assertDatabaseCount;

it('shows only active services by default', function () {
    Service::factory()->count(3)->create(['status' => ServiceStatus::Active]);
    Service::factory()->count(2)->create(['status' => ServiceStatus::Archived]);

    Livewire::test(\App\Filament\Resources\Services\Pages\ListServices::class)
        ->assertCanSeeTableRecords(Service::where('status', ServiceStatus::Active)->get())
        ->assertCountTableRecords(3);
});
```

**Step 2: Esegui il test**

Run: `php artisan test --compact --filter=shows_only_active`
Expected: FAIL (la table non esiste)

**Step 3: Crea la ServicesTable con filtri e ordinamento**

```php
<?php

namespace App\Filament\Resources\Services\Tables;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Number;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Service $record): ?string => $record->description?->substr(0, 50)),

                TextColumn::make('category')
                    ->label('Categoria')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (ServiceCategory $state): string => match ($state) {
                        ServiceCategory::Grooming => 'primary',
                        ServiceCategory::Bath => 'info',
                        ServiceCategory::Trimming => 'warning',
                        ServiceCategory::Wellness => 'success',
                        ServiceCategory::Specialty => 'danger',
                    }),

                TextColumn::make('duration_minutes')
                    ->label('Durata')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => $state . ' min'),

                TextColumn::make('base_price')
                    ->label('Prezzo base')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => Number::format($state / 100, 2, ',', '.') . ' €')
                    ->alignment('right'),

                TextColumn::make('coat')
                    ->label('Tipo pelo')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?Coat $state): ?string => $state?->getLabel())
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->sortable()
                    ->badge()
                    ->color(fn (ServiceStatus $state): string => $state->getColor()),

                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('category', 'asc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(ServiceCategory::class),

                SelectFilter::make('coat')
                    ->label('Tipo pelo')
                    ->options(Coat::class)
                    ->placeholder('Tutti'),

                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(ServiceStatus::class)
                    ->default(ServiceStatus::Active->value),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

**Step 4: Esegui Pint**

Run: `vendor/bin/pint app/Filament/Resources/Services/Tables/ServicesTable.php`
Expected: File formattato

**Step 5: Esegui il test**

Run: `php artisan test --compact --filter=shows_only_active`
Expected: PASS (la table mostra solo gli active con il filtro default)

**Step 6: Commit**

```bash
git add tests/Feature/Filament/ServicesTableTest.php app/Filament/Resources/Services/Tables/ServicesTable.php
git commit -m "feat: add ServicesTable with filters and default active filter"
```

---

## Task 5: Creare ServiceResource principale

**Files:**
- Create: `app/Filament/Resources/Services/ServiceResource.php`

**Step 1: Scrivi il test per verificare che la resource sia registrata**

```php
<?php

it('service resource is registered in filament', function () {
    $resources = filament()->getResources();
    expect($resources)->toContain(\App\Filament\Resources\Services\ServiceResource::class);
});
```

**Step 2: Esegui il test**

Run: `php artisan test --compact --filter=service_resource_registered`
Expected: FAIL

**Step 3: Crea ServiceResource**

```php
<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages;
use App\Filament\Resources\Services\Schemas\ServiceForm;
use App\Filament\Resources\Services\Tables\ServicesTable;
use App\Models\Service;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends Resource
{
    protected static bool $isScopedToTenant = true;

    protected static ?string $model = Service::class;

    protected static ?string $modelLabel = 'Servizio';

    protected static ?string $pluralModelLabel = 'Servizi';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scissors';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationGroup = 'Gestionale';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return ServiceForm::configure($form);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return ServicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'view' => Pages\ViewService::route('/{record}'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['appointments']);
    }
}
```

**Step 4: Esegui Pint**

Run: `vendor/bin/pint app/Filament/Resources/Services/ServiceResource.php`
Expected: File formattato

**Step 5: Esegui il test**

Run: `php artisan test --compact --filter=service_resource_registered`
Expected: PASS

**Step 6: Commit**

```bash
git add tests/Feature/Filament/ServiceResourceTest.php app/Filament/Resources/Services/ServiceResource.php
git commit -m "feat: add ServiceResource with navigation and pages"
```

---

## Task 6: Creare le Pages (List, Create, View, Edit)

**Files:**
- Create: `app/Filament/Resources/Services/Pages/ListServices.php`
- Create: `app/Filament/Resources/Services/Pages/CreateService.php`
- Create: `app/Filament/Resources/Services/Pages/ViewService.php`
- Create: `app/Filament/Resources/Services/Pages/EditService.php`

**Step 1: Scrivi il test per la pagina di creazione**

```php
<?php

it('can create a service', function () {
    $service = Service::factory()->make();

    Livewire::test(\App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => $service->name,
            'description' => $service->description,
            'category' => $service->category,
            'coat' => $service->coat?->value,
            'duration_minutes' => $service->duration_minutes,
            'base_price' => $service->base_price / 100,
            'combinable' => $service->combinable,
            'status' => 'active',
            'size_prices' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('services', [
        'name' => $service->name,
        'category' => $service->category,
    ]);
});
```

**Step 2: Esegui il test**

Run: `php artisan test --compact --filter=can_create_service`
Expected: FAIL

**Step 3: Crea ListServices page**

```php
<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\ListRecords;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
```

**Step 4: Crea CreateService page**

```php
<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;
}
```

**Step 5: Crea ViewService page**

```php
<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string
    {
        return $this->getRecordTitle();
    }
}
```

**Step 6: Crea EditService page**

```php
<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string
    {
        return $this->getRecordTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                DeleteAction::make(),
            ]),
        ];
    }
}
```

**Step 7: Esegui Pint su tutte le pages**

Run: `vendor/bin/pint app/Filament/Resources/Services/Pages/`
Expected: File formattati

**Step 8: Esegui il test**

Run: `php artisan test --compact --filter=can_create_service`
Expected: PASS

**Step 9: Commit**

```bash
git add tests/Feature/Filament/CreateServiceTest.php app/Filament/Resources/Services/Pages/
git commit -m "feat: add ServiceResource pages (list, create, view, edit)"
```

---

## Task 7: Creare ServiceInfolist per la View page

**Files:**
- Create: `app/Filament/Resources/Services/Schemas/ServiceInfolist.php`

**Step 1: Aggiorna ServiceResource per usare ServiceInfolist**

```php
// In ServiceResource.php, aggiungi:
use App\Filament\Resources\Services\Schemas\ServiceInfolist;

public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
{
    return ServiceInfolist::configure($infolist);
}
```

**Step 2: Crea ServiceInfolist**

```php
<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Illuminate\Support\Number;

class ServiceInfolist
{
    public static function configure(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Informazioni generali')
                            ->description('Dettagli del servizio')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nome'),

                                TextEntry::make('description')
                                    ->label('Descrizione')
                                    ->placeholder('Nessuna descrizione'),

                                TextEntry::make('category')
                                    ->label('Categoria')
                                    ->badge()
                                    ->color(fn (ServiceCategory $state): string => match ($state) {
                                        ServiceCategory::Grooming => 'primary',
                                        ServiceCategory::Bath => 'info',
                                        ServiceCategory::Trimming => 'warning',
                                        ServiceCategory::Wellness => 'success',
                                        ServiceCategory::Specialty => 'danger',
                                    }),

                                TextEntry::make('coat')
                                    ->label('Tipo di pelo')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn (?Coat $state): ?string => $state?->getLabel())
                                    ->placeholder('Non specificato'),

                                TextEntry::make('duration_minutes')
                                    ->label('Durata')
                                    ->formatStateUsing(fn ($state): string => $state . ' min'),

                                TextEntry::make('combinable')
                                    ->label('Combinabile')
                                    ->boolean(),
                            ])
                            ->columnSpan(2),

                        Section::make('Prezzo e stato')
                            ->description('Informazioni economiche')
                            ->schema([
                                TextEntry::make('base_price')
                                    ->label('Prezzo base')
                                    ->formatStateUsing(fn ($state): string => Number::format($state / 100, 2, ',', '.') . ' €')
                                    ->alignment('end'),

                                TextEntry::make('status')
                                    ->label('Stato')
                                    ->badge()
                                    ->color(fn (ServiceStatus $state): string => $state->getColor())
                                    ->alignment('end'),
                            ])
                            ->columnSpan(1),
                    ]),

                Section::make('Prezzi per taglia')
                    ->description('Override del prezzo base per taglia')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('size_prices')
                            ->label('')
                            ->schema([
                                TextEntry::make('size')
                                    ->label('Taglia')
                                    ->formatStateUsing(fn ($state): string => \App\Enums\Size::from($state)->getLabel()),

                                TextEntry::make('price')
                                    ->label('Prezzo')
                                    ->formatStateUsing(fn ($state): string => Number::format($state / 100, 2, ',', '.') . ' €')
                                    ->alignment('right'),
                            ])
                            ->columns(2)
                            ->hidden(fn (): bool => empty(\Filament\Infolists\Infolist::$currentEntry?->size_prices)),
                    ])
                    ->columns(1),
            ]);
    }
}
```

**Step 3: Esegui Pint**

Run: `vendor/bin/pint app/Filament/Resources/Services/Schemas/ServiceInfolist.php`
Expected: File formattato

**Step 4: Esegui Pint su ServiceResource aggiornato**

Run: `vendor/bin/pint app/Filament/Resources/Services/ServiceResource.php`
Expected: File formattato

**Step 5: Commit**

```bash
git add app/Filament/Resources/Services/Schemas/ServiceInfolist.php app/Filament/Resources/Services/ServiceResource.php
git commit -m "feat: add ServiceInfolist for view page"
```

---

## Task 8: Test completo del CRUD

**Files:**
- Create: `tests/Feature/Filament/ServiceCrudTest.php`

**Step 1: Scrivi il test completo CRUD**

```php
<?php

use App\Models\Service;
use App\Enums\ServiceStatus;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->service = Service::factory()->create();
});

it('can read a service', function () {
    Livewire::test(\App\Filament\Resources\Services\Pages\ViewService::class, [
        'record' => $this->service->getRouteKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'name' => $this->service->name,
            'category' => $this->service->category,
        ]);
});

it('can update a service', function () {
    $newData = [
        'name' => 'Servizio Aggiornato',
        'description' => 'Descrizione aggiornata',
        'category' => 'grooming',
        'duration_minutes' => 90,
        'base_price' => 85.00,
        'combinable' => false,
        'status' => 'active',
    ];

    Livewire::test(\App\Filament\Resources\Services\Pages\EditService::class, [
        'record' => $this->service->getRouteKey(),
    ])
        ->fillForm($newData)
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('services', [
        'id' => $this->service->id,
        'name' => 'Servizio Aggiornato',
    ]);
});

it('can archive a service instead of deleting it', function () {
    Livewire::test(\App\Filament\Resources\Services\Pages\EditService::class, [
        'record' => $this->service->getRouteKey(),
    ])
        ->fillForm(['status' => 'archived'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->service->fresh()->status)->toBe(ServiceStatus::Archived);

    // Il record esiste ancora nel database
    assertDatabaseHas('services', [
        'id' => $this->service->id,
        'status' => 'archived',
    ]);
});

it('can delete a service', function () {
    Livewire::test(\App\Filament\Resources\Services\Pages\EditService::class, [
        'record' => $this->service->getRouteKey(),
    ])
        ->callAction(\Filament\Actions\DeleteAction::class);

    assertDatabaseMissing('services', [
        'id' => $this->service->id,
    ]);
});
```

**Step 2: Esegui i test CRUD**

Run: `php artisan test --compact --filter=ServiceCrudTest`
Expected: Tutti i test PASS

**Step 3: Commit**

```bash
git add tests/Feature/Filament/ServiceCrudTest.php
git commit -m "test: add complete CRUD tests for ServiceResource"
```

---

## Task 9: Test matrice prezzi per taglia

**Files:**
- Create: `tests/Feature/Filament/ServiceSizePricesTest.php`

**Step 1: Scrivi il test per la matrice prezzi**

```php
<?php

use App\Models\Service;
use App\Enums\Size;
use function Pest\Laravel\assertDatabaseHas;

it('can save size prices matrix', function () {
    $sizePrices = [
        ['size' => 'toy', 'price' => 2500],   // 25.00€
        ['size' => 'small', 'price' => 3500], // 35.00€
        ['size' => 'medium', 'price' => 4500], // 45.00€
    ];

    Livewire::test(\App\Filament\Resources\Services\Pages\CreateService::class)
        ->fillForm([
            'name' => 'Grooming completo',
            'category' => 'grooming',
            'duration_minutes' => 120,
            'base_price' => 40.00,
            'status' => 'active',
            'size_prices' => $sizePrices,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $service = Service::where('name', 'Grooming completo')->first();

    expect($service->size_prices)->toBeArray()
        ->and($service->size_prices)->toHaveCount(3)
        ->and($service->size_prices['toy'])->toBe(2500)
        ->and($service->size_prices['small'])->toBe(3500)
        ->and($service->size_prices['medium'])->toBe(4500);
});

it('can read size prices from service', function () {
    $service = Service::factory()->create([
        'size_prices' => [
            'toy' => 2500,
            'giant' => 9500,
        ],
    ]);

    Livewire::test(\App\Filament\Resources\Services\Pages\ViewService::class, [
        'record' => $service->getRouteKey(),
    ])
        ->assertOk();

    expect($service->size_prices['toy'])->toBe(2500)
        ->and($service->size_prices['giant'])->toBe(9500);
});

it('uses base price when size price is not set', function () {
    $service = Service::factory()->create([
        'base_price' => 5000, // 50.00€
        'size_prices' => [
            'toy' => 2500,
        ],
    ]);

    expect($service->size_prices)->toHaveKey('toy', 2500)
        ->and($service->size_prices)->not->toHaveKey('small')
        ->and($service->base_price)->toBe(5000);
});
```

**Step 2: Esegui i test**

Run: `php artisan test --compact --filter=ServiceSizePricesTest`
Expected: Tutti i test PASS

**Step 3: Commit**

```bash
git add tests/Feature/Filament/ServiceSizePricesTest.php
git commit -m "test: add size prices matrix tests"
```

---

## Task 10: Verifica finale e cleanup

**Files:**
- Test: Tutti i test del ServiceResource

**Step 1: Esegui tutti i test del ServiceResource**

Run: `php artisan test --compact --filter=Service`
Expected: Tutti i test PASS

**Step 2: Verifica che la Resource sia visibile nel panel**

Run: `php artisan route:list --name=services --except-vendor`
Expected: Vedi le rotte per services.* (index, create, view, edit)

**Step 3: Esegui Pint su tutto il codice modificato**

Run: `vendor/bin/pint --dirty --format agent`
Expected: Tutti i file PHP sono formattati correttamente

**Step 4: Commit finale**

```bash
git add .
git commit -m "chore: finalize ServiceResource implementation"
```

---

## Note Importanti per l'Esecuzione

1. **NO Co-authored-by**: Ometti sempre `Co-Authored-By: Claude <noreply@anthropic.com>` dai messaggi di commit. Usa solo il messaggio di commit specificato nel piano.
2. **Tenant Scoping**: Tutte le query sono automaticamente scoped al tenant corrente grazie a `$isScopedToTenant = true`
3. **Prezzi in centesimi**: Ricorda che i prezzi sono salvati in centesimi (€ * 100) per evitare problemi con i decimali
4. **Stato Archived**: I servizi archiviati non vengono eliminati dal database ma sono nascosti dal filtro default
5. **Validazione**: La validazione duration > 0 e base_price ≥ 0 è implementata a livello di form con minValue
6. **Matrice prezzi**: Il campo `size_prices` è un JSON che mappa Size enum => prezzo in centesimi

---

## Checklist di Completamento

- [x] ServiceCategory enum creato
- [x] ServiceResource creata con navigazione
- [x] ServiceForm con validazione completa
- [x] ServicesTable con filtri e ordinamento
- [x] ServiceInfolist per view page
- [x] Tutte le pages (list, create, view, edit)
- [x] Test CRUD completi
- [x] Test matrice prezzi per taglia
- [x] Filtro default per mostrare solo servizi active
- [x] Validazione duration > 0 e base_price ≥ 0
