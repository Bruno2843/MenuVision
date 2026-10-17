/**
 * Filter the kitchen board by status.
 *
 * The board is server-rendered and polled: `startLiveRefresh` swaps its HTML
 * whenever the orders change, which would drop a filter applied by hand. This
 * module keeps the selection in memory and re-applies it to the fresh markup,
 * so the kitchen keeps looking at the same status after a refresh.
 *
 * The page marks itself with `data-kitchen-board` (the region that holds the
 * filters and the cards), one button per `data-kitchen-filter` and one card per
 * `data-order-state`. On any other screen the call is a no-op.
 */
export function startKitchenBoardFilter() {
    const board = document.querySelector('[data-kitchen-board]');

    if (! board) {
        return;
    }

    let active = 'todos';

    const apply = () => {
        board.querySelectorAll('[data-kitchen-filter]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.kitchenFilter === active));
        });

        let visible = 0;

        board.querySelectorAll('[data-order-state]').forEach((card) => {
            const matches = active === 'todos' || card.dataset.orderState === active;
            card.classList.toggle('hidden', ! matches);

            if (matches) {
                visible += 1;
            }
        });

        board.querySelector('[data-kitchen-empty]')?.classList.toggle('hidden', visible > 0);
    };

    // Delegated on the document so the buttons keep working after the board is
    // replaced by a poll, without rebinding anything.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-kitchen-filter]');

        if (! button || ! board.contains(button)) {
            return;
        }

        active = button.dataset.kitchenFilter;
        apply();
    });

    // The board only changes its children when a poll replaces its HTML, so the
    // observer fires on exactly the moments the filter has to be restored.
    new MutationObserver(apply).observe(board, { childList: true, subtree: true });

    apply();
}

export default startKitchenBoardFilter;
