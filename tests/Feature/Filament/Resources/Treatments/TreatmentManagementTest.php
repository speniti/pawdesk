<?php

declare(strict_types=1);

use App\Filament\Resources\Treatments\Pages\EditTreatment;
use App\Filament\Resources\Treatments\Pages\ListTreatments;
use App\Filament\Resources\Treatments\Pages\ViewTreatment;
use App\Filament\Resources\Treatments\TreatmentResource;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;
use Filament\Forms\Components\Select;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->staff = User::factory()->forTenant($this->tenant)->create();

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
            'visible_to_customer' => 0,
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

test('view treatment page shows products used as bulleted list', function () {
    $this->treatment->update(['products_used' => "Shampoo medicato\nBalsamo\nPiega"]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewTreatment::class, ['record' => $this->treatment->id])
        ->assertOk()
        ->assertSeeTextInOrder(['Shampoo medicato', 'Balsamo', 'Piega']);
});

test('view treatment page shows placeholder when no products used', function () {
    $this->treatment->update(['products_used' => null]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewTreatment::class, ['record' => $this->treatment->id])
        ->assertOk()
        ->assertSeeText('Nessun prodotto');
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

test('treatments table exposes the configured filters scoped to the tenant', function () {
    $foreignTenant = Tenant::factory()->create();
    Customer::factory()->for($foreignTenant)->create(['last_name' => 'RossiDiAltroTenant']);
    Pet::factory()->for($foreignTenant)->create(['name' => 'GattoDiUnAltroTenant']);
    Customer::factory()->for($this->tenant)->create(['last_name' => 'BianchiLocale']);
    Pet::factory()->for($this->tenant)->create(['name' => 'CaneLocale']);

    bootFilamentPanelAs($this->admin, $this->tenant);

    $component = Livewire::test(ListTreatments::class)
        ->assertTableFilterExists('periodo')
        ->assertTableFilterExists('visible_to_customer')
        ->assertTableFilterExists('cliente')
        ->assertTableFilterExists('animale');

    // Dalla 5.12 i select searchable+preload renderizzano le opzioni come
    // componente async: non vivono più nell'HTML di pagina, quindi un pin
    // stringa sul markup sarebbe vacuo (i assertDontSee passerebbero anche
    // con lo scoping rotto). Il pin cammina nello schema dei filtri e
    // asserisce sull'ARRAY di opzioni che la dropdown consuma davvero.
    // L'esito è garantito da DUE livelli indipendenti (auto-tenancy delle
    // relationship di Filament 5 + modifyQueryUsing esplicito di #93, che
    // resta come assicurazione contro cambi di default vendor): questo
    // assert è la sentinella che scatta se ENTRAMBI i livelli vengono meno.
    $filterOptions = function (string $filterName) use ($component): array {
        $group = collect($component->instance()->getTableFiltersForm()->getComponents())
            ->first(fn ($schemaComponent) => str_contains((string) $schemaComponent->getKey(), $filterName));

        $select = collect($group->getChildSchema()->getComponents())
            ->first(fn ($schemaComponent) => $schemaComponent instanceof Select);

        return array_values($select->getOptions());
    };

    $cliente = $filterOptions('cliente');
    $animale = $filterOptions('animale');

    expect(collect($cliente)->contains(fn ($label) => str_contains($label, 'BianchiLocale')))->toBeTrue()
        ->and(collect($animale)->contains(fn ($label) => str_contains($label, 'CaneLocale')))->toBeTrue()
        // I marker del tenant esterno NON devono mai comparire come opzioni.
        ->and(collect($cliente)->some(fn ($label) => str_contains($label, 'RossiDiAltroTenant')))->toBeFalse()
        ->and(collect($animale)->some(fn ($label) => str_contains($label, 'GattoDiUnAltroTenant')))->toBeFalse();
});

test('the period filter narrows treatments to the appointment date range', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $inRange = Treatment::factory()->forAppointment(
        Appointment::factory()->forTenant($this->tenant)->create(['start_time' => '2026-06-10 09:00:00']),
    )->create(['final_price' => 1000]);
    $outOfRange = Treatment::factory()->forAppointment(
        Appointment::factory()->forTenant($this->tenant)->create(['start_time' => '2026-12-20 09:00:00']),
    )->create(['final_price' => 2000]);

    Livewire::test(ListTreatments::class)
        ->filterTable('periodo', data: ['dal' => '2026-06-01', 'al' => '2026-06-30'])
        ->assertSee('Dal 01/06/2026')
        ->assertSee('Al 30/06/2026')
        ->assertCanSeeTableRecords([$inRange])
        ->assertCanNotSeeTableRecords([$outOfRange]);
});

test('the period filter accepts a single bound', function (array $data, string $outOfRangeDate) {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $inRange = Treatment::factory()->forAppointment(
        Appointment::factory()->forTenant($this->tenant)->create(['start_time' => '2026-06-10 09:00:00']),
    )->create();
    $outOfRange = Treatment::factory()->forAppointment(
        Appointment::factory()->forTenant($this->tenant)->create(['start_time' => $outOfRangeDate]),
    )->create();

    Livewire::test(ListTreatments::class)
        ->filterTable('periodo', data: $data)
        ->assertCanSeeTableRecords([$inRange])
        ->assertCanNotSeeTableRecords([$outOfRange]);
})->with([
    'solo dal' => [['dal' => '2026-06-01'], '2026-05-15 09:00:00'],
    'solo al' => [['al' => '2026-06-30'], '2026-12-20 09:00:00'],
]);

test('the customer filter narrows treatments to the selected customer', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $otherCustomer = Customer::factory()->for($this->tenant)->create();
    $otherPet = Pet::factory()->for($this->tenant)->for($otherCustomer)->create();
    $otherTreatment = Treatment::factory()->forAppointment(
        Appointment::factory()->create(['customer_id' => $otherCustomer->id, 'pet_id' => $otherPet->id]),
    )->create(['final_price' => 2000]);

    Livewire::test(ListTreatments::class)
        ->filterTable('cliente', data: ['value' => $this->treatment->customer_id])
        ->assertCanSeeTableRecords([$this->treatment])
        ->assertCanNotSeeTableRecords([$otherTreatment]);
});

test('the visibility filter narrows treatments to the selected visibility', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $hidden = Treatment::factory()->forAppointment(
        Appointment::factory()->forTenant($this->tenant)->create(),
    )->create(['visible_to_customer' => false]);

    Livewire::test(ListTreatments::class)
        ->filterTable('visible_to_customer', data: ['value' => true])
        ->assertCanSeeTableRecords([$this->treatment])
        ->assertCanNotSeeTableRecords([$hidden]);
});
