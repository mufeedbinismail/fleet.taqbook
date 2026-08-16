import axios from 'axios';
import { setBusyState, unsetBusyState } from '../foundation/busy';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name="csrf-token"]')?.content;

// Every request raises the shared busy indicator and every settlement — success or failure —
// lowers it again, so the indicator reflects "any request in flight", not just the last one.
axios.interceptors.request.use((config) => {
    setBusyState();
    return config;
});

// A dead session is never recoverable in place, so it's the one case handled by navigating
// away rather than by attaching a message for the caller to display.
axios.interceptors.response.use(
    (response) => {
        unsetBusyState();
        return response;
    },
    (error) => {
        unsetBusyState();

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
