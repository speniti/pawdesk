<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AppointmentSeeder extends Seeder
{
    /**
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Pet>  $pets
     * @param  Collection<string, Service>  $services
     */
    public function run(Tenant $tenant, User $staff, Collection $customers, Collection $pets, Collection $services): void
    {
        if (Appointment::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $schedule = [
            [AppointmentStatus::Requested, now()->addDays(5)->setTime(10, 0), now()->addDays(5)->setTime(11, 0)],
            [AppointmentStatus::Confirmed, now()->addDay()->setTime(14, 0), now()->addDay()->setTime(16, 0)],
            [AppointmentStatus::InProgress, now()->subHour()->setMinute(0), now()->addHour()->setMinute(0)],
            [AppointmentStatus::Completed, now()->subDays(3)->setTime(10, 0), now()->subDays(3)->setTime(12, 0)],
            [AppointmentStatus::Cancelled, now()->subDays(2)->setTime(15, 0), now()->subDays(2)->setTime(16, 30)],
            [AppointmentStatus::NoShow, now()->subDay()->setTime(11, 0), now()->subDay()->setTime(12, 0)],
        ];

        $serviceMap = [
            ['Bagnetto Completo'],
            ['Toelettatura Completa'],
            ['Bagnetto Completo', 'Taglio Unghie'],
            ['Toelettatura Completa'],
            ['Trattamento Antiparassitario'],
            ['De-shedding'],
        ];

        foreach ($schedule as $i => [$status, $start, $end]) {
            $customer = $customers[$i];
            $pet = $pets->first(fn (Pet $p): bool => $p->customer_id === $customer->id) ?? $pets[$i];

            $appointment = Appointment::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'pet_id' => $pet->id,
                'user_id' => $staff->id,
                'status' => $status->value,
                'start_time' => $start,
                'end_time' => $end,
            ]);

            $totalPrice = 0;
            $totalDuration = 0;

            foreach ($serviceMap[$i] as $serviceName) {
                $service = $services[$serviceName];
                $price = $service->size_prices[$pet->size->value] ?? $service->base_price;

                $appointment->services()->attach($service->id, [
                    'applied_price' => $price,
                    'duration_minutes' => $service->duration_minutes,
                ]);

                $totalPrice += $price;
                $totalDuration += $service->duration_minutes;
            }

            if ($status === AppointmentStatus::Completed) {
                Treatment::factory()->forAppointment($appointment)->create([
                    'final_price' => $totalPrice,
                    'actual_duration_minutes' => $totalDuration,
                ]);
            }
        }
    }
}
