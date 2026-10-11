// Search dates travel in the query string, from the results to the hotel page
// to the booking form. `stay` is the server's { check_in, check_out, nights }.
export function stayQuery(stay) {
    if (!stay) return '';
    return `?${new URLSearchParams({ check_in: stay.check_in, check_out: stay.check_out })}`;
}
