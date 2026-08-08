'use strict';

import Alpine from './plugins/alpine';
import { setBusyState, unsetBusyState, isBusy } from './foundation/busy';
import { route, url, buildQuery } from './foundation/route';
import { i18n } from './foundation/i18n';

// Merge, not replace: by the time this deferred module runs, other classic inline scripts on
// the page have already staged data onto window.App — reassigning it outright would discard
// that.
Object.assign(window.App, {
    setBusyState,
    unsetBusyState,
    isBusy,
    route,
    i18n,
    url,
    buildQuery,
});

// Drained before Alpine starts, so a callback still gets to register components with it.
// Replacing the queueing version with a direct call is what lets one name serve both sides of
// boot: a script arriving with an AJAX fragment long afterwards runs immediately, rather than
// joining a queue nothing will drain again.
const queued = App.ready?.queue ?? [];

App.ready = (fn) => fn();
queued.forEach((fn) => fn());

Alpine.start();
