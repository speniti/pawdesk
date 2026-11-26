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
     * - A creation path that lands directly on Completed and syncs the services
     *   pivot afterwards still gets zero totals unless it calls refreshTotals()
     *   after the sync: the calendar widget does exactly that, inside its
     *   transaction (#80). Outside the widget, follow the seeder pattern instead:
     *   create with another status, sync, then update to Completed.
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
     * Recompute the existing treatment's totals from the current pivot, onto
     * the same columns generateFromAppointment uses. No-op when no treatment
     * exists yet.
     *
     * Only safe immediately after the sync that created the treatment within
     * the same operation (#80): staff edits always win, so it must never run
     * for an appointment completed earlier.
     */
    public function refreshTotals(Appointment $appointment): void
    {
        $totals = $this->pivotTotals($appointment);

        $appointment->treatment()->update([
            'actual_duration_minutes' => $totals['total_duration'],
            'final_price' => $totals['total_price'],
        ]);
    }

    /**
     * @return array{total_price: int, total_duration: int}
     */
    private function pivotTotals(Appointment $appointment): array
    {
        // Authoritative read (#80): loadMissing() would return whatever the
        // caller has cached — including the empty collection the created
        // observer caches before the pivot exists — so totals could silently
        // stay zero. A relation query reads the pivot from the database
        // without touching the caller's relation cache.
        $services = $appointment->services()->get();

        return [
            'total_price' => (int) $services->sum(static fn ($service): int => (int) $service->pivot->applied_price),
            'total_duration' => (int) $services->sum(static fn ($service): int => (int) $service->pivot->duration_minutes),
        ];
    }
}
