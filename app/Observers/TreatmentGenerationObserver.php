<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\TreatmentService;

/**
 * Generates the treatment record in the same transaction as the status change,
 * so a generation failure aborts the update to completed. Deliberately not
 * after-commit, unlike AppointmentObserver which handles notifications.
 */
class TreatmentGenerationObserver
{
    public function __construct(public readonly TreatmentService $treatmentService) {}

    public function created(Appointment $appointment): void
    {
        if ($appointment->status === AppointmentStatus::Completed) {
            $this->treatmentService->generateFromAppointment($appointment);
        }
    }

    public function updating(Appointment $appointment): void
    {
        if (! $appointment->isDirty('status')
            || $appointment->status !== AppointmentStatus::Completed
            || $appointment->getOriginal('status') === AppointmentStatus::Completed) {
            return;
        }

        $this->treatmentService->generateFromAppointment($appointment);
    }
}
