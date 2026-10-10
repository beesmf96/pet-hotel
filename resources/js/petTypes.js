// The one list of pet types on the frontend. Mirrors App\Enums\PetType: a
// pet's species and a hotel's per-type pricing share these keys.
export const PET_TYPES = [
    { value: 'dog', label: 'Dog' },
    { value: 'cat', label: 'Cat' },
    { value: 'rabbit', label: 'Rabbit' },
    { value: 'bird', label: 'Bird' },
    { value: 'other', label: 'Other' },
];

export function petTypeLabel(value) {
    return PET_TYPES.find((type) => type.value === value)?.label ?? value;
}
