import data from './data';

const translations = { ...data.i18n };

// Only replaces params actually supplied, same as Laravel's own __() — it never scans a string
// for :word-shaped substrings on its own, so a translation with a literal colon in it is safe.
function fill(template, params) {
    return Object.keys(params).reduce(
        (result, key) => result.replaceAll(':' + key, params[key]),
        template,
    );
}

export const i18n = function (key, params) {
    const template = translations[key];

    if (template === undefined) {
        throw new Error(`Translation "${key}" is not defined`);
    }

    return params ? fill(template, params) : template;
};

i18n.push = function (key, template) {
    translations[key] = template;
};
