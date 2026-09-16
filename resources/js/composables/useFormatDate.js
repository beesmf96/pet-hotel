// Dates arrive either as "YYYY-MM-DD" or, when a controller passes a raw model
// with a `date` cast, as a full ISO timestamp. Only the date part is used, and
// it is parsed as local midnight so the calendar day never shifts.
export function useFormatDate() {
    function formatDate(dateStr, { weekday = false } = {}) {
        return new Date(String(dateStr).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-US', {
            ...(weekday ? { weekday: 'short' } : {}),
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    }

    return { formatDate };
}
