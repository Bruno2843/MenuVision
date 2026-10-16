// Dish photographs and the scenes that stand in for them.
//
// A dish that has no photograph is given one on the server, so a card is never
// rendered without an image to show. The uploaded file, however, can still fail
// after the markup was produced: the row points at a path that is not on the
// disk, the symlink is missing, or the request for it is answered with an error.
// In that case the `<img>` keeps the dark frame the card reserves for it and
// shows nothing at all, which reads exactly like a dish without a photograph
// except that it is not even the intended fallback.
//
// Every dish image therefore carries `data-imagen-respaldo`, the picture the
// server would have chosen had the photograph not existed. This module swaps it
// in once, and never again, so a fallback that also happens to fail cannot turn
// into a loop of requests.

/**
 * Replace the source of every image that failed to load with its fallback.
 *
 * Runs on the whole document, so the dish cards of the public menu, the
 * photograph on the dish detail and the preview in the admin form are all
 * covered without any of them knowing about this module. On every other screen
 * the query simply matches nothing.
 */
export function startImageFallbacks() {
    document.querySelectorAll('img[data-imagen-respaldo]').forEach((imagen) => {
        const respaldo = imagen.dataset.imagenRespaldo;

        if (!respaldo) {
            return;
        }

        imagen.addEventListener(
            'error',
            () => {
                // Cleared first: an error on the fallback itself must not put the
                // browser back in a state where swapping would fire again.
                delete imagen.dataset.imagenRespaldo;
                imagen.src = respaldo;
            },
            { once: true },
        );
    });
}
