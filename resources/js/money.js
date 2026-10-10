// The JS twin of App\Support\Money. Every price shown on a page goes through
// formatMoney(), so the currency lives here and in config('app.currency').
// Change both together.
export const CURRENCY_SYMBOL = 'RM';

// "RM 1,234.50"; { decimals: 0 } gives "RM 1,235" for "from" prices.
export function formatMoney(amount, { decimals = 2 } = {}) {
    const value = Number(amount ?? 0).toLocaleString('en-MY', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
    return `${CURRENCY_SYMBOL} ${value}`;
}

// Whole sen, so a total is never off by a float rounding error.
export function toSen(amount) {
    return Math.round(Number(amount) * 100);
}
