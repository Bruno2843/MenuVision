import QRScanner from '../qr-scanner';

/**
 * Wires the waiter's "Escanear QR" button to the camera overlay.
 *
 * The decoded value is never interpreted here: it is posted to the waiter
 * resolver, and the table the waiter lands on is the one the server resolved
 * from the stored QR code. The browser only learns which table it was and where
 * to go, so a value read off a sticker cannot point the order at another table.
 *
 * The camera is released as soon as a code resolves, as soon as the overlay is
 * closed and as soon as the page goes away, so it never stays on with nobody
 * looking at it.
 */
export function startWaiterQrScanner() {
    const button = document.querySelector('[data-waiter-scan]');
    const overlay = document.querySelector('[data-waiter-scan-overlay]');
    const video = document.querySelector('[data-waiter-scan-video]');
    const status = document.querySelector('[data-waiter-scan-status]');

    if (!button || !overlay || !video || !status) {
        return;
    }

    const resolveUrl = button.dataset.resolveUrl;
    const closeButtons = overlay.querySelectorAll('[data-waiter-scan-close]');

    let scanner = null;
    // Set for the whole time a code is being checked, so a sticker held in front
    // of the camera sends one request instead of one per frame.
    let resolviendo = false;

    const showStatus = (message, error = false) => {
        status.textContent = message;
        status.classList.toggle('text-red-400', error);
        status.classList.toggle('text-gray-300', !error);
    };

    const stopScanner = () => {
        scanner?.stop();
        scanner = null;
    };

    const close = () => {
        stopScanner();
        resolviendo = false;
        overlay.hidden = true;
        button.focus();
    };

    /**
     * Open the camera and read from it until a code resolves.
     *
     * Started again after a rejected code so the waiter can aim at another
     * sticker without leaving the overlay.
     *
     * @returns {Promise<void>}
     */
    const startCamera = async () => {
        if (scanner) {
            return;
        }

        scanner = new QRScanner(video, resolve);

        try {
            await scanner.start();
        } catch (error) {
            scanner = null;
            showStatus('No se pudo acceder a la cámara. Verificá los permisos del navegador.', true);
        }
    };

    /**
     * Ask the server which table the decoded value stands for.
     *
     * @param {string} value
     * @returns {Promise<void>}
     */
    const resolve = async (value) => {
        resolviendo = true;
        showStatus('Buscando la mesa…');

        try {
            const response = await fetch(resolveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    // The CSRF token the staff layout already publishes; the
                    // route is a normal web route, so the protection stays on.
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ code: value }),
            });

            if (response.status === 419) {
                // The token no longer matches the session, so retrying would
                // fail too: the overlay is closed and the waiter signs in again.
                showStatus('La sesión venció. Volvé a iniciar sesión.', true);
                stopScanner();
                return;
            }

            const data = await response.json();

            if (!response.ok || !data.success || !data.url) {
                showStatus(
                    response.ok
                        ? data.message || 'El código QR no es válido.'
                        : 'No se pudo comunicar con el servidor. Intentá nuevamente.',
                    true
                );
                resolviendo = false;
                startCamera();
                return;
            }

            showStatus(`Mesa ${data.table.numero} detectada`);

            // A short beat so the confirmation is read before the page changes.
            setTimeout(() => {
                window.location.href = data.url;
            }, 600);
        } catch (error) {
            showStatus('No se pudo comunicar con el servidor. Intentá nuevamente.', true);
            resolviendo = false;
            startCamera();
        }
    };

    const open = async () => {
        if (scanner || resolviendo || !overlay.hidden) {
            return;
        }

        overlay.hidden = false;
        showStatus('Apuntá la cámara al código QR de la mesa.');

        await startCamera();
    };

    button.addEventListener('click', open);
    closeButtons.forEach((element) => element.addEventListener('click', close));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !overlay.hidden) {
            close();
        }
    });

    window.addEventListener('pagehide', stopScanner);
}

export default startWaiterQrScanner;
