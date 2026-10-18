// Dates arrive either as "YYYY-MM-DD" or, when a controller passes a raw model
// with a `date` cast, as a full ISO timestamp. Only the date part is used, and
// it is parsed as local midnight so the calendar day never shifts.
// dry-keep: the JS twin of App\Support\Dates — PHP is the source; change both together.
const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const pad = (n) => String(n).padStart(2, '0');

export function useFormatDate() {
    // "01 Oct 2026" (Dates::SHORT); with { weekday: true } "Thu, 01 Oct 2026" (Dates::DAY).
    function formatDate(dateStr, { weekday = false } = {}) {
        const date = new Date(String(dateStr).slice(0, 10) + 'T00:00:00');
        const day = `${pad(date.getDate())} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
        return weekday ? `${WEEKDAYS[date.getDay()]}, ${day}` : day;
    }

    // "18 Oct, 2:05 PM" in the viewer's time: a moment, not a calendar day.
    function formatDateTime(iso) {
        const date = new Date(iso);
        const hour = date.getHours() % 12 || 12;
        const half = date.getHours() < 12 ? 'AM' : 'PM';
        return `${pad(date.getDate())} ${MONTHS[date.getMonth()]}, ${hour}:${pad(date.getMinutes())} ${half}`;
    }

    return { formatDate, formatDateTime };
}
