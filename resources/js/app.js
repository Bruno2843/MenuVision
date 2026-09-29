import GLBViewer from './3d/viewer';
import { startCategoryNav } from './category-nav';
import QRScanner from './qr-scanner';
import { startLiveRefresh } from './live-refresh';

// The kitchen and waiter dashboards mark their live area with `data-poll-url`.
// On every other page this call is a no-op.
startLiveRefresh();

// The public menu marks its category bar with `data-category-nav` and
// highlights the category being read. Elsewhere this call is a no-op.
startCategoryNav();

/**
 * Initialise every GLB viewer element found in the page.
 *
 * A viewer is any element with the `data-glb-viewer` attribute; its value is
 * the URL of the `.glb` model to load. This wiring is ready to be reused from
 * any Blade view.
 */
document.querySelectorAll('[data-glb-viewer]').forEach((container) => {
    const url = container.dataset.glbViewer;
    const viewer = new GLBViewer(container);
    viewer.load(url);
});

/**
 * Wire the "Escanear QR" button to the camera scanner overlay on the landing
 * page. A decoded QR that points to this site is followed directly; any other
 * value is treated as a table code and routed to the public menu.
 */
const scanOverlay = document.getElementById('scan-overlay');
const scanVideo = document.getElementById('scan-video');
const scanStatus = document.getElementById('scan-status');
const scanButton = document.getElementById('scan-qr-button');
const closeScanButton = document.getElementById('close-scan-button');

if (scanOverlay && scanVideo && scanStatus) {
    let scanner = null;

    const valueUrl = (value) => {
        if (value.startsWith(window.location.origin)) {
            return value;
        }

        return '/menu/' + encodeURIComponent(value);
    };

    const closeScanner = () => {
        scanner?.stop();
        scanner = null;
        scanOverlay.classList.add('hidden');
    };

    const openScanner = async () => {
        if (scanner) {
            return;
        }

        scanOverlay.classList.remove('hidden');
        scanStatus.classList.remove('text-red-600');
        scanStatus.textContent = 'Apuntá la cámara al código QR para escanearlo.';

        scanner = new QRScanner(scanVideo, (value) => {
            closeScanner();
            window.location.href = valueUrl(value);
        });

        try {
            await scanner.start();
        } catch (error) {
            scanStatus.classList.add('text-red-600');
            scanStatus.textContent = 'No se pudo acceder a la cámara. Verificá permisos o usá HTTPS.';
            scanner = null;
        }
    };

    scanButton?.addEventListener('click', openScanner);
    closeScanButton?.addEventListener('click', closeScanner);

    scanOverlay.addEventListener('click', (event) => {
        if (event.target === scanOverlay) {
            closeScanner();
        }
    });
}
