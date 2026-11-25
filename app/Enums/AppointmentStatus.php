<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasColor, HasLabel
{
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case NoShow = 'no_show';
    case Requested = 'requested';

    /**
     * Whether the state machine permits setting $target: with no current
     * status (creation) only the initial statuses are allowed; otherwise
     * the status must be unchanged or reachable through a transition.
     */
    public static function allows(?self $current, self $target): bool
    {
        return $current === null
            ? in_array($target, self::initialStatuses(), true)
            : ($target === $current || $current->canTransitionTo($target));
    }

    /**
     * Statuses a new appointment may be created with: the entry points of
     * the state machine, i.e. the cases no transition leads to. Derived
     * from the graph so it cannot drift from nextStatuses().
     *
     * @return array<int, self>
     */
    public static function initialStatuses(): array
    {
        $reachable = [];

        foreach (self::cases() as $status) {
            foreach ($status->nextStatuses() as $next) {
                $reachable[$next->value] = true;
            }
        }

        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => ! isset($reachable[$status->value]),
        ));
    }

    /**
     * Resolve the loose representation the form layer hands around (enum
     * instance or backing value), failing closed on anything else.
     */
    public static function tryFromState(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        return is_string($value) ? self::tryFrom($value) : null;
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->nextStatuses(), true);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'gray',
            self::Confirmed => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::NoShow => 'danger',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Richiesto',
            self::Confirmed => 'Confermato',
            self::InProgress => 'In corso',
            self::Completed => 'Completato',
            self::Cancelled => 'Annullato',
            self::NoShow => 'Non presentato',
        };
    }

    /**
     * @return array<int, self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Requested => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::InProgress, self::Cancelled, self::NoShow],
            self::InProgress => [self::Completed, self::Cancelled, self::NoShow],
            default => [],
        };
    }
}
