'use strict';

import axios from '../plugins/axios';
import { BACKGROUND } from './busy';
import { route } from './route';

const DARK = 'dark';
const LIGHT = 'light';

/*
    The document is changed without waiting on the request, and a failed write is left where it
    fell: the page is already wearing what was asked for, and the most a lost write costs is the
    next page opening in the skin this one was asked to leave.
*/
export function wear(dark) {
    if (dark) document.documentElement.setAttribute('data-skin', DARK);
    else document.documentElement.removeAttribute('data-skin');

    axios
        .post(route('preference.skin'), { skin: dark ? DARK : LIGHT }, { busy: BACKGROUND })
        .catch(() => {});
}
