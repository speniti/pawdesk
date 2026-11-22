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

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Fatturato e tasse';

    /**
     * Role gate for both the navigation item and the page itself: staff gets
     * a 403 (Filament's abort for inaccessible pages), cross-tenant users a
     * 404 from mount(), mirroring the tenant settings page.
     */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->role === UserRole::Admin;
    }

    public static function money(int $cents): string
    {
        return Number::currency($cents / 100, in: 'EUR', locale: 'it');
    }

    public function content(Schema $schema): Schema
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            return $schema;
        }

        $config = $tenant->forfettarioTaxConfiguration();

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
                    ])
                    ->columns(1),
            ]);
        }

        $revenueCents = app(TenantRevenue::class)->totalForYear($tenant, now()->year);
        $estimate = app(ForfettarioTaxEstimator::class)->estimate($revenueCents, $config);

        return $schema->components([
            Section::make('Come si calcola la stima')
                ->description('Regime forfettario: l\'imposta sostitutiva si applica all\'imponibile ridotto dai contributi previdenziali, che restano comunque dovuti.')
                ->icon(Heroicon::OutlinedCalculator)
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
                ])
                ->columns(3),
        ]);
    }

    /** Same tenant-membership gate as the tenant settings page: cross-tenant users get a 404. */
    public function mount(): void
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        abort_unless(
            $user instanceof User && $tenant instanceof Tenant && $user->canAccessTenant($tenant),
            404,
        );
    }

    /** @return array<class-string<RevenueChart|RevenueStats>> */
    protected function getHeaderWidgets(): array
    {
        return [RevenueStats::class, RevenueChart::class];
    }
}
