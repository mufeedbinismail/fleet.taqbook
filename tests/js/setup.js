/*
    The document a page module expects to find already there.

    All of it is read — or called — at import time rather than on demand: the base URL out of a
    meta tag, whatever the server staged onto window.App, and App.boot, which a page module
    registers through as it loads. So it has to exist before the first import in a test file runs,
    which is what a setup file is.
*/
document.head.innerHTML = `
    <meta name="base-url" content="http://localhost/">
    <meta name="csrf-token" content="test-token">
`;

window.App = { data: { routes: {}, i18n: {} } };

window.App.boot = function (fn) {
    (window.App.boot.queue ??= []).push(fn);
};
