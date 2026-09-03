<?php

declare(strict_types=1);

use App\Filament\Widgets\AppointmentCalendar;
use App\Models\Tenant;
use App\Models\User;
use Filament\Pages\Dashboard;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('dashboard is accessible after login and shows all widgets', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    // I widget sono lazy per default: la dashboard monta i componenti
    // (i nomi delle classi compaiono nei snapshot Livewire) e il
    // contenuto si verifica renderizzando ciascun widget direttamente
    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSeeHtml('AppointmentCalendar');
});

test('dashboard widgets are ordered before the calendar', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $widgets = collect(filament()->getWidgets());

    expect($widgets->values()->all())->toBe([
        AppointmentCalendar::class,
    ]);
});
