<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Appointments\Schemas\AppointmentInfolist;
use App\Models\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AppointmentResource extends Resource
{
    protected static bool $isScopedToTenant = true;

    protected static ?string $model = Appointment::class;

    protected static ?string $modelLabel = 'Appuntamento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 40;

    protected static ?string $pluralModelLabel = 'Appuntamenti';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentInfolist::configure($schema);
    }
}
