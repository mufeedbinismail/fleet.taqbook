import { vi } from 'vitest';

/**
 * A stand-in for axios that records what it was asked for and answers from a function.
 *
 * @param respond  called with ({ method, url, body }, n) and returns the response body. Throwing
 *                 from it rejects the call, which is how a failure is scripted.
 */
export function httpDouble(respond = () => ({})) {
    const requests = [];

    const send =
        (method) =>
        (url, ...rest) => {
            const request = {
                method,
                url,
                body: method === 'get' || method === 'delete' ? undefined : rest[0],
            };

            requests.push(request);

            try {
                return Promise.resolve({
                    status: 200,
                    headers: {},
                    data: respond(request, requests.length),
                });
            } catch (error) {
                return Promise.reject(error);
            }
        };

    return {
        requests,
        get urls() {
            return requests.map((request) => request.url);
        },
        get last() {
            return requests[requests.length - 1]?.url;
        },
        get: vi.fn(send('get')),
        post: vi.fn(send('post')),
        put: vi.fn(send('put')),
        patch: vi.fn(send('patch')),
        delete: vi.fn(send('delete')),
    };
}

/**
 * The same, except that nothing is answered until a test says so.
 *
 * `answer(n, body)` settles the nth call, counted from zero in the order the calls were made — so
 * a test can settle them in an order the caller never asked for, which is the only way to hold a
 * component to what it does when the network reorders its replies.
 */
export function deferredHttp() {
    const requests = [];
    const pending = [];

    const send =
        (method) =>
        (url, ...rest) => {
            requests.push({
                method,
                url,
                body: method === 'get' || method === 'delete' ? undefined : rest[0],
            });

            return new Promise((resolve, reject) => pending.push({ resolve, reject }));
        };

    return {
        requests,
        get urls() {
            return requests.map((request) => request.url);
        },
        answer(index, data) {
            pending[index].resolve({ status: 200, headers: {}, data });
        },
        fail(index, error) {
            pending[index].reject(error);
        },
        get: vi.fn(send('get')),
        post: vi.fn(send('post')),
        put: vi.fn(send('put')),
        patch: vi.fn(send('patch')),
        delete: vi.fn(send('delete')),
    };
}

/**
 * The query a request carried, as URLSearchParams.
 */
export function queryOf(url) {
    return new URLSearchParams(url.split('?')[1] ?? '');
}

/**
 * Settles everything already queued as a microtask, plus one turn of the event loop — enough for a
 * chain of awaits behind a resolved promise, and for a zero-delay timer.
 */
export function flush() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}
