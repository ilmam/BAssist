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
 * Metronic's components register their global click / keyboard handlers on <body> in `initHandlers()`.
 * Two things go wrong with that here:
 *
 *  1. `KTMenu.init()` (and so `KTComponents.init()`) calls it every time with no guard, so a second
 *     initialisation made one click run each handler twice: an accordion opened and closed at once.
 *  2. wire:navigate replaces <body>, and the handlers were attached to the old one. After the first
 *     navigation nothing in the menu reacted to clicks (project folders would not open).
 *
 * So `initHandlers` runs once *per body element*: a repeated call on the same body does nothing, and a
 * call after a navigation attaches the handlers to the new body. This module runs before the library's
 * own DOM-ready start-up, so the first call still happens normally.
 */
const HANDLER_COMPONENTS = ['KTMenu', 'KTDropdown', 'KTDrawer', 'KTModal', 'KTToggle', 'KTTabs', 'KTAccordion', 'KTCollapse', 'KTTooltip'];

HANDLER_COMPONENTS.forEach((name) => {
    const component = window[name];
    if (!component || typeof component.initHandlers !== 'function' || component.initHandlers.__perBody) {
        return;
    }

    const original = component.initHandlers;
    let boundTo = null;
    component.initHandlers = function (...args) {
        if (boundTo === document.body) {
            return undefined;
        }
        boundTo = document.body;

        return original.apply(this, args);
    };
    component.initHandlers.__perBody = true;
});

/**
 * Create Metronic component instances for markup that appeared after the first load (a page swapped in
 * by wire:navigate, a project's folders loaded on demand).
 *
 * Not `KTComponents.init()`: that registers the global handlers again on the same body (see above).
 * Here `initHandlers()` (once per body, guarded above) re-attaches them after a navigation and
 * `createInstances()` only wraps elements that have no instance yet.
 */
const COMPONENTS = ['KTMenu', 'KTDropdown', 'KTDrawer', 'KTModal', 'KTSelect', 'KTTabs', 'KTToggle', 'KTTooltip',
    'KTAccordion', 'KTCollapse', 'KTScrollable', 'KTSticky', 'KTReparent', 'KTTogglePassword', 'KTImageInput'];

function initComponents() {
    COMPONENTS.forEach((name) => {
        try {
            window[name]?.initHandlers?.();
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
    if (item) {
        item.classList.add('active');
        for (let el = item.parentElement?.closest('.kt-menu-item'); el; el = el.parentElement?.closest('.kt-menu-item')) {
            el.classList.add('here', 'show');
        }
    }

    followProject(menu, hereProject);
}

/** Open or close an accordion item the way Metronic leaves it. */
function setOpen(item, open) {
    item.classList.toggle('show', open);
    const panel = item.querySelector(':scope > .kt-menu-accordion');
    if (panel) {
        panel.style.height = open ? '' : '0px';
        panel.style.display = open ? 'flex' : '';
    }
}

/**
 * The project of the current page is the one that is open: its folders are loaded if they are not yet,
 * and the project that was open before is closed. Without this, clicking a project's name only made it
 * bold while the previous project stayed open (the menu is not rebuilt on navigation).
 */
function followProject(menu, projectId) {
    if (!projectId) {
        return;
    }

    const projects = [...menu.querySelectorAll('.kt-menu-item-accordion')].filter((item) => {
        const href = item.querySelector(':scope > .kt-menu-link a[href]')?.getAttribute('href') ?? '';

        return /\/projects\/\d+\/dashboard/.test(href);
    });

    projects.forEach((item) => {
        const href = item.querySelector(':scope > .kt-menu-link a[href]').getAttribute('href');
        const match = /\/projects\/(\d+)\/dashboard/.exec(href);
        const isCurrent = match !== null && match[1] === projectId;

        if (isCurrent) {
            setOpen(item, true);
            if (item.hasAttribute('data-nav-lazy-url')) {
                loadProject(item);
            }
        } else if (item.classList.contains('show')) {
            setOpen(item, false);
        }
    });
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
            // A project is only fetched because someone is opening it. Metronic animated the (then empty)
            // panel open to a height of 0, which never finishes and never marks it open; now that the
            // folders are in, open it for real and let it size itself.
            setOpen(item, true);
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

/**
 * Project rows are opened and closed here, not by Metronic's menu: it animates a panel whose folders are
 * still being fetched to a height of 0 and never marks it open, and it keeps its own idea of what is open
 * after the page swaps. A click on the row (not on the project's name, which navigates) opens the project,
 * loading its folders first time, and closes the other projects like the rest of the accordion.
 */
document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const row = target?.closest('.kt-menu-item[data-nav-project] > .kt-menu-link');
    if (!row || target.closest('a[href]')) {
        return;
    }

    const item = row.parentElement;
    const opening = !item.classList.contains('show');

    if (opening) {
        item.closest('#sidebar_menu')?.querySelectorAll('.kt-menu-item[data-nav-project].show').forEach((other) => {
            if (other !== item) {
                setOpen(other, false);
            }
        });
    }

    setOpen(item, opening);
    if (opening && item.hasAttribute('data-nav-lazy-url')) {
        loadProject(item);
    }
});

window.bassistSyncSidebar = syncSidebar;
