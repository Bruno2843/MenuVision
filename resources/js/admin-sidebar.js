/**
 * Slide-over navigation for the admin panel on small screens.
 *
 * The sidebar is a permanent column from the `lg` breakpoint up, so above that
 * size this module does nothing. Below it, the sidebar is translated off-canvas
 * and the two header buttons open and close it. The drawer is a plain element
 * rather than a `<dialog>` so it can animate its transform, and the open state
 * is mirrored onto `aria-expanded` and `inert` so a screen reader and a keyboard
 * do not wander into a drawer that is visually closed.
 */
export function startAdminSidebar() {
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const openButton = document.querySelector('[data-sidebar-abrir]');
    const closeButton = document.querySelector('[data-sidebar-cerrar]');

    if (!sidebar || !overlay || !openButton || !closeButton) {
        return;
    }

    const setOpen = (open) => {
        sidebar.classList.toggle('-translate-x-full', !open);
        overlay.classList.toggle('hidden', !open);
        overlay.setAttribute('aria-hidden', String(!open));

        if (open) {
            closeButton.focus();
        } else {
            openButton.focus();
        }
    };

    openButton.addEventListener('click', () => setOpen(true));
    closeButton.addEventListener('click', () => setOpen(false));
    overlay.addEventListener('click', () => setOpen(false));

    // Following a link inside the drawer on a phone should close it, otherwise
    // the new page renders behind an open overlay.
    sidebar.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
            setOpen(false);
        }
    });
}
