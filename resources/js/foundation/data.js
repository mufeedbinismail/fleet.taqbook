/*
    Whatever the server handed this page, read once. A module script is deferred by definition, so
    it cannot run before a classic inline script has: the assignment this reads has already
    happened, whichever of the two sits higher in the document.
*/
export default window.App.data ?? {};
