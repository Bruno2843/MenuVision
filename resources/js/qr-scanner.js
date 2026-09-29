import jsQR from 'jsqr';

/**
 * Camera-based QR scanner built on top of getUserMedia and jsQR.
 *
 * Call `start()` to open the camera and scan in a loop until a code is found,
 * then `stop()` to release the camera. The result callback receives the raw
 * string decoded from the QR code.
 */
export default class QRScanner {
    /**
     * @param {HTMLVideoElement} video
     * @param {(value: string) => void} onResult
     */
    constructor(video, onResult) {
        this.video = video;
        this.onResult = onResult;
        this.stream = null;
        this.animationFrame = null;
    }

    /**
     * Open the camera and start scanning.
     *
     * @returns {Promise<void>}
     */
    async start() {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('Cámara no disponible');
        }

        this.stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: false,
        });

        this.video.srcObject = this.stream;
        await this.video.play();

        this.animationFrame = requestAnimationFrame(() => this.loop());
    }

    /**
     * Analyse video frames until a QR code is decoded.
     */
    loop() {
        if (this.video.readyState < this.video.HAVE_ENOUGH_DATA || this.video.videoWidth === 0) {
            this.animationFrame = requestAnimationFrame(() => this.loop());
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = this.video.videoWidth;
        canvas.height = this.video.videoHeight;
        const context = canvas.getContext('2d', { willReadFrequently: true });

        context.drawImage(this.video, 0, 0, canvas.width, canvas.height);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert',
        });

        if (code?.data) {
            this.stop();
            this.onResult(code.data);
            return;
        }

        this.animationFrame = requestAnimationFrame(() => this.loop());
    }

    /**
     * Release the camera and stop the scanning loop.
     */
    stop() {
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }

        this.video.srcObject = null;
    }
}