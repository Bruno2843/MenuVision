/**
 * How far below the top of the viewport the reader is considered to be looking.
 * A section becomes the active one as soon as its title passes under the
 * category bar, so the bar height is subtracted from the observed area.
 *
 * The remaining bottom margin keeps a short trailing section from winning over
 * a taller one that started before it: the first visible section in document
 * order is the one the reader is actually on.
 */
const ROOT_MARGIN_BOTTOM = '-55%';

/**
 * Highlight the category the reader is currently on.
 *
 * The public menu marks its category bar with `data-category-nav` and every
 * chip with `data-category-link`. On any other page this call is a no-op.
 */
export const startCategoryNav = () => {
    const nav = document.querySelector('[data-category-nav]');

    if (!nav) {
        return;
    }

    const links = [...nav.querySelectorAll('[data-category-link]')];
    const targets = links
        .map((link) => document.getElementById(link.hash.slice(1)))
        .filter((section) => section !== null);

    if (targets.length === 0) {
        return;
    }

    const ACTIVE_CLASSES = ['border-gold-400', 'bg-gold-500/15', 'text-gold-200'];
    const IDLE_CLASSES = ['border-gray-800', 'text-gray-400', 'hover:border-gold-600/60', 'hover:text-gold-300'];

    const markActive = (id) => {
        links.forEach((link) => {
            const isActive = link.hash === `#${id}`;

            ACTIVE_CLASSES.forEach((className) => link.classList.toggle(className, isActive));
            IDLE_CLASSES.forEach((className) => link.classList.toggle(className, ! isActive));

            if (isActive) {
                link.setAttribute('aria-current', 'true');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    // Keep the highlighted chip inside the horizontal scroller on narrow
    // screens, where most of the categories are off screen.
    const revealActive = (id) => {
        links
            .find((link) => link.hash === `#${id}`)
            ?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
    };

    const visible = new Set();

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                visible.add(entry.target);
            } else {
                visible.delete(entry.target);
            }
        });

        const current = targets.find((section) => visible.has(section));

        if (!current) {
            return;
        }

        markActive(current.id);
        revealActive(current.id);
    }, {
        rootMargin: `-${nav.offsetHeight}px 0px ${ROOT_MARGIN_BOTTOM}`,
    });

    targets.forEach((section) => observer.observe(section));
};
