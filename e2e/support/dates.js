// Stays are booked a few weeks ahead so they are always in the future.
export function daysFromToday(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);
    return date;
}

// A day of next month: always in the future, and a run of days from the 10th
// stays inside one month, which the calendar needs (it knows one month at a time).
export function dayOfNextMonth(day) {
    const date = new Date();
    return new Date(date.getFullYear(), date.getMonth() + 1, day);
}

export const iso = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

export const addDays = (date, days) => {
    const next = new Date(date);
    next.setDate(next.getDate() + days);
    return next;
};

// "October 2030", as the calendar header and the overview filter show it.
export const monthLabel = (date) => date.toLocaleString('en-US', { month: 'long', year: 'numeric' });

// "Fri, 25 Oct 2030", like PHP's 'D, d M Y' in the emails and panel tables.
const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
export const longDate = (date) =>
    `${DAYS[date.getDay()]}, ${String(date.getDate()).padStart(2, '0')} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
