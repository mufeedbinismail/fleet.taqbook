'use strict';

import Alpine from './plugins/alpine';
import axios from './plugins/axios';
import data from './foundation/data';
import { setBusyState, unsetBusyState, isBusy } from './foundation/busy';
import { route, url, buildQuery } from './foundation/route';
import { i18n } from './foundation/i18n';
import { wear as setSkin } from './foundation/skin';
import { factory as selectFactory } from './components/select';

const select = selectFactory(Alpine);

// The region every page of this application scrolls. A panel drawn into it travels with the field
// it belongs to and is bounded by what somebody can actually see; one left to the document at large
// stands outside anything that could bound it, and lengthens the page by opening near the foot of
// it. Stated once here rather than at each of the places a control gets built.
select.defaults({ panelParent: '.shell__content-scroller' });

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
    setSkin,
    select,
});

// Passed in rather than left to be reached for on window, so a callback names what it depends on
// and boot stays the only place that decides where any of it comes from.
//
// The staged data is deep-copied per callback, so what one is handed is its own to keep and edit:
// nothing it does to that copy can be read back by whoever boots next, and the server's word on how
// the page opened stays intact underneath for anyone still to ask for it. structuredClone is enough
// because the payload arrives as parsed JSON and holds nothing that isn't.
const supply = () => ({ Alpine, App: window.App, axios, data: structuredClone(data) });

// Drained before Alpine starts, so a callback still gets to register components with it.
// Replacing the queueing version with a direct call is what lets one name serve both sides of
// boot: a script arriving with an AJAX fragment long afterwards runs immediately, rather than
// joining a queue nothing will drain again.
const queued = App.boot?.queue ?? [];

App.boot = (fn) => fn(supply());
queued.forEach((fn) => fn(supply()));

Alpine.start();
