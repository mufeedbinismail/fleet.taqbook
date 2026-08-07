import data from './data';

/*
    Turns any path — relative ("login"), or already a full URL ("https://...") — into a URL
    rooted at this app's own base, which may itself live under a subdirectory read from the
    base-url meta tag. Callers never need to know that subdirectory exists; a path that's
    already absolute passes through unchanged, same as URL's own constructor already does when
    given one.
*/
const base = document.querySelector('meta[name="base-url"]').content;

// Recurses into nested objects/arrays the way PHP's own bracket query-string convention would
// (foo[bar]=baz, foo[0]=baz), so a query object can be handed over exactly as it's already
// shaped rather than flattened by the caller first. Exported standalone too — useful any time
// something just needs a query string/URLSearchParams built the same way, without a URL to
// attach it to.
export function buildQuery(query, prefix, params = new URLSearchParams()) {
    for (const key in query) {
        const value = query[key];
        const name = prefix ? `${prefix}[${key}]` : key;

        if (value !== null && typeof value === 'object') {
            buildQuery(value, name, params);
        } else {
            params.set(name, value);
        }
    }

    return params;
}

export function url(path, { query, absolute = false } = {}) {
    const resolved = new URL(path, base);

    if (query) {
        buildQuery(query, null, resolved.searchParams);
    }

    return absolute ? resolved.toString() : resolved.pathname + resolved.search + resolved.hash;
}

const routes = { ...data.routes };

function fill(template, context) {
    return template.replace(/\{([^}]+)\}/g, (match, key) => {
        if (context === undefined || context[key] === undefined || context[key] === null) {
            throw new Error(`Missing "${key}" for route "${template}"`);
        }

        return encodeURIComponent(context[key]);
    });
}

// Rooted by default: a bare route template is depth-relative and wrong from anywhere but the
// app root, so nothing is ever handed back without going through url() first. context is the
// route's own required input, positional to match route($name, $parameters) on the PHP side;
// query/absolute are formatting options that apply the same way to any URL, so they're named,
// same as url() itself takes them.
export const route = function (name, context, options) {
    const template = routes[name];

    if (template === undefined) {
        throw new Error(`Route "${name}" is not defined`);
    }

    const path = template.includes('{') ? fill(template, context) : template;

    return url(path, options);
};

route.push = function (name, template) {
    routes[name] = template;
};
