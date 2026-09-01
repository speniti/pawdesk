<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\Treatment;

class TreatmentService
{
    /**
     * Generate the treatment record for a completed appointment, or return the
     * existing one untouched: staff edits always win over regeneration.
     *
     * Prepopulates duration and price from the appointment_service pivot sums.
     *
     * Known limitations, accepted by design:
     * - If a caller updates the appointment outside a transaction and the update
     *   fails after generation, an orphan treatment may remain; it is reused as-is
     *   by the next completion attempt (idempotency).
     * - An appointment created directly as completed gets zero totals, because the
     *   services pivot is synced after the create; staff can fix them in the edit form.
     */
    public function generateFromAppointment(Appointment $appointment): Treatment
    {
        /**
         * @var array{total_price: int, total_duration: int} $totals
         */
        $totals = $this->pivotTotals($appointment);

        /** @var Treatment */
        return $appointment->treatment()->firstOrCreate([
            'appointment_id' => $appointment->id,
        ], [
            'tenant_id' => $appointment->tenant_id,
            'customer_id' => $appointment->customer_id,
            'pet_id' => $appointment->pet_id,
            'actual_duration_minutes' => $totals['total_duration'],
            'final_price' => $totals['total_price'],
        ]);
    }

    /**
     * @return array{total_price: int, total_duration: int}
     */
    private function pivotTotals(Appointment $appointment): array
    {
        $services = $appointment->loadMissing('services')->services;

        return [
            'total_price' => (int) $services->sum(static fn ($service): int => (int) $service->pivot->applied_price),
            'total_duration' => (int) $services->sum(static fn ($service): int => (int) $service->pivot->duration_minutes),
        ];
    }
}
