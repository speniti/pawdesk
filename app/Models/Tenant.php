<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InpsMode;
use App\Services\Forfettario\TaxConfiguration;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $slug
 * @property string|null $primary_color
 * @property array<string, array<array{open: string, close: string}>> $opening_hours
 * @property array $notification_settings
 * @property array{
 *     slot_duration_minutes?: int,
 *     buffer_minutes?: int,
 *     tax_coefficient?: float,
 *     tax_rate?: float,
 *     inps_mode?: string,
 *     inps_fixed_amount?: int,
 *     inps_percentage?: float,
 *     privacy_business_name?: string,
 *     privacy_owner_name?: string,
 *     privacy_vat_number?: string,
 *     privacy_business_address?: string,
 *     privacy_contact_email?: string,
 *     privacy_contact_phone?: string,
 * } $settings
 */
#[Fillable(['name', 'slug', 'primary_color', 'opening_hours', 'notification_settings', 'settings'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $attributes = [
        'opening_hours' => '{}',
        'settings' => '{}',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

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

    public function hasMailgunConfigured(): bool
    {
        $settings = $this->notification_settings ?? [];

        return filled($settings['mailgun_api_key'] ?? null)
            && filled($settings['mailgun_domain'] ?? null);
    }

    public function hasVonageConfigured(): bool
    {
        $settings = $this->notification_settings ?? [];

        return filled($settings['vonage_api_key'] ?? null)
            && filled($settings['vonage_api_secret'] ?? null);
    }

    public function mailFromAddress(): ?string
    {
        return $this->notification_settings['mail_from_address'] ?? null;
    }

    public function mailFromName(): ?string
    {
        return $this->notification_settings['mail_from_name'] ?? null;
    }

    public function mailgunApiKey(): ?string
    {
        return $this->notification_settings['mailgun_api_key'] ?? null;
    }

    public function mailgunDomain(): ?string
    {
        return $this->notification_settings['mailgun_domain'] ?? null;
    }

    public function mailgunMailerName(): string
    {
        return "tenant-mailgun-{$this->id}";
    }

    public function mailgunRegion(): string
    {
        return $this->notification_settings['mailgun_region'] ?? 'us';
    }

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    public function privacyBusinessAddress(): ?string
    {
        return $this->settings['privacy_business_address'] ?? null;
    }

    /**
     * Data of the data controller (art. 4 GDPR) shown in the privacy notice.
     * The business name and contact email fall back to the salon name and the
     * mail from address so the notice works before the fields are filled in.
     */
    public function privacyBusinessName(): string
    {
        return $this->settings['privacy_business_name'] ?? $this->name;
    }

    public function privacyContactEmail(): ?string
    {
        return $this->settings['privacy_contact_email'] ?? $this->mailFromAddress();
    }

    public function privacyContactPhone(): ?string
    {
        return $this->settings['privacy_contact_phone'] ?? null;
    }

    public function privacyOwnerName(): ?string
    {
        return $this->settings['privacy_owner_name'] ?? null;
    }

    public function privacyVatNumber(): ?string
    {
        return $this->settings['privacy_vat_number'] ?? null;
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<Treatment, $this>
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function vonageApiKey(): ?string
    {
        return $this->notification_settings['vonage_api_key'] ?? null;
    }

    public function vonageApiSecret(): ?string
    {
        return $this->notification_settings['vonage_api_secret'] ?? null;
    }

    public function vonageSmsSenderId(): ?string
    {
        return $this->notification_settings['vonage_sms_sender_id'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'notification_settings' => 'encrypted:array',
            'settings' => 'array',
        ];
    }
}
