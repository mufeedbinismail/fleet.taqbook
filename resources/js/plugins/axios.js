import axios from 'axios';
import { LIVE, setBusyState, unsetBusyState } from '../foundation/busy';

// Requests carry which kind they are; anything that does not say is one somebody is waiting on.
// Written against the kind it means rather than against the absence of the other, so the two names
// and this test cannot drift apart without something failing.
const blocks = (config) => (config?.busy ?? LIVE) === LIVE;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name="csrf-token"]')?.content;

// A request raises the shared busy indicator unless it opted out as background, and its
// settlement — success or failure — lowers it the same way, so the indicator reflects "any
// foreground request in flight", not just the last one.
axios.interceptors.request.use((config) => {
    if (blocks(config)) setBusyState();

    return config;
});

// A dead session is never recoverable in place, so it's the one case handled by navigating
// away rather than by attaching a message for the caller to display.
axios.interceptors.response.use(
    (response) => {
        if (blocks(response.config)) unsetBusyState();

        return response;
    },
    (error) => {
        // Lowered only where it was raised: an aborted request settles with no config at all, and
        // lowering for one that never raised anything would let go of somebody else's.
        if (error.config && blocks(error.config)) unsetBusyState();

        const status = error.response?.status;

        if (!error.response) {
            error.friendlyMessage = window.App.i18n('foundation.http.offline');
        } else if (status === 401) {
            window.location.href = window.App.route('login');
        } else if (status === 419) {
            error.friendlyMessage = window.App.i18n('foundation.http.expired');
        } else if (status !== 422) {
            // 422 is left for the caller: only it knows how to map field errors to its own form.
            error.friendlyMessage =
                error.response.data?.message ?? window.App.i18n('foundation.http.failed');
        }

        return Promise.reject(error);
    },
);

export default axios;
