import GLBViewer from './3d/viewer';
import { startAdminSidebar } from './admin-sidebar';
import { startCategoryNav } from './category-nav';
import { startImageFallbacks } from './imagen-respaldo';
import QRScanner from './qr-scanner';
import { startLiveRefresh } from './live-refresh';
import { startWaiterQrScanner } from './waiter/qr-scanner';

// The kitchen and waiter dashboards mark their live area with `data-poll-url`.
// On every other page this call is a no-op.
startLiveRefresh();

// The admin panel marks its navigation drawer with `data-sidebar`. On the other
// panels, and on the public menu, this call is a no-op.
startAdminSidebar();

// The public menu marks its category bar with `data-category-nav` and
// highlights the category being read. Elsewhere this call is a no-op.
startCategoryNav();

// Every dish image declares, in `data-imagen-respaldo`, the picture the server
// would have picked had the uploaded photograph not been there. This call swaps
// that picture in when the photograph cannot be loaded, so a dish is never left
// with an empty dark frame where its picture should be.
startImageFallbacks();

// The waiter dashboard marks its scan button with `data-waiter-scan` and the
// overlay it opens lives next to it. On the other panels, and on the public
// menu, this call is a no-op.
startWaiterQrScanner();

// The dish detail shows a 3D model with `<model-viewer>` and a "Ver en mi
// espacio" button. The runtime is pulled in only when that page is the one
// being rendered, because it is by far the heaviest dependency of the project
// and no other screen needs it. Elsewhere this call is a no-op.
if (document.querySelector('[data-ar-visor]')) {
    import('./3d/ar-viewer')
        .then(({ startAugmentedReality }) => startAugmentedReality())
        .catch(() => {
            const estado = document.querySelector('[data-ar-estado]');

            if (estado) {
                estado.textContent =
                    'Hubo un problema al cargar el modelo. Intentá nuevamente.';
            }
        });
}

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
