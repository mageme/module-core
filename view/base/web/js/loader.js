'use strict';
(function () {
    if (typeof window.MageMe !== 'object') {
        window.MageMe = {};
    }
    const cssReady = new WeakMap();
    const cssLate = new WeakMap();
    const pending = new Promise(() => {});
    window.MageMe.loader = {

        /**
         * A function to dynamically add a script to the DOM if it doesn't already exist,
         * and execute a callback function once the script has loaded.
         *
         * On a load failure or a timeout the loading placeholder is torn down instead of left in
         * place: without this a 404, a CSP block or a stalled request would leave the queued
         * callbacks pending forever and every later addScript for the same name blocked behind them,
         * with nothing in the console. Now the failure is logged and a later call may retry.
         *
         * @param {string} objName - The name of the object to check or create in the global window scope.
         * @param {string} scriptSrc - The source URL of the script to be added to the DOM.
         * @param {function} callbackFunc - The callback function to be executed after the script has loaded.
         */
        addScript: (objName, scriptSrc, callbackFunc) => {
            if (!window[objName]) {
                let callbacks = [];
                window[objName] = {'-isLoading': (callback) => callbacks.push(callback)};
                const script = document.createElement('script');
                script.src = scriptSrc;
                const fail = (reason) => {
                    console.error('MageMe.loader: ' + reason + ' ' + scriptSrc);
                    callbacks = [];
                    if (window[objName] && window[objName]['-isLoading']) {
                        delete window[objName];
                    }
                };
                const timer = setTimeout(() => fail('timed out loading'), 15000);
                script.onload = () => {
                    clearTimeout(timer);
                    callbacks.map(callback => callback());
                    callbacks = [];
                };
                script.onerror = () => {
                    clearTimeout(timer);
                    fail('failed to load');
                };
                document.head.append(script);

            }
            if (window[objName] && window[objName]['-isLoading']) {
                window[objName]['-isLoading'](() => MageMe.loader.addScript(objName, scriptSrc, callbackFunc))
            }
            if (window[objName] && !window[objName]['-isLoading'] && callbackFunc) {
                callbackFunc();
            }
        },

        /**
         * A function that adds a script to the document head if it does not already exist.
         *
         * @param {string} scriptSrc - the source URL of the script to be added
         */
        addScriptWoCb: (scriptSrc) => {
            if (document.querySelector('script[src="' + scriptSrc + '"]')) {
                return;
            }
            const script = document.createElement('script');
            script.src = scriptSrc;
            document.head.append(script);
        },

        /**
         * A function that adds a CSS file to the document if it's not already included.
         *
         * The returned promise settles once the file has loaded or failed, or after 3 seconds, and never
         * rejects, so a caller that measures styled elements can wait for it without hanging on a stalled
         * request. A file this loader did not add counts as ready.
         *
         * @param {string} ref - the reference to the CSS file to be added
         * @return {Promise<void>}
         */
        addCss: (ref) => {
            const existing = document.querySelector('link[href="' + ref + '"]');
            if (existing) {
                return cssReady.get(existing) || Promise.resolve();
            }
            const css = document.createElement("link");
            css.rel = "stylesheet";
            css.href = ref;
            let gaveUp = false;
            let arrivedLate;
            cssLate.set(css, new Promise((resolve) => {
                arrivedLate = resolve;
            }));
            const ready = new Promise((resolve) => {
                css.addEventListener('load', () => {
                    if (gaveUp) {
                        arrivedLate();
                    }
                    resolve();
                }, {once: true});
                css.addEventListener('error', () => resolve(), {once: true});
                setTimeout(() => {
                    gaveUp = true;
                    resolve();
                }, 3000);
            });
            cssReady.set(css, ready);
            document.head.appendChild(css);
            return ready;
        },

        /**
         * Settles when a stylesheet added by addCss loads after its promise has already settled on the
         * 3-second limit, so a caller that measured unstyled elements then can measure them again. Stays
         * pending when the file loaded in time, failed, or was not added by addCss.
         *
         * @param {string} ref - the reference passed to addCss
         * @return {Promise<void>}
         */
        cssArrivedLate: (ref) => {
            const existing = document.querySelector('link[href="' + ref + '"]');
            return (existing && cssLate.get(existing)) || pending;
        },

        /**
         * A function that adds a CSS file to the document head in a specific order.
         *
         * @param {string} ref - The reference to the CSS file to be added.
         * @param {number|string} order - The order in which the CSS file should be added.
         * @return {void}
         */
        addCssOrdered: (ref, order) => {
            if (document.querySelector('link[href="' + ref + '"]')) {
                return;
            }
            order = Number(order);
            if (!order) {
                order = 0;
            }
            const css = document.createElement("link");
            css.rel = "stylesheet";
            css.href = ref;
            css.setAttribute('data-mm-order', order);
            const els = Array.from(document.head.querySelectorAll("[data-mm-order]"))
                .filter(el => Number(el.dataset.mmOrder) > order);
            if (!els.length) {
                document.head.appendChild(css);
                return;
            }
            document.head.insertBefore(css, els[0]);
        }
    }
}());