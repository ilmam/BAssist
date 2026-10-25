/**
 * Keeps the persistent sidebar (see template: @persist('sidebar')) in step with wire:navigate,
 * and loads a project's folders the first time the project is opened.
 *
 * The menu is rendered once, so the server cannot mark the new active item. After each
 * navigation this marks the link that matches the address bar and opens its folders.
 * A link that carries a project (`project_id` in its query, or /projects/{id}/…) only
 * matches a page of the same project, so the same entity list in two projects is not
 * both "active".
 */
function parse(href) {
    try {
        return new URL(href, window.location.origin);
    } catch {
        return null;
    }
}

function pathOf(url) {
    return url.pathname.replace(/\/+$/, '') || '/';
}

function projectOf(url) {
    const fromQuery = url.searchParams.get('project_id');
    if (fromQuery) {
        return fromQuery;
    }

    const match = /^\/projects\/(\d+)(\/|$)/.exec(url.pathname);

    return match ? match[1] : null;
}

/**
 * Metronic's components register their global click / keyboard handlers in `initHandlers()`, and
 * `KTMenu.init()` (and so `KTComponents.init()`) calls it every time with no guard. Anything that
 * initialises the library a second time then makes one click run each handler twice: an accordion
 * opens and closes at once, which is exactly "the project folders do not open". Make `initHandlers`
 * run once. This module runs before the library's own DOM-ready start-up, so the first call still happens.
 */
['KTMenu', 'KTDropdown', 'KTDrawer', 'KTModal', 'KTToggle', 'KTTabs', 'KTAccordion', 'KTCollapse', 'KTTooltip'].forEach((name) => {
    const component = window[name];
    if (!component || typeof component.initHandlers !== 'function' || component.initHandlers.__once) {
        return;
    }

    const original = component.initHandlers;
    let done = false;
    component.initHandlers = function (...args) {
        if (done) {
            return undefined;
        }
        done = true;

        return original.apply(this, args);
    };
    component.initHandlers.__once = true;
});

/**
 * Create Metronic component instances for markup that appeared after the first load (a page swapped in
 * by wire:navigate, a project's folders loaded on demand).
 *
 * Deliberately NOT `KTComponents.init()`: that also registers the components' global click / keyboard
 * handlers again, so every call made one menu click toggle an item several times (project folders
 * would not open). `createInstances()` only wraps elements that have no instance yet.
 */
const COMPONENTS = ['KTMenu', 'KTDropdown', 'KTDrawer', 'KTModal', 'KTSelect', 'KTTabs', 'KTToggle', 'KTTooltip',
    'KTAccordion', 'KTCollapse', 'KTScrollable', 'KTSticky', 'KTReparent', 'KTTogglePassword', 'KTImageInput'];

function initComponents() {
    COMPONENTS.forEach((name) => {
        try {
            window[name]?.createInstances?.();
        } catch {
            // A component that cannot wrap this markup must not stop the others.
        }
    });
}

window.bassistInitComponents = initComponents;

function syncSidebar() {
    const menu = document.getElementById('sidebar_menu');
    if (!menu) {
        return;
    }

    const here = parse(window.location.href);
    if (!here) {
        return;
    }

    const herePath = pathOf(here);
    const hereProject = projectOf(here);
    let best = null;
    let bestScore = -1;

    menu.querySelectorAll('a[href]').forEach((link) => {
        const url = parse(link.getAttribute('href'));
        if (!url) {
            return;
        }

        const path = pathOf(url);
        const matches = path === herePath || (path !== '/' && herePath.startsWith(path + '/'));
        if (!matches) {
            return;
        }

        const linkProject = projectOf(url);
        if (linkProject !== null && linkProject !== hereProject) {
            return;
        }

        const score = path.length * 2 + (linkProject !== null ? 1 : 0);
        if (score > bestScore) {
            best = link;
            bestScore = score;
        }
    });

    menu.querySelectorAll('.kt-menu-item.active, .kt-menu-item.here').forEach((el) => el.classList.remove('active', 'here'));

    const item = best?.closest('.kt-menu-item');
    if (!item) {
        return;
    }

    item.classList.add('active');
    for (let el = item.parentElement?.closest('.kt-menu-item'); el; el = el.parentElement?.closest('.kt-menu-item')) {
        el.classList.add('here', 'show');
    }
}

/** Fetch the folders of a project into its (empty) accordion, once. */
async function loadProject(item) {
    const url = item.getAttribute('data-nav-lazy-url');
    if (!url || item.dataset.navLoading === '1') {
        return;
    }

    item.dataset.navLoading = '1';
    try {
        const response = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) {
            throw new Error(String(response.status));
        }

        const accordion = item.querySelector(':scope > .kt-menu-accordion');
        if (accordion) {
            accordion.innerHTML = await response.text();
            // Metronic animated the (then empty) panel open to a height of 0 when the row was clicked;
            // now that the folders are in, let an open panel size itself.
            if (item.classList.contains('show')) {
                accordion.style.height = '';
                accordion.style.display = 'flex';
            }
        }
        item.removeAttribute('data-nav-lazy-url');
        initComponents();
        syncSidebar();
    } catch {
        // Leave it unloaded: the next click tries again.
    } finally {
        delete item.dataset.navLoading;
    }
}

document.addEventListener('click', (event) => {
    const row = event.target instanceof Element ? event.target.closest('.kt-menu-item[data-nav-lazy-url] > .kt-menu-link') : null;
    if (row) {
        loadProject(row.parentElement);
    }
}, true); // capture: Metronic's accordion handler may stop the event before it bubbles here

window.bassistSyncSidebar = syncSidebar;
