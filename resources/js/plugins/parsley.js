'use strict';

import Parsley from 'parsley-vanilla';

/*
    The field's own wrapper, which is where both the class and the message belong. Left to itself
    the library drops its list in as the input's next sibling — and in a two-column grid that is a
    third grid item, which moves every cell after it.
*/
const group = (field) =>
    field.element.closest('[data-validator-field]') ?? field.element.parentElement;

Object.assign(Parsley.options, {
    // Named for what the attribute is for rather than which library answers it.
    namespace: 'data-validator-',

    // The state a control is in, said the way the rest of the app says it rather than in the
    // library's own vocabulary: anything that can be right or wrong wears these, validated by
    // Parsley or not.
    errorClass: 'is-invalid',
    successClass: 'is-valid',

    classHandler: group,
    errorsContainer: group,
    errorsWrapper: '<ul class="validator-errors mt-1 grid list-none gap-1 p-0"></ul>',
    errorTemplate: '<li class="text-sm font-semibold text-error-accent"></li>',

    // Custom controls announce themselves rather than being guessed at, and a search box a control
    // drew for itself is not a field anybody filled in.
    inputs: 'input, textarea, select, [data-validator-control]',
    excluded:
        'input[type=button], input[type=submit], input[type=reset], input[type=hidden], [data-validator-excluded]',

    // Nothing is said about a field until it has been asked once. After that it answers as it is
    // typed in, so a correction is acknowledged rather than held against the next submit.
    trigger: false,
    triggerAfterFailure: 'input change',
});

/*
    Parsley's own `pattern` builds its regexp through a parser that accepts only `gimy`, so a
    pattern needing `u` or `s` cannot be written with it at all. This one reads the flags itself.
*/
Parsley.addValidator('pattern2', {
    requirementType: 'string',
    messages: { en: 'This value seems to be invalid' },
    validateString(value, regexp, instance) {
        if (!value && instance.options.validateIfEmpty === undefined) return true;

        let flags = '';

        if (/^\/.*\/(?:[gisumy]*)$/.test(regexp)) {
            flags = regexp.replace(/.*\/([gisumy]*)$/, '$1');
            regexp = regexp.replace(new RegExp(`^/(.*?)/${flags}$`), '$1');
        } else {
            regexp = `^${regexp}$`;
        }

        return new RegExp(regexp, flags).test(value);
    },
});

Parsley.addValidator('maxFileSize', {
    requirementType: 'number',
    messages: { en: 'File is too big' },
    validate(value, requirement, instance) {
        const files = instance.element.files;

        if (files.length === 0) return true;

        return files.length === 1 && files[0].size <= requirement * 1048576;
    },
});

Parsley.addValidator('mimetypes', {
    requirementType: 'string',
    messages: { en: 'File mime type not allowed' },
    validate(value, requirement, instance) {
        const files = instance.element.files;

        if (files.length === 0) return true;

        return requirement.replace(/\s/g, '').split(',').includes(files[0].type);
    },
});

const ticked = (nodes) => [...nodes].some((node) => node.checked);

// Whether a control is holding anything: a tick among the set for a checkbox or radio, and for
// everything else a value that is neither empty nor zero.
const answered = (nodes) => {
    const [first] = nodes;

    if (first.tagName === 'INPUT' && (first.type === 'radio' || first.type === 'checkbox')) {
        return ticked(nodes);
    }

    const value = first.value;

    return (isNaN(parseFloat(value)) ? value.length : parseFloat(value)) !== 0;
};

Parsley.addValidator('requiredWith', {
    requirementType: 'string',
    messages: { en: 'This value is required' },
    validate(value, requirement, instance) {
        const others = document.querySelectorAll(requirement);

        if (!others.length) throw new Error(`${requirement} is not an element`);

        if (!answered(others)) return true;

        const element = instance.element;

        if (
            element.tagName === 'INPUT' &&
            (element.type === 'radio' || element.type === 'checkbox')
        ) {
            return element.name
                ? ticked(document.querySelectorAll(`[name="${element.name}"]`))
                : true;
        }

        return (isNaN(parseFloat(value)) ? value.length : parseFloat(value)) !== 0;
    },
});

/*
    A field that is required only when another one is answered has to be asked about while it is
    still empty, which is the one case Parsley skips by default.
*/
const askEvenWhenEmpty = () => {
    const { namespace } = Parsley.options;

    document
        .querySelectorAll(`[${namespace}required-with]:not([${namespace}validate-if-empty])`)
        .forEach((element) => {
            element.setAttribute(`${namespace}validate-if-empty`, 'true');
        });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', askEvenWhenEmpty, { once: true });
} else {
    askEvenWhenEmpty();
}

export default Parsley;
