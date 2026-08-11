import { i18n } from '@/foundation/i18n';
import { route } from '@/foundation/route';

/**
 * Adds routes and translations to the ones a page was served with.
 *
 * Through the push APIs rather than through window.App.data, and that is the point of this
 * existing: the modules answering for routes and translations each take their copy of the staged
 * data when they are first imported, so anything written onto window.App afterwards is written
 * somewhere nothing will read it again.
 *
 * Whatever a page stages under its own key is a different matter: a page module's factory takes
 * the whole supply as an argument, so a test hands the seed straight in and stages nothing.
 */
export function stage({ routes = {}, translations = {} } = {}) {
    for (const [name, template] of Object.entries(routes)) {
        route.push(name, template);
    }

    for (const [key, template] of Object.entries(translations)) {
        i18n.push(key, template);
    }
}
