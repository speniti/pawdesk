<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $tenant_id
 * @property int $customer_id
 * @property int $appointment_id
 * @property string $type
 * @property string $channel
 * @property NotificationStatus $status
 * @property string|null $error_message
 */
#[Fillable(['tenant_id', 'customer_id', 'appointment_id', 'type', 'channel', 'status', 'error_message', 'sent_at', 'failed_at'])]
class NotificationLog extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationLogFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * The most recent pending log for a given appointment notification channel.
     */
    public static function latestPending(int $appointmentId, string $type, string $channel): ?self
    {
        return self::query()
            ->forAppointment($appointmentId)
            ->where('type', $type)
            ->where('channel', $channel)
            ->pending()
            ->latest()
            ->first();
    }

    /**
     * Whether a notification of the given type was already dispatched
     * (sent or still pending in the queue) for the appointment. Failed
     * sends do not count, so they are retried on the next run.
     */
    public static function wasDispatched(int $appointmentId, string $type): bool
    {
        return self::query()
            ->forAppointment($appointmentId)
            ->where('type', $type)
            ->whereIn('status', [NotificationStatus::Sent->value, NotificationStatus::Pending->value])
            ->exists();
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function markFailed(?string $error): void
    {
        $this->update([
            'status' => NotificationStatus::Failed->value,
            'error_message' => $error,
            'failed_at' => now(),
        ]);
    }

    public function markSent(): void
    {
        $this->update([
            'status' => NotificationStatus::Sent->value,
            'sent_at' => now(),
        ]);
    }

    public function markSkipped(string $reason): void
    {
        $this->update([
            'status' => NotificationStatus::Skipped->value,
            'error_message' => $reason,
        ]);
    }

    public function scopeFailed($query): void
    {
        $query->where('status', NotificationStatus::Failed->value);
    }

    public function scopeForAppointment($query, int $appointmentId): void
    {
        $query->where('appointment_id', $appointmentId);
    }

    public function scopePending($query): void
    {
        $query->where('status', NotificationStatus::Pending->value);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function casts(): array
    {
        return [
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
