<?php

declare(strict_types=1);

namespace App\Filament\Pages\PublicArea;

use App\Models\Tenant;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class PrivacyPolicy extends SimplePage
{
    public Tenant $tenant;

    protected static bool $isDiscovered = false;

    protected Width|string|null $maxContentWidth = Width::ThreeExtraLarge;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.pages.privacy-policy')
                    ->viewData(fn (): array => ['tenant' => $this->tenant]),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Informativa sulla privacy';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Informativa sul trattamento dei dati personali — art. 13 Regolamento UE 2016/679 (GDPR)';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Informativa sulla privacy';
    }

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }
}
