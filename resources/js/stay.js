// Search dates travel in the query string, from the results to the hotel page
// to the booking form. `stay` is the server's { check_in, check_out, nights }.
export function stayQuery(stay) {
    if (!stay) return '';
    return `?${new URLSearchParams({ check_in: stay.check_in, check_out: stay.check_out })}`;
}

// Nights between two "YYYY-MM-DD" dates. The server counts the same way
// (Booking::nights()); the price is nights × the nightly rate.
export function nightsBetween(checkIn, checkOut) {
    return Math.round((new Date(checkOut) - new Date(checkIn)) / 86400000);
}

// "1 night", "3 nights"
export function nightsLabel(nights) {
    return `${nights} night${nights !== 1 ? 's' : ''}`;
}
