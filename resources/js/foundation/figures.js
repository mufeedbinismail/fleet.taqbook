import { user } from './user';

function render(value, places, trimZeros = false) {
    const thousands = user.pref('thousand_separator');
    const decimal = user.pref('decimal_separator');
    const parsed = Number(value) || 0;
    const magnitude = Math.abs(parsed);

    // The half-unit is a hair over, so a value that sits on a halfway point in binary rounds up.
    const scale = 10 ** places;
    const scaled = Math.floor(magnitude * scale + 0.50000000001);
    const whole = Math.floor(scaled / scale)
        .toString()
        .replace(/\B(?=(\d{3})+(?!\d))/g, thousands);
    const fraction = places === 0 ? '' : (scaled % scale).toString().padStart(places, '0');
    const shown = trimZeros ? fraction.replace(/0+$/, '') : fraction;
    const sign = parsed < 0 && scaled !== 0 ? '-' : '';

    return shown === '' ? `${sign}${whole}` : `${sign}${whole}${decimal}${shown}`;
}

export function parseDecimals(text) {
    const thousands = user.pref('thousand_separator');
    const decimal = user.pref('decimal_separator');
    const parsed = Number(text.split(thousands).join('').split(decimal).join('.'));

    return Number.isNaN(parsed) ? 0 : parsed;
}

export const formatDecimals = (value, decimals) => {
    return render(value, decimals);
};

export const formatPrice = (value) => {
    return formatDecimals(value, user.pref('price_decimals'));
};

export const formatQuantity = (value, unitDecimals = null) => {
    return render(value, unitDecimals ?? user.pref('quantity_decimals'));
};

// As many decimals as a double holds, without the trailing zeros.
export function formatMaxPrecision(value) {
    const magnitude = Math.abs(Number(value) || 0);

    return render(
        value,
        magnitude > 0 ? Math.max(0, 15 - Math.floor(Math.log10(magnitude))) : 2,
        true,
    );
}

export const formatExchangeRate = (value) => {
    return formatDecimals(value, user.pref('exchange_rate_decimals'));
};

export const formatPercent = (value) => {
    return formatDecimals(value, user.pref('percent_decimals'));
};

export const parsePrice = (text) => parseDecimals(text);

export const parseQuantity = (text) => parseDecimals(text);

export const parsePercent = (text) => parseDecimals(text);
