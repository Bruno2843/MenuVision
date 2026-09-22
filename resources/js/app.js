import GLBViewer from './3d/viewer';

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
