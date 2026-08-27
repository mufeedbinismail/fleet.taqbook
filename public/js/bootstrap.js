'use strict';

/*
    The one file a document waits on: loaded blocking, from the head, because everything in it has
    to be true before anything is painted and before any module has run.

    Everything this app owns on window hangs off one name, so it can never collide with legacy
    FrontAccounting globals or a third-party script — Alpine and axios stay bare since those are
    their own ecosystem's convention, not ours to rename.

    Nothing here is written in syntax an older engine could refuse: a file that will not parse is a
    page with no App on it at all and no skin ever put on, on the very engines least able to spare
    either.
*/
window.App = window.App || {};

/*
    Queued rather than run: a classic script executes before any module does, so nothing calling
    this can assume App is finished being assembled. Whatever is handed over runs once it is, and
    calling later is no different — anything arriving after that point runs straight away.

    Boot, not ready: this fires while the page is still being assembled, which is the one moment a
    component can still be registered and the last moment at which no component's state exists yet.
    Anything needing live state belongs in that component's own init(), which is called for it at
    the right time.
*/
App.boot = function (fn) {
    App.boot.queue = App.boot.queue || [];
    App.boot.queue.push(fn);
};

(function initializeSkin() {
    var query = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)');

    var stored = function () {
        try {
            return localStorage.getItem('skin');
        } catch (e) {
            return null;
        }
    };

    var remember = function (skin) {
        try {
            localStorage.setItem('skin', skin);
        } catch (e) {}
    };

    var apply = function (skin) {
        var dark = skin === 'dark' || (!skin && query && query.matches);

        document.documentElement.setAttribute('data-skin', dark ? 'dark' : 'light');
    };

    App.setSkin = function (skin) {
        apply(skin);
        remember(skin);

        axios
            .post(App.route('preference.skin'), { skin: skin }, { busy: 'background' })
            .catch(function () {});
    };

    // An attribute on the document is the account's own answer, the empty string included, which is
    // an account that would rather its browser decided. With none there is nobody signed in to have
    // answered, and what this device was told is all there is to go on.
    var served = document.documentElement.getAttribute('data-skin');

    // Copied down as it arrives, which is what carries a choice made on one machine to the login
    // screen of the next: the account answers there once, and the device has it from then on.
    if (served !== null) remember(served);

    apply(served === null ? stored() : served);

    if (!query) return;

    // Only a device nobody has pinned a skin on moves with the browser. Safari answered the query
    // itself for years before it would let anything listen to one as an event target, and those
    // versions still answer to the older name.
    var react = function () {
        if (!stored()) apply('');
    };

    if (query.addEventListener) query.addEventListener('change', react);
    else if (query.addListener) query.addListener(react);
})();
