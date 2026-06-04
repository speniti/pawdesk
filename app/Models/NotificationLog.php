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
