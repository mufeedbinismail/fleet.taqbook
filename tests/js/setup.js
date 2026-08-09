/*
    The document a page module expects to find already there.

    Two of them are read at import time rather than on demand — the base URL out of a meta tag, and
    whatever the server staged onto window.App — so both have to exist before the first import in a
    test file runs, which is what a setup file is.
*/
document.head.innerHTML = `
    <meta name="base-url" content="http://localhost/">
    <meta name="csrf-token" content="test-token">
`;

window.App = { data: { routes: {}, i18n: {} } };
