/**
 * How long to wait between two refresh attempts of a live region.
 */
const POLL_INTERVAL_MS = 15000;

/**
 * Whether the user is currently interacting with a live region. Swapping the
 * HTML of a region they are using would throw away a half typed reason or
 * collapse an expanded panel, so those regions are skipped this round and
 * picked up on the next one.
 *
 * @param {HTMLElement} region
 * @returns {boolean}
 */
const isBusy = (region) =>
    region.contains(document.activeElement) || region.querySelector('details[open]') !== null;

/**
 * Keep every `[data-poll-url]` region of the page in sync with its server
 * side fragment, so the kitchen and the waiter see each other's progress
 * without reloading by hand.
 *
 * Only the regions whose HTML actually changed are replaced, which leaves the
 * scroll position and the rest of the layout untouched.
 *
 * @param {number} intervalMs
 */
export const startLiveRefresh = (intervalMs = POLL_INTERVAL_MS) => {
    const regions = [...document.querySelectorAll('[data-poll-url]')];

    if (regions.length === 0) {
        return;
    }

    // The markup each region was rendered with, used to skip no-op updates.
    const known = new Map(regions.map((region) => [region, region.innerHTML]));

    const refresh = async () => {
        if (document.hidden) {
            return;
        }

        await Promise.all(regions.map(async (region) => {
            try {
                const response = await fetch(region.dataset.pollUrl, {
                    headers: { Accept: 'text/html' },
                });

                if (!response.ok) {
                    return;
                }

                const html = await response.text();

                if (html === known.get(region) || isBusy(region)) {
                    return;
                }

                region.innerHTML = html;
                known.set(region, html);
            } catch {
                // A failed poll is not worth showing: the next tick retries.
            }
        }));
    };

    setInterval(refresh, intervalMs);

    // Coming back to the tab is when the numbers are most likely to be stale,
    // so refresh straight away instead of waiting out the current interval.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refresh();
        }
    });
};
