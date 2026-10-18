<?php

namespace App\Enums;

/**
 * Labels and badge colours for bookings.status in both panels. The column
 * stays a plain string; this only names its values. The JS labels live in
 * resources/js/Components/Ui/StatusPill.vue.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** A Filament colour name for the status badge. */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Cancelled => 'danger',
            self::Completed => 'info',
        };
    }

    /** color() for a raw column value; gray for anything unknown. */
    public static function colorOf(string $state): string
    {
        return self::tryFrom($state)?->color() ?? 'gray';
    }

    /** @return array<string, string> value => label, for select filters */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $status) => $status->label(), self::cases()),
        );
    }
}
