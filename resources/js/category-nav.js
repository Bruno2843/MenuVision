/**
 * Filter the dish cards by category.
 *
 * The public menu marks its category bar with `data-category-nav`, every
 * category button with `data-category-link` (carrying its category id in
 * `data-categoria`), and the "Todas" button with `data-category-all`. Dish
 * cards are marked with `data-dish-card` and the grid with `data-grid`.
 *
 * The server already sent only the available dishes; this toggle only decides
 * which of those are visible, so no business logic lives on the client. On any
 * other page the call is a no-op.
 */
export const startCategoryNav = () => {
    const nav = document.querySelector('[data-category-nav]');

    if (!nav) {
        return;
    }

    const categoryButtons = [...nav.querySelectorAll('[data-category-link]')];
    const allButton = nav.querySelector('[data-category-all]');
    const resetLink = document.querySelector('[data-reset-filtro]');
    const cards = [...document.querySelectorAll('[data-dish-card]')];
    const emptyNotice = document.querySelector('[data-filtro-vacio]');
    const countHint = document.querySelector('[data-platos-count]');

    const applyFilter = (categoryId) => {
        let visible = 0;

        cards.forEach((card) => {
            const matches = categoryId === null || card.dataset.categoria === String(categoryId);

            card.classList.toggle('hidden', ! matches);

            if (matches) {
                visible += 1;
            }
        });

        categoryButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.categoria === String(categoryId)));
        });

        allButton?.setAttribute('aria-pressed', String(categoryId === null));

        countHint?.replaceChildren(document.createTextNode(
            `${visible} ${visible === 1 ? 'plato' : 'platos'} disponibles`
        ));

        emptyNotice?.classList.toggle('hidden', visible > 0);
    };

    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            applyFilter(button.dataset.categoria ?? null);
        });
    });

    allButton?.addEventListener('click', () => {
        applyFilter(null);
    });

    // "Ver todo" is an anchor to the grid, so it resets the filter first and
    // then lets the browser scroll to the grid as usual.
    resetLink?.addEventListener('click', (event) => {
        applyFilter(null);

        if (event.defaultPrevented) {
            return;
        }

        resetLink.scrollIntoView({ behavior: 'smooth', block: 'start' });
        event.preventDefault();
    });
};