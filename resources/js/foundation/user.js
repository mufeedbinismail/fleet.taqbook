import data from './data';

const granted = { ...data.user?.permissions };

// Keyed by the name of a `Permission` constant. Only the ones the page registered are answerable; any other
// is a lookup nothing sent, so it fails the way an unregistered route or translation does rather than reading as a refusal.
export const user = {
    can(permission) {
        if (!(permission in granted)) {
            throw new Error(`Permission "${permission}" is not defined`);
        }

        return granted[permission];
    },
};
