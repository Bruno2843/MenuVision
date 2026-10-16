/**
 * Augmented reality for the public dish detail.
 *
 * The runtime is imported here and only here, so `<model-viewer>` arrives as a
 * separate chunk that is downloaded on the dish page and nowhere else. The
 * import points at the prebuilt bundle on purpose: it ships its own copy of
 * three.js, so it never depends on the version the app has installed. The
 * `lib/` entry instead re-exports a few symbols from the host's three and its
 * peer range does not accept the three version this project is on.
 *
 * `<model-viewer>` is what provides the real AR, trying its modes in the order
 * given by `ar-modes` on the element:
 *
 *   - webxr        Android Chrome with ARCore: stays in the browser and needs
 *                  the page to be served over HTTPS.
 *   - scene-viewer Android without WebXR: hands over to the Scene Viewer app.
 *   - quick-look   iPhone and iPad: opens AR Quick Look, generating the USDZ
 *                  from the same `.glb` on the fly, so no second file is needed.
 *   - fallback     a plain 3D view when the device offers no AR at all.
 *
 * Nothing here starts a session on its own. The camera is only ever requested
 * from the click on the "Ver en mi espacio" button, and every state is reported
 * in the page instead of through an alert, so the detail is never left blocked.
 */
import '@google/model-viewer/dist/model-viewer.min.js';

const MENSAJES = {
    cargando: 'Cargando modelo 3D…',
    preparando: 'Preparando realidad aumentada…',
    sinAr: 'Tu dispositivo no admite esta experiencia AR. Podés seguir viendo el modelo en 3D.',
    error: 'Hubo un problema al cargar el modelo. Intentá nuevamente.',
};

/**
 * Wire the "Ver en mi espacio" button to the `<model-viewer>` element.
 *
 * A no-op on any page without those elements, which is every page but the
 * public dish detail.
 */
export const startAugmentedReality = () => {
    const visor = document.querySelector('[data-ar-visor]');
    const boton = document.querySelector('[data-ar-boton]');
    const estado = document.querySelector('[data-ar-estado]');

    if (!visor || !boton) {
        return;
    }

    const mostrar = (texto) => {
        if (estado && estado.textContent !== texto) {
            estado.textContent = texto;
        }
    };

    // The model is still coming in, so AR cannot start yet.
    mostrar(MENSAJES.cargando);

    visor.addEventListener('load', () => {
        // `canActivateAR` is only meaningful once a model is loaded.
        boton.disabled = false;
        mostrar('');
    });

    visor.addEventListener('error', () => {
        // Nothing to place in the room, so the button stays disabled instead of
        // offering an experience that cannot start.
        boton.disabled = true;
        mostrar(MENSAJES.error);
    });

    visor.addEventListener('ar-status', (event) => {
        const status = event.detail?.status;

        if (status === 'failed') {
            // Every mode in `ar-modes` was refused by this device.
            boton.disabled = false;
            mostrar(MENSAJES.sinAr);
        }

        if (status === 'not-presenting') {
            // The session ended, either by the user or by the app, so the detail
            // is interactive again and the button is ready for another try.
            boton.disabled = false;
            mostrar('');
        }
    });

    boton.addEventListener('click', () => {
        if (boton.disabled) {
            return;
        }

        mostrar(MENSAJES.preparando);

        // Called synchronously from the click: scene-viewer and quick-look open
        // another app, and both ignore the request when it loses the user
        // gesture. The session then reports itself through `ar-status`.
        try {
            const inicio = visor.activateAR();

            inicio?.catch?.(() => {
                boton.disabled = false;
                mostrar(MENSAJES.sinAr);
            });
        } catch (error) {
            boton.disabled = false;
            mostrar(MENSAJES.sinAr);
        }
    });
};
