<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Tenant;

// Coerenza referenziale #83: il pet dell'appuntamento deve appartenere al
// cliente dell'appuntamento, in entrambi gli stati della factory. Il default
// storicizzava due factory Customer separate e forTenant() ne creava due
// ugualmente — un bug mascherato dalla mancanza di assertion sul legame.
test('the appointment factory creates a pet belonging to the appointment customer', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->pet->customer_id)->toBe($appointment->customer_id);
});

test('the appointment factory forTenant state keeps customer and pet in the same tenant', function () {
    $tenant = Tenant::factory()->create();
    $appointment = Appointment::factory()->forTenant($tenant)->create();

    expect($appointment->pet->customer_id)->toBe($appointment->customer_id)
        ->and($appointment->pet->customer->tenant_id)->toBe($tenant->id)
        ->and($appointment->customer->tenant_id)->toBe($tenant->id);
});
