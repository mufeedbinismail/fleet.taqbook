import data from './data';

const settings = { ...data.settings };

// Keyed by the name of the method the server called, which also chose its arguments. Only the ones the page
// registered are answerable; any other is a lookup nothing sent, so it fails the way an unregistered route does.
export function setting(name) {
    if (!(name in settings)) {
        throw new Error(`Setting "${name}" is not defined`);
    }

    return settings[name];
}
