<?php

declare(strict_types=1);

use App\Filament\Resources\Treatments\Pages\EditTreatment;
use App\Filament\Resources\Treatments\Pages\ListTreatments;
use App\Filament\Resources\Treatments\TreatmentResource;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);

    $this->staff = User::factory()->create();
    $this->staff->tenants()->attach($this->tenant);

    $appointment = Appointment::factory()->forTenant($this->tenant)->create();
    $this->treatment = Treatment::factory()->forAppointment($appointment)->create([
        'actual_duration_minutes' => 60,
        'final_price' => 3500,
    ]);
});

test('user with access can view treatment list', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(ListTreatments::class)->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('user with access can view edit treatment page', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(EditTreatment::class, ['record' => $this->treatment->id])->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('edit treatment page saves notes, products, duration, price and visibility', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditTreatment::class, ['record' => $this->treatment->id])
        ->fillForm([
            'actual_duration_minutes' => 75,
            'final_price' => 25.50,
            'visible_to_customer' => false,
            'notes' => 'Note di test',
            'products_used' => 'Shampoo medicato',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->treatment->refresh())
        ->actual_duration_minutes->toBe(75)
        ->final_price->toBe(2550)
        ->visible_to_customer->toBeFalse()
        ->notes->toBe('Note di test')
        ->products_used->toBe('Shampoo medicato');
});

test('manual treatment creation is not possible', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    expect(TreatmentResource::getPages())->not->toHaveKey('create')
        ->and($this->admin->can('create', Treatment::class))->toBeFalse();
});

test('treatment list shows most recent appointment first', function () {
    $createTreatment = fn (string $startTime): Treatment => Treatment::factory()
        ->forAppointment(Appointment::factory()->forTenant($this->tenant)->create([
            'start_time' => $startTime,
        ]))
        ->create();

    $oldest = $createTreatment(now()->subDays(30)->format('Y-m-d H:i'));
    $middle = $createTreatment(now()->subDays(10)->format('Y-m-d H:i'));
    $newest = $createTreatment(now()->subDay()->format('Y-m-d H:i'));

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ListTreatments::class)
        ->assertCanSeeTableRecords([$newest, $middle, $oldest], inOrder: true);
});
