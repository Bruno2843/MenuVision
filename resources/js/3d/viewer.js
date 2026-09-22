import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

/**
 * Reusable GLB viewer built on Three.js.
 *
 * Mounts a `THREE.WebGLRenderer` in the provided container, loads a `.glb`
 * model and provides orbit rotation/zoom controls.
 */
export default class GLBViewer {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            autoRotate: true,
            backgroundColor: 0xf5f5f5,
            ...options,
        };

        this.model = null;
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.animationId = null;

        this.init();
    }

    init() {
        const width = this.container.clientWidth || 300;
        const height = this.container.clientHeight || 300;

        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(this.options.backgroundColor);

        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        this.camera.position.set(0, 1.5, 3);

        this.renderer = new THREE.WebGLRenderer({ antialias: true });
        this.renderer.setSize(width, height);
        this.container.appendChild(this.renderer.domElement);

        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.autoRotate = this.options.autoRotate;

        this.addLights();

        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.container);

        this.animate();

        window.addEventListener('resize', () => this.resize());
    }

    addLights() {
        const ambient = new THREE.AmbientLight(0xffffff, 0.6);
        this.scene.add(ambient);

        const directional = new THREE.DirectionalLight(0xffffff, 0.8);
        directional.position.set(2, 3, 4);
        this.scene.add(directional);
    }

    /**
     * Load a `.glb` model from the given URL.
     */
    load(url) {
        const loader = new GLTFLoader();

        loader.load(
            url,
            (gltf) => {
                this.model = gltf.scene;
                this.scene.add(this.model);
                this.fitCamera();
            },
            undefined,
            (error) => {
                this.container.dispatchEvent(new CustomEvent('viewer:error', { detail: error }));
            }
        );
    }

    fitCamera() {
        if (!this.model) {
            return;
        }

        const box = new THREE.Box3().setFromObject(this.model);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        const maxSize = Math.max(size.x, size.y, size.z) || 1;
        const distance = maxSize / Math.tan((this.camera.fov * Math.PI) / 360);

        this.camera.position.set(center.x, center.y, center.z + distance * 1.2);
        this.controls.target.copy(center);
        this.controls.update();
    }

    resize() {
        const width = this.container.clientWidth || 300;
        const height = this.container.clientHeight || 300;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    }

    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());

        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }

    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }

        this.resizeObserver?.disconnect();
        window.removeEventListener('resize', () => this.resize());

        this.renderer.dispose();
        this.model?.traverse((child) => {
            if (child.isMesh) {
                child.geometry?.dispose();
                child.material?.dispose?.();
            }
        });

        this.container.innerHTML = '';
    }
}
