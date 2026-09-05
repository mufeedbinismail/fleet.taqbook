'use strict';

import Alpine from './plugins/alpine';
import axios from './plugins/axios';
import data from './foundation/data';
import { setBusyState, unsetBusyState, isBusy } from './foundation/busy';
import { route, url, buildQuery } from './foundation/route';
import { i18n } from './foundation/i18n';
import { dataTable, table } from './components/table';
import { factory as selectFactory } from './components/select';
import { date } from './components/date';
import { dateRange } from './components/date-range';
import { modal } from './components/modal';

const select = selectFactory(Alpine);

// Bounded by the region that scrolls, so a panel opens against what is visible rather than
// standing outside anything that could bound it and lengthening the document.
select.defaults({ panelParent: '.shell__content-scroller' });
date.defaults({ panelParent: '.shell__content-scroller' });

// The same region is what a modal holds still behind it, for the same reason: html and body never
// scroll, so a lock put on either of them locks nothing.
modal.defaults({ scroller: '.shell__content-scroller' });

// Merge, not replace: a deferred module runs after the classic inline scripts that have already
// staged onto window.App.
Object.assign(window.App, {
    setBusyState,
    unsetBusyState,
    isBusy,
    route,
    i18n,
    url,
    buildQuery,
    dataTable,
    table,
    select,
    date,
    dateRange,
    modal,
});

// Deep-copied per callback, so nothing one edits can be read back by whoever boots next.
// structuredClone is enough because the payload arrives as parsed JSON.
const supply = () => ({ Alpine, App: window.App, axios, data: structuredClone(data) });

// Drained before Alpine starts, so a callback still gets to register components with it. The
// queueing version is then replaced with a direct call, so one arriving later runs immediately
// rather than joining a queue nothing will drain again.
const queued = App.boot?.queue ?? [];

App.boot = (fn) => fn(supply());
queued.forEach((fn) => fn(supply()));

Alpine.start();
