/**
 * MageMe Ecosystem panel behaviour: collapse memory, filters, tooltips, the licence row's
 * activate/deactivate calls, deferred re-render, and the node field behind the Pro plate.
 *
 * Loaded on Stores > Configuration only (layout adminhtml_system_config_edit). Everything the
 * script needs comes from data-* attributes on the block, so it holds no server state of its own.
 */

{
    const initEcosystemBlock = (root) => {
        const sectionId = root.getAttribute('data-section-id') ?? '';
        const offerSignature = root.getAttribute('data-offer-signature') ?? '';
        // Keying the memory by the current offer means a new Pro add-on gets one fresh
        // showing; without a new offer the dismissal keeps standing.
        const keyPrefix = `mageme_ecosystem.${sectionId}`;
        const key = offerSignature ? `${keyPrefix}.${offerSignature}` : keyPrefix;
        let stored = null;
        try { stored = window.localStorage.getItem(key); } catch (_) {}
        let setActiveFilter = null;

        const btn = root.querySelector('.mageme-ecosystem__toggle');
        const body = root.querySelector('.mageme-ecosystem__body');
        const header = root.querySelector('.mageme-ecosystem__header');
        if (!btn || !body || !header) return;

        const I18N_DEFAULTS = {
            until: 'Until %1', since: 'Since %1', nonProduction: 'Non-production',
            activate: 'Activate', deactivate: 'Deactivate',
            noLicenseHint: "Don't have a license?", getOne: 'Get one',
            connectError: "Can't connect to license server.",
            supportExpiredTitle: 'Support expired',
            supportExpiredBody: 'Your support window expired on %1.',
            renewHint: 'You can renew it from your account at %1',
            openMyLicenses: 'Open my licenses', close: 'Close'
        };
        let I18N = I18N_DEFAULTS;
        try { I18N = { ...I18N_DEFAULTS, ...JSON.parse(root.getAttribute('data-i18n') || '{}') }; } catch (e) { /* keep defaults */ }
        const STATE_TEXT = { active: '', dev: '', expired: '', inactive: '', unknown: '', ...(I18N.states || {}) };

        const apply = (open) => {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            body.setAttribute('aria-hidden', open ? 'false' : 'true');
            // The collapsed body keeps its layout, so without this its controls — the serial field
            // among them — stay in the tab order behind a zero height.
            body.toggleAttribute('inert', !open);
            body.classList.toggle('is-expanded', open);
        };

        if (stored === '1') apply(true);
        else if (stored === '0') apply(false);
        else apply(body.classList.contains('is-expanded'));

        const remember = (open) => {
            try {
                for (let i = window.localStorage.length - 1; i >= 0; i--) {
                    const k = window.localStorage.key(i);
                    if (k && k !== key && (k === keyPrefix || k.startsWith(`${keyPrefix}.`))) {
                        window.localStorage.removeItem(k);
                    }
                }
                window.localStorage.setItem(key, open ? '1' : '0');
            } catch (_) {}
        };

        const toggle = () => {
            const next = btn.getAttribute('aria-expanded') !== 'true';
            apply(next);
            remember(next);
        };

        // A header pill is an invitation, not a toggle: it always opens, and lands on its
        // own view instead of the default one.
        const openWithFilter = (filter) => {
            apply(true);
            remember(true);
            if (setActiveFilter) setActiveFilter(filter);
        };

        // Click anywhere on the header toggles the block, except clicks on Docs/Support buttons
        // (those follow their hrefs). The caret toggle is still clickable directly too.
        header.addEventListener('click', (e) => {
            if (e.target.closest('.mageme-ecosystem__btn')) return;
            const pill = e.target.closest('[data-open-filter]');
            if (pill) {
                e.stopPropagation();
                openWithFilter(pill.getAttribute('data-open-filter'));
                return;
            }
            toggle();
        });
        header.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const pill = e.target.closest('[data-open-filter]');
            if (!pill) return;
            e.preventDefault();
            e.stopPropagation();
            openWithFilter(pill.getAttribute('data-open-filter'));
        });

        const activateCta = root.querySelector('[data-mageme-activate-cta]');
        if (activateCta) {
            activateCta.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!body.classList.contains('is-expanded')) apply(true);
                const input = root.querySelector('[data-mageme-license-serial]');
                if (!input) return;
                input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                input.classList.remove('is-attention');
                void input.offsetWidth;
                input.classList.add('is-attention');
                setTimeout(() => input.focus({ preventScroll: true }), 320);
            });
        }

        const expiredBtn = root.querySelector('[data-mageme-support-expired]');
        if (expiredBtn) {
            expiredBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const expiredDate = expiredBtn.getAttribute('data-expired-date') || '';
                const renewUrl = 'https://mageme.com/licenses/';
                const renewLink = `<a href="${renewUrl}" target="_blank" rel="noopener noreferrer">mageme.com/licenses/</a>`;
                const bodyExpired = I18N.supportExpiredBody.replace('%1', `<strong>${expiredDate}</strong>`);
                const bodyRenew = I18N.renewHint.replace('%1', renewLink);
                const html = `
                    <div class="mageme-modal__icon" aria-hidden="true">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h2 class="mageme-modal__title">${I18N.supportExpiredTitle}</h2>
                    <div class="mageme-modal__body">${bodyExpired}<br>${bodyRenew}</div>
                `;
                require(['swal'], (Swal) => {
                    Swal.fire({
                        html,
                        showCancelButton: true,
                        confirmButtonText: I18N.openMyLicenses,
                        cancelButtonText: I18N.close,
                        reverseButtons: true,
                        buttonsStyling: false,
                        customClass: {
                            popup: 'mageme-modal',
                            htmlContainer: 'mageme-modal__container',
                            actions: 'mageme-modal__actions',
                            confirmButton: 'mageme-modal__btn mageme-modal__btn--primary',
                            cancelButton: 'mageme-modal__btn',
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open(renewUrl, '_blank', 'noopener');
                        }
                    });
                });
            });
        }

        // === Copy serial to clipboard ===
        const copyBtn = root.querySelector('[data-mageme-license-copy]');
        if (copyBtn) {
            copyBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                const input = root.querySelector('[data-mageme-license-serial]');
                const value = input?.value || '';
                if (!value) return;
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(value);
                    } else {
                        const wasReadonly = input.hasAttribute('readonly');
                        input.removeAttribute('readonly');
                        input.select();
                        document.execCommand('copy');
                        input.setSelectionRange(0, 0);
                        if (wasReadonly) input.setAttribute('readonly', '');
                    }
                    copyBtn.classList.add('is-copied');
                    setTimeout(() => copyBtn.classList.remove('is-copied'), 1400);
                } catch (_) { /* clipboard denied — no-op */ }
            });
        }

        // === License row ===
        const license = root.querySelector('[data-mageme-license]');
        if (license) {
            const serialInput = license.querySelector('[data-mageme-license-serial]');
            const toggleBtn = license.querySelector('[data-mageme-license-toggle]');
            const toggleLabel = toggleBtn?.querySelector('.mageme-ecosystem__license-btn-label');
            const messagesEl = license.parentElement.querySelector('[data-mageme-license-messages]');

            const ICONS = {
                'is-success': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
                'is-warning': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                'is-error':   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9"  x2="9"  y2="15"/><line x1="9"  y1="9"  x2="15" y2="15"/></svg>'
            };
            const renderMessages = ({ messages = [], warnings = [], errors = [] }) => {
                messagesEl.replaceChildren();
                const append = (list, cls) => list.forEach((m) => {
                    const div = document.createElement('div');
                    div.className = cls;
                    div.innerHTML = ICONS[cls] + '<span></span>';
                    div.querySelector('span').textContent = m;
                    messagesEl.appendChild(div);
                });
                append(messages, 'is-success');
                append(warnings, 'is-warning');
                append(errors,   'is-error');
            };

            const STATE_LABELS = {
                active:   { primary: STATE_TEXT.active,   secondary: (d) => d ? I18N.until.replace('%1', d) : '' },
                dev:      { primary: STATE_TEXT.dev,      secondary: () => I18N.nonProduction },
                expired:  { primary: STATE_TEXT.expired,  secondary: (d) => d ? I18N.since.replace('%1', d) : '' },
                inactive: { primary: STATE_TEXT.inactive, secondary: () => '' },
                unknown:  { primary: STATE_TEXT.unknown,  secondary: () => '' }
            };
            const STATES = Object.keys(STATE_LABELS);

            const formatDate = (iso) => {
                if (!iso) return '';
                const d = new Date(iso + 'T00:00:00');
                if (isNaN(d)) return iso;
                return new Intl.DateTimeFormat(document.documentElement.lang || 'en', { dateStyle: 'medium' }).format(d);
            };

            const applyState = (statusData) => {
                const badge = license.querySelector('[data-mageme-license-status]');
                if (!badge) return;
                STATES.forEach((s) => badge.classList.remove(`is-${s}`));
                badge.classList.add(`is-${statusData.state}`);
                badge.dataset.state = statusData.state;
                const primary = badge.querySelector('.mageme-ecosystem__license-status-primary');
                const secondary = badge.querySelector('.mageme-ecosystem__license-status-secondary');
                const labels = STATE_LABELS[statusData.state] || STATE_LABELS.unknown;
                if (primary) primary.textContent = labels.primary;
                if (secondary) {
                    const getOneUrl = badge.dataset.getOneUrl;
                    const serialEmpty = !serialInput.value.trim();
                    if (statusData.state === 'inactive' && getOneUrl && serialEmpty && !statusData.isActive) {
                        secondary.textContent = '';
                        secondary.appendChild(document.createTextNode(I18N.noLicenseHint + ' '));
                        const getLink = document.createElement('a');
                        getLink.href = getOneUrl;
                        getLink.target = '_blank';
                        getLink.rel = 'noopener noreferrer';
                        getLink.textContent = I18N.getOne;
                        secondary.appendChild(getLink);
                    } else {
                        secondary.textContent = labels.secondary(formatDate(statusData.validUntil));
                    }
                }
                license.setAttribute('data-active', statusData.isActive ? '1' : '0');
                if (statusData.isActive) serialInput.setAttribute('readonly', '');
                else serialInput.removeAttribute('readonly');
                if (toggleLabel) toggleLabel.textContent = statusData.isActive ? I18N.deactivate : I18N.activate;
                const actions = root.querySelector('.mageme-ecosystem__actions');
                if (actions) actions.setAttribute('data-license-state', statusData.state);
                const expiredHeaderBtn = root.querySelector('[data-mageme-support-expired]');
                if (expiredHeaderBtn) expiredHeaderBtn.setAttribute('data-expired-date', formatDate(statusData.validUntil));
            };

            const refreshStatus = async () => {
                const badge = license.querySelector('[data-mageme-license-status]');
                const url = badge?.getAttribute('data-status-url');
                if (!url) return;
                try {
                    const resp = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                    if (!resp.ok) return;
                    const statusData = await resp.json();
                    if (statusData.stale) return;
                    applyState(statusData);
                } catch (_) {}
            };

            const sendRequest = async () => {
                const isActive = license.getAttribute('data-active') === '1';
                const url = isActive
                    ? license.getAttribute('data-deactivate-url')
                    : license.getAttribute('data-activate-url');
                const formKey = document.querySelector('input[name="form_key"]')?.value ?? '';
                const params = new URLSearchParams({
                    form_key: formKey,
                    serial: serialInput.value.trim(),
                    license_section: license.getAttribute('data-license-section') ?? '',
                    module_id: license.getAttribute('data-module-id') ?? '',
                    module_name: license.getAttribute('data-module-name') ?? ''
                });
                document.body.dispatchEvent(new Event('processStart', { bubbles: true }));
                try {
                    const resp = await fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: params.toString()
                    });
                    const data = await resp.json().catch(() => ({}));
                    renderMessages(data);
                    if (data.status) applyState(data.status);
                } catch (_) {
                    renderMessages({ errors: [I18N.connectError] });
                } finally {
                    document.body.dispatchEvent(new Event('processStop', { bubbles: true }));
                }
            };

            refreshStatus();

            toggleBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                sendRequest();
            });
            serialInput?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendRequest();
                }
            });
            serialInput?.addEventListener('click', (e) => e.stopPropagation());
        }

        // === Filter pills ===
        const filterButtons = root.querySelectorAll('.mageme-ecosystem__filter');
        if (filterButtons.length > 0) {
            const allRows = root.querySelectorAll('.mageme-ecosystem__row');
            const counts = { all: allRows.length, update: 0, installed: 0, 'not-installed': 0 };
            allRows.forEach((row) => {
                const status = row.getAttribute('data-status');
                if (status === 'update' || status === 'installed') counts.installed++;
                if (status === 'update') counts.update++;
                if (status === 'not-installed') counts['not-installed']++;
            });

            // Populate counts and disable empty filters
            filterButtons.forEach((fbtn) => {
                const filter = fbtn.getAttribute('data-filter');
                const countEl = fbtn.querySelector('.mageme-ecosystem__filter-count');
                const n = counts[filter] ?? 0;
                if (countEl) countEl.textContent = String(n);
                if (n === 0 && filter !== 'all') fbtn.setAttribute('data-empty', 'true');
            });

            setActiveFilter = (filter) => {
                body.setAttribute('data-filter', filter);
                filterButtons.forEach((fbtn) => {
                    const isActive = fbtn.getAttribute('data-filter') === filter;
                    fbtn.classList.toggle('is-active', isActive);
                    fbtn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
            };

            filterButtons.forEach((fbtn) => {
                const trigger = (e) => {
                    e.stopPropagation();
                    const filter = fbtn.getAttribute('data-filter') ?? 'all';
                    setActiveFilter(filter);
                };
                fbtn.addEventListener('click', trigger);
                fbtn.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        trigger(e);
                    }
                });
            });
        }

        // === Row description tooltip ===
        const tooltipRows = root.querySelectorAll('[data-mageme-row-tooltip]');
        if (tooltipRows.length > 0) {
            let tip = document.querySelector('.mageme-ecosystem-tooltip');
            if (!tip) {
                tip = document.createElement('div');
                tip.className = 'mageme-ecosystem-tooltip';
                tip.setAttribute('role', 'tooltip');
                document.body.appendChild(tip);
            }

            const positionTip = (row) => {
                const rect = row.getBoundingClientRect();
                const tipRect = tip.getBoundingClientRect();
                const margin = 10;
                const spaceAbove = rect.top;
                const placeTop = spaceAbove > tipRect.height + margin + 8;
                const top = placeTop
                    ? rect.top - tipRect.height - margin
                    : rect.bottom + margin;
                const idealLeft = rect.left + rect.width / 2 - tipRect.width / 2;
                const minLeft = 8;
                const maxLeft = window.innerWidth - tipRect.width - 8;
                const left = Math.max(minLeft, Math.min(maxLeft, idealLeft));
                const arrowX = (rect.left + rect.width / 2) - left;
                tip.style.top  = `${top}px`;
                tip.style.left = `${left}px`;
                tip.style.setProperty('--arrow-x', `${arrowX}px`);
                tip.dataset.placement = placeTop ? 'top' : 'bottom';
            };

            const showFor = (row) => {
                tip.textContent = row.dataset.description ?? '';
                tip.classList.add('is-visible');
                positionTip(row);
            };
            const hide = () => tip.classList.remove('is-visible');

            tooltipRows.forEach((row) => {
                row.addEventListener('mouseenter', () => showFor(row));
                row.addEventListener('mouseleave', hide);
                row.addEventListener('focusin',   () => showFor(row));
                row.addEventListener('focusout',  hide);
            });
            window.addEventListener('scroll', hide, { passive: true });
            window.addEventListener('resize', hide);
        }
    };

    const dropLoadingIndicator = (root) => {
        const loading = root.querySelector('.mageme-ecosystem__loading');
        if (loading) loading.remove();
    };

    const hydrateEcosystemBlock = (root) => {
        const renderUrl = root.getAttribute('data-ecosystem-render-url');
        if (!renderUrl) return;
        fetch(renderUrl, { method: 'GET', credentials: 'same-origin', headers: { Accept: 'text/html' } })
            .then((r) => r.ok ? r.text() : Promise.reject(new Error('render failed')))
            .then((html) => {
                const tmp = document.createElement('div');
                tmp.innerHTML = (html || '').trim();
                const newRoot = tmp.firstElementChild;
                if (newRoot && newRoot.matches('[data-mageme-ecosystem]')) {
                    root.replaceWith(newRoot);
                    initEcosystemBlock(newRoot);
                    newRoot.querySelectorAll('[data-mageme-offer-net]').forEach(initOfferNet);
                    // The re-render answers 200 even when the catalog is still out of reach, and
                    // then carries the spinner with it. Nothing more is coming — stop the wait.
                    if (newRoot.getAttribute('data-needs-refresh') === '1') {
                        dropLoadingIndicator(newRoot);
                    }
                }
            })
            .catch(() => dropLoadingIndicator(root));
    };

    // Node field behind the Pro plate: particles carry their own velocity, links are
    // recomputed per frame from distance, so connections form and dissolve on their own.
    const initOfferNet = (canvas) => {
        const plate = canvas.parentElement;
        const ctx = canvas.getContext('2d');
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const rand = (min, max) => min + Math.random() * (max - min);
        const LINK_IN = 48;
        const LINK_OUT = 54;
        const MARGIN = 12;
        let width = 0;
        let height = 0;
        let nodes = [];
        let frame = null;
        let last = 0;
        const live = new Set();

        const resize = () => {
            const dpr = window.devicePixelRatio || 1;
            const box = plate.getBoundingClientRect();
            width = box.width;
            height = box.height;
            canvas.width = Math.round(width * dpr);
            canvas.height = Math.round(height * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        };

        const seed = () => {
            // two depths: the far layer is slower, smaller and dimmer
            const layer = (count, far) => Array.from({ length: count }, () => ({
                x: rand(0, width),
                y: rand(0, height),
                vx: rand(-1, 1) * (far ? 4 : 7),
                vy: rand(-1, 1) * (far ? 2.2 : 3.8),
                r: far ? rand(0.8, 1.2) : rand(1.2, 1.8),
                a: far ? rand(0.3, 0.48) : rand(0.45, 0.7),
            }));
            nodes = [
                ...layer(Math.round(width / 20), true),
                ...layer(Math.round(width / 28), false),
            ];
            live.clear();
        };

        const draw = () => {
            ctx.clearRect(0, 0, width, height);
            ctx.strokeStyle = '#e3eaf8';
            for (let i = 0; i < nodes.length; i++) {
                const a = nodes[i];
                for (let j = i + 1; j < nodes.length; j++) {
                    const b = nodes[j];
                    const dx = Math.abs(a.x - b.x);
                    const dy = Math.abs(a.y - b.y);
                    const key = `${i}:${j}`;
                    if (dx > LINK_OUT || dy > LINK_OUT) {
                        live.delete(key);
                        continue;
                    }
                    const d = Math.hypot(dx, dy);
                    // hysteresis: a link lights up nearer than it goes out, so edges
                    // near the threshold stop flickering
                    const on = live.has(key) ? d < LINK_OUT : d < LINK_IN;
                    if (!on) {
                        live.delete(key);
                        continue;
                    }
                    live.add(key);
                    const k = 1 - d / LINK_OUT;
                    ctx.globalAlpha = k * 0.42;
                    ctx.lineWidth = 0.4 + k * 0.4;
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            }
            ctx.fillStyle = '#eef2fb';
            nodes.forEach((n) => {
                ctx.globalAlpha = n.a;
                ctx.beginPath();
                ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
                ctx.fill();
            });
            ctx.globalAlpha = 1;
        };

        const step = (now) => {
            const dt = Math.min((now - last) / 1000, 0.05);
            last = now;
            nodes.forEach((n) => {
                n.x += n.vx * dt;
                n.y += n.vy * dt;
                // wrap around instead of bouncing: a bounce off an invisible wall reads
                // as a glitch on a strip this short
                if (n.x < -MARGIN) { n.x = width + MARGIN; } else if (n.x > width + MARGIN) { n.x = -MARGIN; }
                if (n.y < -MARGIN) { n.y = height + MARGIN; } else if (n.y > height + MARGIN) { n.y = -MARGIN; }
            });
            draw();
            frame = window.requestAnimationFrame(step);
        };

        const start = () => {
            if (frame || reduced) { return; }
            last = window.performance.now();
            frame = window.requestAnimationFrame(step);
        };
        const stop = () => {
            if (!frame) { return; }
            window.cancelAnimationFrame(frame);
            frame = null;
        };

        resize();
        seed();
        draw();
        start();

        // nothing runs while the plate is off-screen or the tab is hidden
        if (window.IntersectionObserver) {
            new IntersectionObserver(([entry]) => (entry.isIntersecting ? start() : stop()))
                .observe(plate);
        }
        document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
        if (window.ResizeObserver) {
            let pending = null;
            new ResizeObserver(() => {
                window.clearTimeout(pending);
                pending = window.setTimeout(() => { resize(); seed(); draw(); }, 120);
            }).observe(plate);
        }
    };

    const boot = () => {
        const roots = document.querySelectorAll('[data-mageme-ecosystem]');
        if (roots.length === 0) {
            return;
        }
        const anyNeedsRefresh = Array.from(roots).some((r) => r.getAttribute('data-needs-refresh') === '1');

        if (!anyNeedsRefresh && roots[0]) {
            const refreshUrl = roots[0].getAttribute('data-catalog-refresh-url');
            if (refreshUrl) {
                fetch(refreshUrl, { method: 'GET', credentials: 'same-origin', headers: { Accept: 'application/json' } })
                    .catch(() => { /* fire-and-forget */ });
            }
        }

        roots.forEach((root) => {
            initEcosystemBlock(root);
            root.querySelectorAll('[data-mageme-offer-net]').forEach(initOfferNet);
            if (root.getAttribute('data-needs-refresh') === '1') {
                hydrateEcosystemBlock(root);
            }
        });
    };

    // The file is loaded from <head>, so the panel may not be parsed yet.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
}
