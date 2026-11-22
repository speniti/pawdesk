<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

test('marketing consent column and index are dropped and the migration is reversible', function () {
    expect(Schema::hasColumn('customers', 'marketing_consent_at'))->toBeFalse()
        ->and(Schema::hasIndex('customers', 'customers_tenant_id_marketing_consent_at_index'))->toBeFalse();

    $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();

    expect(Schema::hasColumn('customers', 'marketing_consent_at'))->toBeTrue()
        ->and(Schema::hasIndex('customers', 'customers_tenant_id_marketing_consent_at_index'))->toBeTrue();

    $this->artisan('migrate')->assertSuccessful();

    expect(Schema::hasColumn('customers', 'marketing_consent_at'))->toBeFalse()
        ->and(Schema::hasIndex('customers', 'customers_tenant_id_marketing_consent_at_index'))->toBeFalse();
});
