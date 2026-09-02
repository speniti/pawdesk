<?php

declare(strict_types=1);

use App\Filament\Widgets\AppointmentCalendar;
use App\Filament\Widgets\CustomerCountWidget;
use App\Filament\Widgets\PetCountWidget;
use App\Filament\Widgets\TodayAppointmentsWidget;
use App\Filament\Widgets\UpcomingAppointmentsWidget;
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
        ->assertSeeHtml('TodayAppointmentsWidget')
        ->assertSeeHtml('CustomerCountWidget')
        ->assertSeeHtml('PetCountWidget')
        ->assertSeeHtml('UpcomingAppointmentsWidget')
        ->assertSeeHtml('AppointmentCalendar');

    Livewire::test(TodayAppointmentsWidget::class)->assertSeeText('Appuntamenti di oggi');
    Livewire::test(CustomerCountWidget::class)->assertSeeText('Clienti');
    Livewire::test(PetCountWidget::class)->assertSeeText('Animali');
    Livewire::test(UpcomingAppointmentsWidget::class)->assertSeeText('Prossimi appuntamenti');
});

test('dashboard widgets are ordered before the calendar', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    $widgets = collect(filament()->getWidgets());

    expect($widgets->values()->all())->toBe([
        TodayAppointmentsWidget::class,
        CustomerCountWidget::class,
        PetCountWidget::class,
        UpcomingAppointmentsWidget::class,
        AppointmentCalendar::class,
    ]);
});
