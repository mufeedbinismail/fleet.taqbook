'use strict';

import icons from './icons';

export default function initUI() {
    if (document.querySelector('meta[name="is-legacy-page"]')?.content !== '1') {
        return;
    }

    /**
     * Refreshes the UI
     *
     * This function adds the necessary styling classes to elements,
     * Primarily, it is used to support the :has CSS selector
     */
    function refreshUI() {
        // find all the td element which has direct child table element
        // and add the class has-table
        document.querySelectorAll("td > table, th > table").forEach(table => {
            table.parentElement.classList.add("has-table");
        });

        // find all the span that has a direct select element
        // and add the class has-select
        document.querySelectorAll("span > select").forEach(select => {
            select.parentElement.classList.add("has-select");
        });

        // find all the span that has a direct sibling element with
        // name ending on _update and add the class has-updatable-sibling
        document.querySelectorAll("span + input[name$='_update']").forEach(update => {
            update.previousElementSibling.classList.add("has-updatable-sibling");
        });

        // find all the span that has a direct sibling img element
        // and add the class has-img-sibling
        document.querySelectorAll("span + img").forEach(img => {
            img.previousElementSibling.classList.add("has-img-sibling");
        });

        // find all the li inside ajaxtabs that has a direct current button
        // add the class has-current-button
        document.querySelectorAll("ul.ajaxtabs li > button.current").forEach(current => {
            current.parentElement.classList.add("has-current");
        });

        // find all parent elements that have an image icon inside it
        document.querySelectorAll('button > img, input[name$="_update"] + img, span.has-select + img, input + a[href^="javascript:date_picker"] > img').forEach(img => {
            const baseName = img.src.split('/').pop().split('.').shift();
            const parent = img.parentElement;

            if (!icons[baseName]) return; // Skip if icon not found

            // Convert SVG string to an actual SVG element
            const icon = document.createElement("span");
            icon.classList.add('icon', icons[baseName], 'text-lg');
            icon.title = img.title;
            icon.onclick = img.onclick;

            parent.dataset.icon = baseName;
            parent.classList.add("has-img-icon");

            // Replace the image with the new SVG
            parent.replaceChild(icon, img);

            if (parent.children.length == 1) {
                parent.classList.add("is-icon");
            }
        });

        for (const select of document.getElementsByTagName('select')) {
            if (select.options.length > 10 && !select.classList.contains('select2-hidden-accessible')) {
                $(select).select2()
            }
        };
    }

    /**
     * Monkey patch the FrontAccounting date_picker
     * 
     * This adds the ability to adjust the position of the date picker
     * element when it is out of bounds of the parent container.  
     * As well as follow the input field when the parent container is scrolled.
     */
    function monkeyPatchFADatePicker() {
        const originalDatePicker = window.date_picker;

        if (!originalDatePicker) {
            return;
        }

        const containerEl = document.querySelector('.main-content');
        const calendarId = 'CC';
        let currentInput = null;
        let currentBounds = null;
        let lastScrollTop = containerEl.scrollTop;

        window.date_picker = function date_picker(textField) {
            currentInput = textField;
            currentBounds = new positionInfo(containerEl);
            currentBounds = {
                t: currentBounds.getElementTop(),
                l: currentBounds.getElementLeft(),
                w: currentBounds.getElementWidth(),
                h: currentBounds.getElementHeight(),
                r: currentBounds.getElementRight(),
                b: currentBounds.getElementBottom()
            }

            originalDatePicker.apply(this, arguments);

            if (window.cC?.visible?.()) {
                const calendar = document.getElementById(calendarId);

                let calendarPos = new positionInfo(calendar);
                calendarPos = {
                    t: calendarPos.getElementTop(),
                    l: calendarPos.getElementLeft(),
                    w: calendarPos.getElementWidth(),
                    h: calendarPos.getElementHeight(),
                    r: calendarPos.getElementRight(),
                    b: calendarPos.getElementBottom()
                };

                let fieldPos = new positionInfo(currentInput);
                fieldPos = {
                    t: fieldPos.getElementTop(),
                    l: fieldPos.getElementLeft(),
                    w: fieldPos.getElementWidth(),
                    h: fieldPos.getElementHeight(),
                    r: fieldPos.getElementRight(),
                    b: fieldPos.getElementBottom()
                };
                
                const outOfBounds = {
                    t: calendarPos.t < currentBounds.t,
                    l: calendarPos.l < currentBounds.l,
                    r: calendarPos.r > currentBounds.r,
                    b: calendarPos.b > currentBounds.b
                }

                // check if the calendar is out of bounds and adjust the position
                if (outOfBounds.r) {
                    calendar.style.left = (fieldPos.r - calendarPos.w) + 'px';
                    calendar.style.right = 'auto';
                }

                if (
                    outOfBounds.b
                    && (fieldPos.t - calendarPos.h) > currentBounds.t
                ) {
                    calendar.style.top = (fieldPos.t - calendarPos.h) + 'px';
                    calendar.style.bottom = 'auto';
                }
            }
        }

        // on scroll follow the input field
        containerEl.addEventListener('scroll', function() {
            let scrollTop = containerEl.scrollTop;
            let dir = scrollTop > lastScrollTop ? 'up' : 'down';
            let scrolled = Math.abs(scrollTop - lastScrollTop);

            if (window.cC?.visible?.()) {
                const calendar = document.getElementById(calendarId);

                let calendarPos = new positionInfo(calendar);
                calendarPos = {
                    t: calendarPos.getElementTop(),
                    l: calendarPos.getElementLeft(),
                    w: calendarPos.getElementWidth(),
                    h: calendarPos.getElementHeight(),
                    r: calendarPos.getElementRight(),
                    b: calendarPos.getElementBottom()
                };

                let fieldPos = new positionInfo(currentInput);
                fieldPos = {
                    t: fieldPos.getElementTop(),
                    l: fieldPos.getElementLeft(),
                    w: fieldPos.getElementWidth(),
                    h: fieldPos.getElementHeight(),
                    r: fieldPos.getElementRight(),
                    b: fieldPos.getElementBottom()
                };

                const outOfBounds = {
                    t: calendarPos.t < currentBounds.t,
                    l: calendarPos.l < currentBounds.l,
                    r: calendarPos.r > currentBounds.r,
                    b: calendarPos.b > currentBounds.b
                };

                if (dir === 'up') {
                    calendar.style.top = (calendarPos.t - scrolled) + 'px';
                    calendar.style.bottom = 'auto';

                    if (
                        outOfBounds.t
                        && (fieldPos.b + calendarPos.h) < currentBounds.b
                    ) {
                        calendar.style.top = fieldPos.b + 'px';
                        calendar.style.bottom = 'auto';
                    }
                }
                
                else {
                    calendar.style.top = (calendarPos.t + scrolled) + 'px';
                    calendar.style.bottom = 'auto';

                    if (
                        outOfBounds.b
                        && (fieldPos.t - calendarPos.h) > currentBounds.t
                    ) {
                        calendar.style.top = (fieldPos.t - calendarPos.h) + 'px';
                        calendar.style.bottom = 'auto';
                    }
                }
            }

            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });
    }

    /**
     * Monkey patch the FrontAccounting set_mark function
     * 
     * This adds the ability to refresh the UI when the set_mark
     * function is called with a null argument (ie. clear the ajax loader mark)
     */
    function monkeyPatchFASetMark() {
        const loaderVariants = {
            'progressbar.gif': 'progress',
            'ajax-loader.gif': 'spinner',
            'warning.png': 'warning',
        };

        if (!window.set_mark) {
            return;
        }

        window.set_mark = function set_mark(img) {
            const container = document.querySelector('[data-loader-container]');
            const loader = container?.querySelector('[data-loader]');

            if (loader) {
                loader.dataset.loader = loaderVariants[img] ?? 'spinner';
            }

            container?.toggleAttribute('data-loader-visible', Boolean(img));

            // If there is no image its being called to clear the ajax
            // loader mark. This is a great place to refresh the UI
            if (!img) {
                setTimeout(refreshUI);
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        refreshUI();
        monkeyPatchFADatePicker();
        monkeyPatchFASetMark();
    });
}