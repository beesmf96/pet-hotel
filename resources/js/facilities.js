// The one list of hotel facilities on the frontend: the filter checkboxes,
// the chips on a hotel card and the hotel page all read from here.
export const FACILITIES = [
    { value: 'grooming', label: 'Grooming' },
    { value: 'play_area', label: 'Play area' },
    { value: 'vet_care', label: 'Vet care' },
    { value: 'swimming_pool', label: 'Swimming pool' },
    { value: 'training', label: 'Training' },
    { value: 'outdoor_walks', label: 'Outdoor walks' },
    { value: 'webcam', label: 'Live webcam' },
    { value: '24h_care', label: '24h care' },
];

export function facilityLabel(value) {
    return FACILITIES.find((facility) => facility.value === value)?.label ?? value;
}
