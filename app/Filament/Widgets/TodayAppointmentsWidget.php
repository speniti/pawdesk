<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayAppointmentsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        $todayAppointments = $tenant->appointments()
            ->whereDate('start_time', today())
            ->whereIn('status', [
                AppointmentStatus::Requested,
                AppointmentStatus::Confirmed,
                AppointmentStatus::InProgress,
            ])
            ->get();

        return [
            Stat::make('Richiesti', $todayAppointments->where('status', AppointmentStatus::Requested)->count())
                ->description('Appuntamenti di oggi')
                ->color('gray'),
            Stat::make('Confermati', $todayAppointments->where('status', AppointmentStatus::Confirmed)->count())
                ->description('Appuntamenti di oggi')
                ->color('info'),
            Stat::make('In corso', $todayAppointments->where('status', AppointmentStatus::InProgress)->count())
                ->description('Appuntamenti di oggi')
                ->color('warning'),
        ];
    }
}
