/**
 * Light / dark / system appearance, chosen in the user menu.
 *
 * Metronic already supports the `dark` class on <html>; the layout restores the saved
 * choice before paint (key `kt-theme`: light | dark | system). This only lets the
 * user change it, applies it at once, and follows the operating system while the
 * choice is "system".
 */
const KEY = 'kt-theme';
const media = window.matchMedia('(prefers-color-scheme: dark)');

function stored() {
    try {
        return localStorage.getItem(KEY) || 'light';
    } catch {
        return 'light';
    }
}

function resolve(mode) {
    return mode === 'system' ? (media.matches ? 'dark' : 'light') : mode;
}

function markChoices(mode) {
    document.querySelectorAll('[data-theme-choice]').forEach((button) => {
        const active = button.dataset.themeChoice === mode;
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.classList.toggle('is-active', active);
    });
}

function apply(mode) {
    const root = document.documentElement;
    root.classList.remove('light', 'dark');
    root.classList.add(resolve(mode));
    markChoices(mode);
}

document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-theme-choice]') : null;
    if (!button) {
        return;
    }

    const mode = button.dataset.themeChoice;
    try {
        localStorage.setItem(KEY, mode);
    } catch {
        // Private mode: the choice lasts until the page is closed.
    }
    apply(mode);
});

media.addEventListener('change', () => {
    if (stored() === 'system') {
        apply('system');
    }
});

// The user menu is part of the page body, so mark the active choice after every navigation too.
document.addEventListener('livewire:navigated', () => markChoices(stored()));
document.addEventListener('DOMContentLoaded', () => markChoices(stored()));
if (document.readyState !== 'loading') {
    markChoices(stored());
}
