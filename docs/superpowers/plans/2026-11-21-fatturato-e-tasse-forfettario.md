# Piano di implementazione — Pagina "Fatturato e tasse" (regime forfettario)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dare al titolare di una toelettatura (ditta individuale in regime forfettario) una pagina dove vedere il fatturato dell'anno, la stima di imposte e contributi INPS, la percentuale da accantonare e l'avvicinamento alla soglia di €85.000.

**Architecture:** Tre unità con confini netti: (1) un estimatore puro `ForfettarioTaxEstimator` + value objects, senza dipendenze da DB/Filament; (2) un servizio di aggregazione `TenantRevenue` che legge `treatments.final_price`; (3) una pagina Filament "Fatturato e tasse" (solo Admin) con widget stats, grafico mensile e dettaglio del calcolo. I parametri fiscali vivono nella colonna JSON `settings` del tenant (nessuna migrazione).

**Tech Stack:** Laravel 13, Filament v5.7, PHP 8.5, Pest 4, Tailwind 4. UI in italiano, importi in centesimi (interi).

**Spec:** Design concordato in conversazione il 2026-11-21 (decisioni: data del ricavo = `treatments.created_at`; INPS con entrambi i regimi configurabili — importo fisso o percentuale; coefficiente default 78%, aliquota default 15%).

## Contesto

PawDesk è un gestionale multi-tenant per toelettature. La home è un calendario; di analytics non esiste nulla. L'unico dato economico è `treatments.final_price` (centesimi), generato quando un appuntamento passa a "Completato" (`app/Services/TreatmentService.php`) e modificabile dallo staff. La tabella `treatments` ha già l'indice `(tenant_id, created_at)`. Le impostazioni tenant usano la colonna JSON `settings` con accessori sul modello (`app/Models/Tenant.php`, pattern `privacyBusinessName()`). Widget di riferimento: `app/Filament/Resources/Customers/Widgets/CustomerStats.php`. Test di riferimento per pagine/autorizzazioni: `tests/Feature/Filament/Pages/EditTenantSettingsTest.php` con helper `bootFilamentPanelAs()` da `tests/Pest.php`.

## Vincoli globali

- Filament v5.7 (verificato nel vendor): `Page::content(Schema)` con view predefinita `filament-panels::pages.page` (nessun blade custom necessario), `getHeaderWidgets()`, `ChartWidget::$heading` + `getType()/getData()`, `Stat::description/descriptionIcon/descriptionColor`.
- Ruoli: `UserRole::Admin/Staff` su `User->role`; tenant corrente via `Filament::getTenant()`.
- Importi in centesimi (int); percentuali come float; formattazione con `Number::currency($cents / 100, in: 'EUR', locale: 'it')` e `Number::percentage(..., maxPrecision: 1, locale: 'it')`.
- **Il selettore `inps_mode` deve avere default `null`** (non 'fixed'): con default valorizzato la `required` condizionale impedirebbe il salvataggio delle altre sezioni del form e romperebbe i test esistenti.
- Regole repo: commit conventional senza trailer Co-Authored-By (`.ai/rules/general.md`); icone Heroicon outlined (`.ai/rules/filament.md`); `declare(strict_types=1)`; PHPDoc con array shape; enum TitleCase con `HasLabel`; dopo ogni modifica PHP eseguire `vendor/bin/pint --dirty --format agent`.
- Nessuna migrazione, nessuna nuova dipendenza, nessun file di documentazione oltre a questo piano.
- Ogni task: prima il test che fallisce, poi l'implementazione, poi commit.

## Struttura file

| File | Azione | Responsabilità |
| --- | --- | --- |
| `app/Enums/InpsMode.php` | Create | Enum regime contributivo (fisso/percentuale) |
| `app/Services/Forfettario/TaxConfiguration.php` | Create | Value object parametri fiscali |
| `app/Services/Forfettario/TaxEstimate.php` | Create | Value object risultato stima |
| `app/Services/Forfettario/ForfettarioTaxEstimator.php` | Create | Calcolo puro imponibile/INPS/imposta |
| `app/Services/TenantRevenue.php` | Create | Aggregazione ricavi per anno/mese/intervallo |
| `app/Models/Tenant.php` | Modify | PHPDoc settings + `forfettarioTaxConfiguration()` |
| `app/Filament/Pages/Tenancy/Schemas/TenantSettingsForm.php` | Modify | Sezione "Fiscalità (regime forfettario)" |
| `app/Filament/Pages/RevenueDashboard.php` | Create | Pagina (solo Admin) con dettaglio calcolo |
| `app/Filament/Pages/RevenueDashboard/Widgets/RevenueStats.php` | Create | Stat: fatturato, tasse, accantonamento, soglia |
| `app/Filament/Pages/RevenueDashboard/Widgets/RevenueChart.php` | Create | Grafico mensile anno corrente vs precedente |
| Test (vedi task) | Create/Modify | Pest, pattern esistenti |

---

### Task 1: Motore di calcolo forfettario

**Files:**
- Create: `app/Enums/InpsMode.php`, `app/Services/Forfettario/TaxConfiguration.php`, `app/Services/Forfettario/TaxEstimate.php`, `app/Services/Forfettario/ForfettarioTaxEstimator.php`
- Test: `tests/Feature/Services/ForfettarioTaxEstimatorTest.php`

**Interfaces:**
- Produces: `ForfettarioTaxEstimator::estimate(int $revenueCents, TaxConfiguration $config): TaxEstimate`; `ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS = 8_500_000`; `TaxConfiguration::__construct(float $coefficientPercent, float $substituteTaxRatePercent, InpsMode $inpsMode, ?int $inpsFixedAnnualCents, ?float $inpsPercentage)`; `TaxEstimate` proprietà readonly `revenueCents, taxableIncomeCents, inpsContributionsCents, substituteTaxCents, totalTaxCents` + `setAsideRate(): float`; `InpsMode::Fixed = 'fixed'` / `InpsMode::Percentage = 'percentage'`.

- [ ] **Step 1: Scrivere il test fallimentare**

```php
<?php

declare(strict_types=1);

use App\Enums\InpsMode;
use App\Services\Forfettario\ForfettarioTaxEstimator;
use App\Services\Forfettario\TaxConfiguration;

// Revenue €100.000, coefficient 78%, gestione separata 26%, imposta 15%:
// imponibile 78.000; INPS 20.280; imposta 15% x 57.720 = 8.658; totale 28.938.
test('estimates taxes with percentage inps mode', function () {
    $estimate = app(ForfettarioTaxEstimator::class)->estimate(
        10_000_000,
        new TaxConfiguration(78.0, 15.0, InpsMode::Percentage, null, 26.0),
    );

    expect($estimate->taxableIncomeCents)->toBe(7_800_000)
        ->and($estimate->inpsContributionsCents)->toBe(2_028_000)
        ->and($estimate->substituteTaxCents)->toBe(865_800)
        ->and($estimate->totalTaxCents)->toBe(2_893_800)
        ->and($estimate->setAsideRate())->toBe(0.2894);
});

// Revenue €50.000, coefficient 78%, contributi fissi €4.500, imposta 15%:
// imponibile 39.000; imposta 15% x 34.500 = 5.175; totale 9.675.
test('estimates taxes with fixed inps contributions', function () {
    $estimate = app(ForfettarioTaxEstimator::class)->estimate(
        5_000_000,
        new TaxConfiguration(78.0, 15.0, InpsMode::Fixed, 450_000, null),
    );

    expect($estimate->inpsContributionsCents)->toBe(450_000)
        ->and($estimate->substituteTaxCents)->toBe(517_500)
        ->and($estimate->totalTaxCents)->toBe(967_500);
});

// Con contributi fissi superiori all'imponibile l'imposta è 0 (non negativa),
// ma i contributi restano dovuti: il totale può superare il fatturato.
test('clamps substitute tax to zero when contributions exceed taxable income', function () {
    $estimate = app(ForfettarioTaxEstimator::class)->estimate(
        100_000, // €1.000
        new TaxConfiguration(78.0, 15.0, InpsMode::Fixed, 450_000, null),
    );

    expect($estimate->taxableIncomeCents)->toBe(78_000)
        ->and($estimate->substituteTaxCents)->toBe(0)
        ->and($estimate->totalTaxCents)->toBe(450_000)
        ->and($estimate->setAsideRate())->toBe(4.5);
});

test('handles zero revenue', function () {
    $estimate = app(ForfettarioTaxEstimator::class)->estimate(
        0,
        new TaxConfiguration(78.0, 15.0, InpsMode::Percentage, null, 26.0),
    );

    expect($estimate->taxableIncomeCents)->toBe(0)
        ->and($estimate->totalTaxCents)->toBe(0)
        ->and($estimate->setAsideRate())->toBe(0.0);
});

test('exposes the forfettario revenue limit', function () {
    expect(ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS)->toBe(8_500_000);
});
```

- [ ] **Step 2: Eseguire e verificare il fallimento**

Run: `vendor/bin/pest tests/Feature/Services/ForfettarioTaxEstimatorTest.php`
Expected: error "Class App\Enums\InpsMode not found".

- [ ] **Step 3: Implementare le quattro classi**

```php
// app/Enums/InpsMode.php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InpsMode: string implements HasLabel
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'Contributi fissi (artigiani/commercianti)',
            self::Percentage => 'Percentuale sull\'imponibile (gestione separata)',
        };
    }
}
```

```php
// app/Services/Forfettario/TaxConfiguration.php
<?php

declare(strict_types=1);

namespace App\Services\Forfettario;

use App\Enums\InpsMode;

final readonly class TaxConfiguration
{
    /**
     * @param  float  $coefficientPercent  Coefficiente di redditività (es. 78)
     * @param  float  $substituteTaxRatePercent  Aliquota imposta sostitutiva (15 o 5)
     * @param  int|null  $inpsFixedAnnualCents  Contributi fissi annui in centesimi
     * @param  float|null  $inpsPercentage  Aliquota INPS sulla imponibile (es. 26.07)
     */
    public function __construct(
        public readonly float $coefficientPercent,
        public readonly float $substituteTaxRatePercent,
        public readonly InpsMode $inpsMode,
        public readonly ?int $inpsFixedAnnualCents,
        public readonly ?float $inpsPercentage,
    ) {}
}
```

```php
// app/Services/Forfettario/TaxEstimate.php
<?php

declare(strict_types=1);

namespace App\Services\Forfettario;

final readonly class TaxEstimate
{
    public function __construct(
        public readonly int $revenueCents,
        public readonly int $taxableIncomeCents,
        public readonly int $inpsContributionsCents,
        public readonly int $substituteTaxCents,
        public readonly int $totalTaxCents,
    ) {}

    /**
     * Share of the revenue to set aside for taxes. May exceed 1.0 early in the
     * year, when fixed INPS contributions outweigh the revenue accrued so far.
     */
    public function setAsideRate(): float
    {
        if ($this->revenueCents === 0) {
            return 0.0;
        }

        return round($this->totalTaxCents / $this->revenueCents, 4);
    }
}
```

```php
// app/Services/Forfettario/ForfettarioTaxEstimator.php
<?php

declare(strict_types=1);

namespace App\Services\Forfettario;

use App\Enums\InpsMode;

class ForfettarioTaxEstimator
{
    public const int REVENUE_LIMIT_CENTS = 8_500_000;

    public function estimate(int $revenueCents, TaxConfiguration $config): TaxEstimate
    {
        $taxableIncomeCents = (int) round($revenueCents * $config->coefficientPercent / 100);
        $contributionsCents = $config->inpsMode === InpsMode::Percentage
            ? (int) round($taxableIncomeCents * ($config->inpsPercentage ?? 0.0) / 100)
            : ($config->inpsFixedAnnualCents ?? 0);
        $substituteTaxCents = (int) round(
            max(0, $taxableIncomeCents - $contributionsCents) * $config->substituteTaxRatePercent / 100
        );

        return new TaxEstimate(
            $revenueCents,
            $taxableIncomeCents,
            $contributionsCents,
            $substituteTaxCents,
            $contributionsCents + $substituteTaxCents,
        );
    }
}
```

- [ ] **Step 4: Eseguire i test**

Run: `vendor/bin/pest tests/Feature/Services/ForfettarioTaxEstimatorTest.php`
Expected: 5 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Enums/InpsMode.php app/Services/Forfettario tests/Feature/Services/ForfettarioTaxEstimatorTest.php
git commit -m "feat: add forfettario tax estimator"
```

---

### Task 2: Impostazioni fiscali del tenant

**Files:**
- Modify: `app/Filament/Pages/Tenancy/Schemas/TenantSettingsForm.php` (nuova sezione dopo "Configurazione slot", prima di "Email (Mailgun)")
- Modify: `app/Models/Tenant.php` (PHPDoc `$settings` + metodo accessorio)
- Modify: `tests/Feature/Filament/Pages/EditTenantSettingsTest.php` (aggiornare aspettative esistenti + nuovo describe)
- Create: `tests/Feature/Models/TenantTaxConfigurationTest.php`

**Interfaces:**
- Consumes: `TaxConfiguration`, `InpsMode` (Task 1).
- Produces: chiavi `settings`: `tax_coefficient` (float, default 78), `tax_rate` (float, default 15), `inps_mode` ('fixed'|'percentage'|null, **default null**), `inps_fixed_amount` (euro int, richiesto se mode=fixed), `inps_percentage` (float, richiesta se mode=percentage). `Tenant::forfettarioTaxConfiguration(): ?TaxConfiguration` (null se incompleto).

- [ ] **Step 1: Scrivere i test fallimentari**

In `EditTenantSettingsTest.php`, aggiornare il test `settings page loads with tenant data` aggiungendo alle aspettative di `'settings'`:

```php
'tax_coefficient' => 78,
'tax_rate' => 15,
'inps_mode' => null,
'inps_fixed_amount' => null,
'inps_percentage' => null,
```

e aggiungere in fondo al file:

```php
describe('saving fiscal settings', function () {
    test('can save forfettario tax settings with percentage inps', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->fillForm([
                'settings' => [
                    'slot_duration_minutes' => 30,
                    'buffer_minutes' => 15,
                    'tax_coefficient' => 78,
                    'tax_rate' => 5,
                    'inps_mode' => 'percentage',
                    'inps_fixed_amount' => null,
                    'inps_percentage' => 26.07,
                ],
            ])
            ->call('save')
            ->assertNotified();

        $this->tenant->refresh();

        expect($this->tenant->settings['tax_coefficient'])->toBe(78)
            ->and($this->tenant->settings['tax_rate'])->toBe(5)
            ->and($this->tenant->settings['inps_mode'])->toBe('percentage')
            ->and($this->tenant->settings['inps_percentage'])->toBe(26.07);
    });

    test('fixed annual contribution is required when inps mode is fixed', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->fillForm([
                'settings' => [
                    'tax_coefficient' => 78,
                    'tax_rate' => 15,
                    'inps_mode' => 'fixed',
                    'inps_fixed_amount' => null,
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['settings.inps_fixed_amount']);
    });
});
```

Nuovo file `tests/Feature/Models/TenantTaxConfigurationTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\InpsMode;
use App\Models\Tenant;
use App\Services\Forfettario\TaxConfiguration;

test('builds the tax configuration from tenant settings', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'tax_coefficient' => 78,
            'tax_rate' => 15,
            'inps_mode' => 'fixed',
            'inps_fixed_amount' => 4500,
        ],
    ]);

    $config = $tenant->forfettarioTaxConfiguration();

    expect($config)->toBeInstanceOf(TaxConfiguration::class)
        ->and($config->inpsMode)->toBe(InpsMode::Fixed)
        ->and($config->inpsFixedAnnualCents)->toBe(450_000)
        ->and($config->coefficientPercent)->toBe(78.0);
});

test('returns null when the fiscal settings are incomplete', function (array $settings) {
    $tenant = Tenant::factory()->create(['settings' => $settings]);

    expect($tenant->forfettarioTaxConfiguration())->toBeNull();
})->with([
    'empty settings' => [[]],
    'missing inps amount' => [['tax_coefficient' => 78, 'tax_rate' => 15, 'inps_mode' => 'fixed']],
    'missing inps percentage' => [['tax_coefficient' => 78, 'tax_rate' => 15, 'inps_mode' => 'percentage']],
    'missing mode' => [['tax_coefficient' => 78, 'tax_rate' => 15]],
]);
```

- [ ] **Step 2: Eseguire e verificare i fallimenti**

Run: `vendor/bin/pest tests/Feature/Filament/Pages/EditTenantSettingsTest.php tests/Feature/Models/TenantTaxConfigurationTest.php`
Expected: FAIL (nuovi campi assenti nel form; metodo su Tenant inesistente).

- [ ] **Step 3: Implementare form e accessorio**

In `TenantSettingsForm.php` importare `App\Enums\InpsMode` e `Filament\Forms\Get`, e inserire tra "Configurazione slot" e "Email (Mailgun)":

```php
Section::make('Fiscalità (regime forfettario)')
    ->description('Parametri per la stima di imposte e contributi nella pagina Fatturato e tasse.')
    ->aside()
    ->schema([
        TextInput::make('settings.tax_coefficient')
            ->label('Coefficiente di redditività (%)')
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->default(78)
            ->suffix('%'),

        TextInput::make('settings.tax_rate')
            ->label('Aliquota imposta sostitutiva (%)')
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->default(15)
            ->suffix('%')
            ->helperText('15% ordinaria, 5% per nuove attività nei primi 5 anni.'),

        Select::make('settings.inps_mode')
            ->label('Regime contributivo INPS')
            ->options(InpsMode::class)
            ->placeholder('Seleziona il regime')
            ->live(),

        TextInput::make('settings.inps_fixed_amount')
            ->label('Contributi fissi annui (€)')
            ->numeric()
            ->minValue(0)
            ->suffix('€')
            ->visible(fn (Get $get): bool => $get('settings.inps_mode') === InpsMode::Fixed->value)
            ->required(fn (Get $get): bool => $get('settings.inps_mode') === InpsMode::Fixed->value),

        TextInput::make('settings.inps_percentage')
            ->label('Aliquota INPS (%)')
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->step(0.01)
            ->suffix('%')
            ->visible(fn (Get $get): bool => $get('settings.inps_mode') === InpsMode::Percentage->value)
            ->required(fn (Get $get): bool => $get('settings.inps_mode') === InpsMode::Percentage->value),
    ])
    ->columns(1)
    ->collapsible(),
```

In `Tenant.php`: aggiungere al PHPDoc di `$settings` le chiavi `tax_coefficient?: float, tax_rate?: float, inps_mode?: string, inps_fixed_amount?: int, inps_percentage?: float`; importare `App\Enums\InpsMode` e `App\Services\Forfettario\TaxConfiguration`; aggiungere (accanto agli altri accessori `privacy*`):

```php
/**
 * Forfettario tax parameters, or null while the fiscal section is
 * incomplete (no defaults are assumed: estimates must be opt-in).
 */
public function forfettarioTaxConfiguration(): ?TaxConfiguration
{
    $settings = $this->settings;
    $coefficient = $settings['tax_coefficient'] ?? null;
    $rate = $settings['tax_rate'] ?? null;
    $inpsMode = InpsMode::tryFrom($settings['inps_mode'] ?? '');

    if ($coefficient === null || $rate === null || $inpsMode === null) {
        return null;
    }

    $fixedAmount = $settings['inps_fixed_amount'] ?? null;
    $percentage = $settings['inps_percentage'] ?? null;

    if ($inpsMode === InpsMode::Fixed && $fixedAmount === null) {
        return null;
    }

    if ($inpsMode === InpsMode::Percentage && $percentage === null) {
        return null;
    }

    return new TaxConfiguration(
        coefficientPercent: (float) $coefficient,
        substituteTaxRatePercent: (float) $rate,
        inpsMode: $inpsMode,
        inpsFixedAnnualCents: $fixedAmount !== null ? (int) round(((float) $fixedAmount) * 100) : null,
        inpsPercentage: $percentage !== null ? (float) $percentage : null,
    );
}
```

- [ ] **Step 4: Eseguire i test**

Run: `vendor/bin/pest tests/Feature/Filament/Pages/EditTenantSettingsTest.php tests/Feature/Models/TenantTaxConfigurationTest.php`
Expected: tutti passed (nessun test esistente rotto grazie al default null di `inps_mode`).

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Pages/Tenancy/Schemas/TenantSettingsForm.php app/Models/Tenant.php tests/Feature/Filament/Pages/EditTenantSettingsTest.php tests/Feature/Models/TenantTaxConfigurationTest.php
git commit -m "feat: add forfettario tax settings to tenant settings"
```

---

### Task 3: Aggregazione ricavi per tenant

**Files:**
- Create: `app/Services/TenantRevenue.php`
- Test: `tests/Feature/Services/TenantRevenueTest.php`

**Interfaces:**
- Consumes: `Tenant::treatments()` (esistente).
- Produces: `TenantRevenue::monthlyForYear(Tenant $tenant, int $year): array<int, int>` (12 slot chiavi 1–12, centesimi, sempre presenti); `totalForYear(Tenant $tenant, int $year): int`; `totalBetween(Tenant $tenant, CarbonInterface $from, CarbonInterface $to): int`. Aggregazione lato PHP (nessuna espressione SQL specifica del driver: psql in dev, sqlite in produzione).

- [ ] **Step 1: Scrivere il test fallimentare**

```php
<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Services\TenantRevenue;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->service = app(TenantRevenue::class);
});

function revenueTreatment(Tenant $tenant, string $createdAt, int $finalPrice): Treatment
{
    $appointment = Appointment::factory()->forTenant($tenant)->create();

    return Treatment::factory()->forAppointment($appointment)
        ->create(['final_price' => $finalPrice, 'created_at' => Carbon::parse($createdAt)]);
}

test('aggregates monthly revenue for a year with zero-filled gaps', function () {
    revenueTreatment($this->tenant, '2026-01-10 10:00', 4500);
    revenueTreatment($this->tenant, '2026-01-20 10:00', 3000);
    revenueTreatment($this->tenant, '2026-03-05 10:00', 5000);

    $monthly = $this->service->monthlyForYear($this->tenant, 2026);

    expect($monthly)->toHaveCount(12)
        ->and($monthly[1])->toBe(7500)
        ->and($monthly[2])->toBe(0)
        ->and($monthly[3])->toBe(5000)
        ->and(array_sum($monthly))->toBe($this->service->totalForYear($this->tenant, 2026))
        ->and($this->service->totalForYear($this->tenant, 2026))->toBe(12500);
});

test('totals revenue between two dates', function () {
    revenueTreatment($this->tenant, '2026-02-01 00:00', 1000);
    revenueTreatment($this->tenant, '2026-04-30 23:59', 2000);
    revenueTreatment($this->tenant, '2026-06-15 12:00', 4000);

    $total = $this->service->totalBetween(
        $this->tenant,
        Carbon::parse('2026-02-01')->startOfDay(),
        Carbon::parse('2026-04-30')->endOfDay(),
    );

    expect($total)->toBe(3000);
});

test('ignores treatments of other tenants', function () {
    $otherTenant = Tenant::factory()->create();

    revenueTreatment($this->tenant, '2026-05-10 10:00', 1000);
    revenueTreatment($otherTenant, '2026-05-11 10:00', 9999);

    expect($this->service->totalForYear($this->tenant, 2026))->toBe(1000);
});
```

- [ ] **Step 2: Eseguire e verificare il fallimento**

Run: `vendor/bin/pest tests/Feature/Services/TenantRevenueTest.php`
Expected: error "Class App\Services\TenantRevenue not found".

- [ ] **Step 3: Implementare il servizio**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TenantRevenue
{
    /** @var array<int, array<int, array<int, int>>> */
    protected array $monthlyCache = [];

    /**
     * Revenue in cents for each month (keys 1-12) of the given year. Months
     * without treatments are zero, so charts always render 12 points.
     *
     * @return array<int, int>
     */
    public function monthlyForYear(Tenant $tenant, int $year): array
    {
        if (isset($this->monthlyCache[$tenant->id][$year])) {
            return $this->monthlyCache[$tenant->id][$year];
        }

        $treatments = $tenant->treatments()
            ->whereBetween('created_at', [Carbon::create($year)->startOfYear(), Carbon::create($year)->endOfYear()])
            ->get(['final_price', 'created_at']);

        $monthly = array_fill(1, 12, 0);

        foreach ($treatments as $treatment) {
            $monthly[$treatment->created_at->month] += $treatment->final_price;
        }

        return $this->monthlyCache[$tenant->id][$year] = $monthly;
    }

    public function totalForYear(Tenant $tenant, int $year): int
    {
        return array_sum($this->monthlyForYear($tenant, $year));
    }

    /** Revenue in cents between the two bounds, inclusive. */
    public function totalBetween(Tenant $tenant, CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) $tenant->treatments()
            ->whereBetween('created_at', [$from, $to])
            ->sum('final_price');
    }
}
```

- [ ] **Step 4: Eseguire i test**

Run: `vendor/bin/pest tests/Feature/Services/TenantRevenueTest.php`
Expected: 3 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/TenantRevenue.php tests/Feature/Services/TenantRevenueTest.php
git commit -m "feat: add tenant revenue aggregation service"
```

---

### Task 4: Pagina "Fatturato e tasse" con widget

**Files:**
- Create: `app/Filament/Pages/RevenueDashboard.php`, `app/Filament/Pages/RevenueDashboard/Widgets/RevenueStats.php`, `app/Filament/Pages/RevenueDashboard/Widgets/RevenueChart.php`
- Test: `tests/Feature/Filament/Pages/RevenueDashboardTest.php`, `tests/Feature/Filament/Widgets/RevenueStatsTest.php`

**Interfaces:**
- Consumes: `ForfettarioTaxEstimator::estimate()` + `REVENUE_LIMIT_CENTS`, `Tenant::forfettarioTaxConfiguration()`, `TenantRevenue` (Task 1–3), `Filament::getTenant()`.
- Produces: pagina raggiungibile in navigazione dopo il calendario (`navigationSort = 10`), visibile solo a `UserRole::Admin`; helper statico condiviso `RevenueDashboard::money(int $cents): string`.

- [ ] **Step 1: Scrivere i test fallimenti**

`tests/Feature/Filament/Pages/RevenueDashboardTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

describe('authorization', function () {
    test('admin can access the revenue dashboard', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(\App\Filament\Pages\RevenueDashboard::class)->assertOk();
    });

    test('staff cannot access the revenue dashboard', function () {
        $staff = User::factory()->create();
        $staff->tenants()->attach($this->tenant);

        bootFilamentPanelAs($staff, $this->tenant);

        Livewire::test(\App\Filament\Pages\RevenueDashboard::class)->assertStatus(404);
    });

    test('admin from another tenant cannot access the revenue dashboard', function () {
        $otherTenant = Tenant::factory()->create();
        $otherAdmin = User::factory()->admin()->create();
        $otherAdmin->tenants()->attach($otherTenant);

        bootFilamentPanelAs($otherAdmin, $this->tenant);

        Livewire::test(\App\Filament\Pages\RevenueDashboard::class)->assertStatus(404);
    });
});

describe('content', function () {
    test('shows the unconfigured notice when fiscal settings are missing', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(\App\Filament\Pages\RevenueDashboard::class)
            ->assertOk()
            ->assertSeeText('Stima tasse non disponibile');
    });

    test('shows the estimate breakdown when fiscal settings are configured', function () {
        $this->tenant->forceFill(['settings' => [
            'tax_coefficient' => 78,
            'tax_rate' => 15,
            'inps_mode' => 'percentage',
            'inps_percentage' => 26,
        ]])->save();

        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(\App\Filament\Pages\RevenueDashboard::class)
            ->assertOk()
            ->assertSeeText('Imponibile')
            ->assertSeeText('Imposta sostitutiva')
            ->assertSeeText('Accantonamento consigliato');
    });
});
```

`tests/Feature/Filament/Widgets/RevenueStatsTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\Pages\RevenueDashboard\Widgets\RevenueStats;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

it('shows year to date revenue and the 85k threshold usage', function () {
    $appointment = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($appointment)
        ->create(['final_price' => 10_000, 'created_at' => Carbon::now()->subDays(3)]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(RevenueStats::class)
        ->assertOk()
        ->assertSeeText('Fatturato '.now()->year)
        ->assertSeeText(Number::currency(100.0, in: 'EUR', locale: 'it'))
        ->assertSeeText('Soglia forfettario');
});

it('shows the estimated taxes when fiscal settings are configured', function () {
    $this->tenant->forceFill(['settings' => [
        'tax_coefficient' => 78,
        'tax_rate' => 15,
        'inps_mode' => 'percentage',
        'inps_percentage' => 26,
    ]])->save();

    $appointment = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($appointment)
        ->create(['final_price' => 10_000, 'created_at' => Carbon::now()->subDays(3)]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    // €100 fatturato: imponibile 78, INPS 20,28, imposta 8,66, totale 28,94
    Livewire::test(RevenueStats::class)
        ->assertOk()
        ->assertSeeText('Stima tasse anno')
        ->assertSeeText(Number::currency(28.94, in: 'EUR', locale: 'it'))
        ->assertSeeText('Da accantonare');
});

it('does not count other tenants treatments', function () {
    $otherTenant = Tenant::factory()->create();
    $otherAppointment = Appointment::factory()->forTenant($otherTenant)->create();
    Treatment::factory()->forAppointment($otherAppointment)
        ->create(['final_price' => 99_999, 'created_at' => Carbon::now()->subDays(3)]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(RevenueStats::class)
        ->assertOk()
        ->assertSeeText(Number::currency(0.0, in: 'EUR', locale: 'it'));
});
```

- [ ] **Step 2: Eseguire e verificare i fallimenti**

Run: `vendor/bin/pest tests/Feature/Filament/Pages/RevenueDashboardTest.php tests/Feature/Filament/Widgets/RevenueStatsTest.php`
Expected: error "Page class not found".

- [ ] **Step 3: Implementare pagina e widget**

```php
// app/Filament/Pages/RevenueDashboard.php
<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Pages\RevenueDashboard\Widgets\RevenueChart;
use App\Filament\Pages\RevenueDashboard\Widgets\RevenueStats;
use App\Filament\Pages\Tenancy\EditTenantSettings;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Forfettario\ForfettarioTaxEstimator;
use App\Services\TenantRevenue;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;

class RevenueDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Fatturato e tasse';

    protected static ?string $title = 'Fatturato e tasse';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role === UserRole::Admin;
    }

    /** @return array<class-string<RevenueChart|RevenueStats>> */
    public function getHeaderWidgets(): array
    {
        return [RevenueStats::class, RevenueChart::class];
    }

    public function content(Schema $schema): Schema
    {
        $config = Filament::getTenant()?->forfettarioTaxConfiguration();

        if ($config === null) {
            return $schema->components([
                Section::make('Stima tasse non disponibile')
                    ->description('Compila la sezione Fiscalità (regime forfettario) nelle impostazioni dell\'attività per vedere la stima di imposte e contributi.')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->schema([
                        TextEntry::make('fiscal_settings')
                            ->label('')
                            ->state('Apri le impostazioni')
                            ->url(fn (): string => EditTenantSettings::getUrl())
                            ->color('primary'),
                    ]),
            ]);
        }

        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();
        $revenueCents = app(TenantRevenue::class)->totalForYear($tenant, now()->year);
        $estimate = app(ForfettarioTaxEstimator::class)->estimate($revenueCents, $config);

        return $schema->components([
            Section::make('Come si calcola la stima')
                ->description('Regime forfettario: l\'imposta sostitutiva si applica all\'imponibile ridotto dai contributi previdenziali, che restano comunque dovuti.')
                ->icon(Heroicon::OutlinedCalculator)
                ->columns(3)
                ->schema([
                    TextEntry::make('revenue')
                        ->label('Fatturato '.now()->year)
                        ->state(self::money($revenueCents)),
                    TextEntry::make('taxable_income')
                        ->label("Imponibile (coefficiente {$config->coefficientPercent}%)")
                        ->state(self::money($estimate->taxableIncomeCents))
                        ->helperText('Fatturato × coefficiente di redditività'),
                    TextEntry::make('inps')
                        ->label('Contributi INPS stimati')
                        ->state(self::money($estimate->inpsContributionsCents))
                        ->helperText($config->inpsMode->getLabel()),
                    TextEntry::make('substitute_tax')
                        ->label("Imposta sostitutiva ({$config->substituteTaxRatePercent}%)")
                        ->state(self::money($estimate->substituteTaxCents))
                        ->helperText('Applicata all\'imponibile al netto dei contributi'),
                    TextEntry::make('total_tax')
                        ->label('Totale tasse stimate')
                        ->state(self::money($estimate->totalTaxCents))
                        ->color('danger'),
                    TextEntry::make('set_aside')
                        ->label('Accantonamento consigliato')
                        ->state(Number::percentage($estimate->setAsideRate() * 100, maxPrecision: 1, locale: 'it'))
                        ->helperText('Quota del fatturato da mettere da parte, contributi inclusi')
                        ->color('success'),
                ]),
        ]);
    }

    public static function money(int $cents): string
    {
        return Number::currency($cents / 100, in: 'EUR', locale: 'it');
    }
}
```

```php
// app/Filament/Pages/RevenueDashboard/Widgets/RevenueStats.php
<?php

declare(strict_types=1);

namespace App\Filament\Pages\RevenueDashboard\Widgets;

use App\Filament\Pages\RevenueDashboard;
use App\Models\Tenant;
use App\Services\Forfettario\ForfettarioTaxEstimator;
use App\Services\TenantRevenue;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class RevenueStats extends BaseWidget
{
    protected ?string $pollingInterval = null;

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();
        $revenue = app(TenantRevenue::class);
        $year = now()->year;

        $ytd = $revenue->totalForYear($tenant, $year);
        $samePeriodLastYear = $revenue->totalBetween(
            $tenant,
            now()->subYear()->startOfYear(),
            now()->subYear()->startOfDay(),
        );

        $config = $tenant->forfettarioTaxConfiguration();
        $estimate = $config !== null
            ? app(ForfettarioTaxEstimator::class)->estimate($ytd, $config)
            : null;

        return [
            Stat::make("Fatturato {$year}", RevenueDashboard::money($ytd))
                ->description($this->yoyDescription($ytd, $samePeriodLastYear))
                ->descriptionIcon($this->yoyIcon($ytd, $samePeriodLastYear))
                ->descriptionColor($this->yoyColor($ytd, $samePeriodLastYear)),

            $estimate !== null
                ? Stat::make('Stima tasse anno', RevenueDashboard::money($estimate->totalTaxCents))
                    ->description('INPS '.RevenueDashboard::money($estimate->inpsContributionsCents)
                        .' + imposta '.RevenueDashboard::money($estimate->substituteTaxCents))
                    ->descriptionColor('warning')
                : Stat::make('Stima tasse anno', '—')
                    ->description('Configura la sezione Fiscalità nelle impostazioni')
                    ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                    ->descriptionColor('warning'),

            $estimate !== null
                ? Stat::make('Da accantonare', Number::percentage($estimate->setAsideRate() * 100, maxPrecision: 1, locale: 'it'))
                    ->description('≈ '.RevenueDashboard::money((int) round($estimate->setAsideRate() * 10_000)).' ogni 100 € incassati')
                    ->descriptionColor('success')
                : Stat::make('Da accantonare', '—')
                    ->description('Richiede la configurazione fiscale')
                    ->descriptionColor('gray'),

            Stat::make('Soglia forfettario', Number::percentage(
                $ytd / ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS * 100,
                maxPrecision: 1,
                locale: 'it',
            ))
                ->description(RevenueDashboard::money($ytd).' su '.RevenueDashboard::money(ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS))
                ->descriptionIcon(Heroicon::OutlinedFlag)
                ->descriptionColor($ytd / ForfettarioTaxEstimator::REVENUE_LIMIT_CENTS >= 0.8 ? 'danger' : 'gray'),
        ];
    }

    private function yoyDescription(int $ytd, int $samePeriodLastYear): string
    {
        if ($samePeriodLastYear === 0) {
            return 'Nessun dato nello stesso periodo dell\'anno scorso';
        }

        $delta = ($ytd - $samePeriodLastYear) / $samePeriodLastYear * 100;

        return ($delta >= 0 ? '+' : '').Number::percentage($delta, maxPrecision: 1, locale: 'it')
            .' vs stesso periodo '.(now()->year - 1);
    }

    private function yoyIcon(int $ytd, int $samePeriodLastYear): Heroicon
    {
        if ($samePeriodLastYear === 0) {
            return Heroicon::OutlinedMinus;
        }

        return $ytd >= $samePeriodLastYear ? Heroicon::OutlinedTrendingUp : Heroicon::OutlinedTrendingDown;
    }

    /** @return 'success' | 'danger' | 'gray' */
    private function yoyColor(int $ytd, int $samePeriodLastYear): string
    {
        if ($samePeriodLastYear === 0) {
            return 'gray';
        }

        return $ytd >= $samePeriodLastYear ? 'success' : 'danger';
    }
}
```

```php
// app/Filament/Pages/RevenueDashboard/Widgets/RevenueChart.php
<?php

declare(strict_types=1);

namespace App\Filament\Pages\RevenueDashboard\Widgets;

use App\Models\Tenant;
use App\Services\TenantRevenue;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Fatturato mensile';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array{datasets: array<int, array{label: string, data: array<int, float>}>, labels: array<int, string>}
     */
    protected function getData(): array
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();
        $revenue = app(TenantRevenue::class);
        $year = now()->year;

        return [
            'datasets' => [
                [
                    'label' => (string) $year,
                    'data' => $this->inEuros($revenue->monthlyForYear($tenant, $year)),
                ],
                [
                    'label' => (string) ($year - 1),
                    'data' => $this->inEuros($revenue->monthlyForYear($tenant, $year - 1)),
                ],
            ],
            'labels' => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
        ];
    }

    /**
     * @param  array<int, int>  $monthlyCents
     * @return array<int, float>
     */
    private function inEuros(array $monthlyCents): array
    {
        return array_map(static fn (int $cents): float => round($cents / 100, 2), array_values($monthlyCents));
    }
}
```

Nota implementativa: nessun `$view` custom — la view predefinita `filament-panels::pages.page` renderizza header widget + `content()`. Se `TextEntry::state()`/`->url()` o `Section::icon()` non esistessero con queste firme in v5.7, verificarli con `grep` nel vendor (`filament/infolists/src/Components/TextEntry.php`) prima di cambiare approccio — la logica resta invariata.

- [ ] **Step 4: Eseguire i test**

Run: `vendor/bin/pest tests/Feature/Filament/Pages/RevenueDashboardTest.php tests/Feature/Filament/Widgets/RevenueStatsTest.php`
Expected: tutti passed. Se l'asserzione su `Number::currency(28.94, ...)` non combacia per arrotondamenti, calcolare il valore atteso con la stessa formula del widget (`RevenueDashboard::money(2894)`) invece del letterale.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Pages/RevenueDashboard.php app/Filament/Pages/RevenueDashboard tests/Feature/Filament/Pages/RevenueDashboardTest.php tests/Feature/Filament/Widgets/RevenueStatsTest.php
git commit -m "feat: add revenue and taxes dashboard page"
```

---

### Task 5: Verifica finale e pulizia

**Files:** nessun nuovo file.

- [ ] **Step 1:** Formattazione: `vendor/bin/pint --dirty --format agent`
- [ ] **Step 2:** Static analysis: `vendor/bin/phpstan analyse` (livello da `phpstan.neon.dist`) — attendersi 0 errori
- [ ] **Step 3:** Suite completa: `vendor/bin/pest` — attendersi tutti verdi, incluse le suite esistenti (nessuna regressione: `EditTenantSettingsTest`, `DashboardTest`, isolation tests)
- [ ] **Step 4:** Verifica manuale veloce (opzionale): ambiente locale `make up` + `make php-shell`, login come Admin → voce "Fatturato e tasse" in navigazione; come Staff la voce non deve comparire. Configurare la sezione Fiscalità nelle impostazioni e ricontrollare i numeri della stima.
- [ ] **Step 5:** Se tutto verde, il ramo è pronto (commit già fatti per task; nessun commit finale necessario).

## Verifica end-to-end (riepilogo)

1. `vendor/bin/pest` — intera suite verde
2. `vendor/bin/phpstan analyse` — 0 errori
3. `vendor/bin/pint --dirty --format agent` — nessuna differenza residua
4. Browser: pagina visibile solo ad Admin; senza configurazione → avviso con link alle impostazioni; con configurazione → stats (fatturato, stima tasse, accantonamento, soglia 85k), grafico mensile corrente vs precedente, dettaglio del calcolo

## Non-goal (v1, esplicitamente esclusi)

- Tracciamento incassi/pagamenti (data ricavo = `treatments.created_at`, decisione concordata)
- Calcolo meccanico acconto/saldo e rate INPS con importi da versare per scadenza
- Alert automatici sulla soglia, esportazioni, ricavi da vendita prodotti, aggiornamento della wiki (non richiesto: il repo vieta file di documentazione non richiesti)
