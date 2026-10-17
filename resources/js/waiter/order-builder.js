/**
 * Build the order the waiter is taking.
 *
 * The form renders the catalog once, inside the picker overlay, and this script
 * is what turns a chosen dish into a row of the order list carrying the
 * `items[dishId][cantidad]` and `items[dishId][observaciones]` fields the server
 * expects. Nothing about the order is decided here: the rows are plain inputs,
 * and the server validates every dish and quantity again on submit.
 *
 * The page marks itself with `data-order-items` (the row list), `data-order-add`
 * (the button that opens the picker), `data-order-picker` (the overlay),
 * `data-order-dish` (one button per available dish), `data-order-row-template`
 * (the row the browser clones) and `data-order-old-items` (what was chosen
 * before a validation error). On any other page the call is a no-op.
 */
export function startOrderBuilder() {
    const rows = document.querySelector('[data-order-items]');
    const addButton = document.querySelector('[data-order-add]');
    const picker = document.querySelector('[data-order-picker]');
    const template = document.querySelector('[data-order-row-template]');

    if (! rows || ! addButton || ! picker || ! template) {
        return;
    }

    const emptyNotice = rows.querySelector('[data-order-empty]');

    const refreshEmptyState = () => {
        const hasRows = rows.querySelector('[data-order-row]') !== null;
        emptyNotice?.classList.toggle('hidden', hasRows);
    };

    const openPicker = () => {
        picker.hidden = false;
        picker.querySelector('[data-order-dish]')?.focus();
    };

    const closePicker = () => {
        picker.hidden = true;
        addButton.focus();
    };

    /**
     * The row already on the order for a dish, or null when it is not there.
     *
     * @param {string|number} dishId
     * @returns {HTMLElement|null}
     */
    const rowFor = (dishId) =>
        rows.querySelector(`[data-order-row][data-dish-id="${dishId}"]`);

    /**
     * Add a dish to the order, or bump its quantity when it is already on it.
     *
     * @param {{id: string, name: string, price: string}} dish
     */
    const addDish = (dish) => {
        const existing = rowFor(dish.id);

        if (existing) {
            const quantity = existing.querySelector('[data-order-row-qty]');
            quantity.value = String(Math.min(99, (Number.parseInt(quantity.value, 10) || 0) + 1));
            existing.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const row = template.content.firstElementChild.cloneNode(true);
        row.dataset.dishId = dish.id;
        row.querySelector('[data-order-row-name]').textContent = dish.name;
        row.querySelector('[data-order-row-price]').textContent = `$${dish.price}`;

        const quantity = row.querySelector('[data-order-row-qty]');
        quantity.name = `items[${dish.id}][cantidad]`;

        const observation = row.querySelector('[data-order-row-obs]');
        observation.name = `items[${dish.id}][observaciones]`;

        rows.append(row);
        refreshEmptyState();
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    // The catalog the server rendered, keyed by dish id, so a dish chosen in the
    // picker becomes a row without another round trip.
    const dishes = new Map(
        [...picker.querySelectorAll('[data-order-dish]')].map((button) => [button.dataset.dishId, button]),
    );

    const addDishById = (dishId) => {
        const button = dishes.get(String(dishId));

        if (! button) {
            return;
        }

        addDish({
            id: button.dataset.dishId,
            name: button.dataset.dishName,
            price: button.dataset.dishPrice,
        });
    };

    // After a validation error the page comes back with what the waiter had
    // chosen, so the rows the form rejected are rebuilt from `old('items')`.
    const oldItems = document.querySelector('[data-order-old-items]');

    if (oldItems) {
        try {
            Object.entries(JSON.parse(oldItems.textContent || '{}')).forEach(([dishId, item]) => {
                const quantity = Number.parseInt(item?.cantidad ?? 0, 10);

                if (! Number.isFinite(quantity) || quantity < 1) {
                    return;
                }

                addDishById(dishId);

                const row = rowFor(dishId);

                if (! row) {
                    return;
                }

                row.querySelector('[data-order-row-qty]').value = String(Math.min(99, quantity));

                if (item?.observaciones) {
                    row.querySelector('[data-order-row-obs]').value = item.observaciones;
                }
            });
        } catch {
            // Malformed state is not worth breaking the form over: the waiter
            // simply starts with an empty order.
        }
    }

    addButton.addEventListener('click', openPicker);

    picker.querySelectorAll('[data-order-picker-close]').forEach((button) =>
        button.addEventListener('click', closePicker),
    );

    picker.addEventListener('click', (event) => {
        if (event.target === picker) {
            closePicker();
        }
    });

    picker.querySelectorAll('[data-order-dish]').forEach((button) =>
        button.addEventListener('click', () => addDishById(button.dataset.dishId)),
    );

    rows.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-order-row-remove]');

        if (remove) {
            remove.closest('[data-order-row]')?.remove();
            refreshEmptyState();
            return;
        }

        const step = event.target.closest('[data-order-row-increment], [data-order-row-decrement]');

        if (! step) {
            return;
        }

        const quantity = step.closest('[data-order-row]')?.querySelector('[data-order-row-qty]');

        if (! quantity) {
            return;
        }

        const current = Number.parseInt(quantity.value, 10) || 1;
        const next = step.hasAttribute('data-order-row-increment') ? current + 1 : current - 1;

        quantity.value = String(Math.min(99, Math.max(1, next)));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ! picker.hidden) {
            closePicker();
        }
    });
}

export default startOrderBuilder;
