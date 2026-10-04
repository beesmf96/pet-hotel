<?php

namespace App\Enums;

/**
 * The one list of pet types. A pet's species and a hotel's per-type pricing
 * must use the same keys, or the booking form finds no price for the pet.
 * The JS copy lives in resources/js/petTypes.js.
 */
enum PetType: string
{
    case Dog = 'dog';
    case Cat = 'cat';
    case Rabbit = 'rabbit';
    case Bird = 'bird';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** @return array<string, string> value => label, for select fields */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $type) => $type->label(), self::cases()),
        );
    }
}
