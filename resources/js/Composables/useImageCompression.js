/**
 * Shrink an image in the browser before it is uploaded.
 *
 * MediaService already scales every upload down to fit 1920x1080 and
 * re-encodes it, so anything bigger than that is bandwidth the server
 * throws away. Doing the same resize here means a 12MB phone photo
 * leaves as ~600KB and arrives as the exact same stored file — the admin
 * just stops waiting on the upload, and stops being told their photo is
 * too large when it did not have to be.
 *
 * Quality is deliberately set ABOVE what the server encodes at (JPEG 88,
 * WebP 82), so this pass is never the binding constraint on how the
 * stored image looks. The server's encode stays the one that decides.
 */

/** The server's own ceiling — see MediaService::write(). */
export const MAX_WIDTH = 1920;
export const MAX_HEIGHT = 1080;

/** Above the server's encode quality, so it stays the limiting step. */
const QUALITY = 0.92;

/** Below this, the upload is already quick; leave the bytes alone. */
const SIZE_FLOOR_BYTES = 1024 * 1024;

/**
 * Canvas can decode AVIF but cannot encode it, so an AVIF in would come
 * back out as something else. They are small already — leave them be.
 */
const COMPRESSIBLE = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Decode to a bitmap with EXIF rotation already applied.
 *
 * This matters: the server strips EXIF on the way in, so if the rotation
 * were left in the metadata rather than baked into the pixels, a portrait
 * phone photo would be stored on its side.
 */
async function decode(file) {
    if (typeof createImageBitmap === 'function') {
        try {
            return await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch {
            // Older Safari rejects the options bag; fall through to <img>.
        }
    }

    return await new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();

        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve(img);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('decode failed'));
        };

        // Browsers apply EXIF orientation to <img> by default.
        img.src = url;
    });
}

function targetSize(width, height) {
    const scale = Math.min(MAX_WIDTH / width, MAX_HEIGHT / height, 1);

    return {
        width: Math.max(1, Math.round(width * scale)),
        height: Math.max(1, Math.round(height * scale)),
        scaled: scale < 1,
    };
}

function toBlob(canvas, type, quality) {
    return new Promise((resolve) => {
        // PNG ignores the quality argument; it is lossless either way.
        canvas.toBlob((blob) => resolve(blob), type, quality);
    });
}

/**
 * Returns a compressed File, or the original when compressing it would
 * not help. Never throws — a failure here should not block an upload.
 *
 * @param {File} file
 * @returns {Promise<File>}
 */
export async function compressImage(file) {
    if (!(file instanceof File) || !COMPRESSIBLE.includes(file.type)) {
        return file;
    }

    let bitmap = null;

    try {
        bitmap = await decode(file);

        const width = bitmap.width || bitmap.naturalWidth;
        const height = bitmap.height || bitmap.naturalHeight;

        if (!width || !height) return file;

        const target = targetSize(width, height);

        // Already within the server's box and already small: re-encoding
        // would only throw away quality for nothing.
        if (!target.scaled && file.size <= SIZE_FLOOR_BYTES) {
            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = target.width;
        canvas.height = target.height;

        const context = canvas.getContext('2d');
        if (!context) return file;

        // A white floor for formats without alpha, so a transparent PNG
        // re-encoded as JPEG cannot come out with a black background.
        if (file.type === 'image/jpeg') {
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, target.width, target.height);
        }

        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';
        context.drawImage(bitmap, 0, 0, target.width, target.height);

        const blob = await toBlob(canvas, file.type, QUALITY);

        // Free the backing store now rather than at the next GC; a batch
        // of 25 phone photos is a lot of raster to hold at once.
        canvas.width = 0;
        canvas.height = 0;

        if (!blob || blob.size >= file.size) {
            return file;
        }

        return new File([blob], file.name, {
            type: blob.type || file.type,
            lastModified: file.lastModified,
        });
    } catch {
        return file;
    } finally {
        bitmap?.close?.();
    }
}

/**
 * Compress a batch one at a time.
 *
 * Sequential on purpose: 25 files decoded in parallel is 25 full-size
 * rasters resident at once, which is how a tab runs out of memory.
 *
 * @param {File[]} files
 * @param {(done: number, total: number) => void} [onProgress]
 * @returns {Promise<File[]>}
 */
export async function compressImages(files, onProgress) {
    const list = Array.from(files ?? []);
    const out = [];

    for (const [index, file] of list.entries()) {
        out.push(await compressImage(file));
        onProgress?.(index + 1, list.length);
    }

    return out;
}

export function useImageCompression() {
    return { compressImage, compressImages, MAX_WIDTH, MAX_HEIGHT };
}
