/**
 * Boot preloader teardown.
 *
 * The overlay itself ships in the Blade shell (resources/views/app.blade.php)
 * so that it paints on the first frame, well before this bundle arrives.
 * All this module does is take it back down once Vue has mounted.
 */

/**
 * How long the overlay stays up at minimum.
 *
 * On a warm cache the app can mount in well under a tenth of a second, and
 * tearing the overlay down that fast reads as a flicker rather than a load.
 * Holding it briefly makes the hand-off deliberate instead of jarring.
 */
const MIN_VISIBLE_MS = 450;

const FADE_MS = 500;

export function hidePreloader() {
    const el = document.getElementById('app-preloader');

    if (! el || el.classList.contains('is-done')) {
        return;
    }

    const fadeMs = window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ? 0
        : FADE_MS;

    // Mounting only queues the page's first paint. Waiting two frames lets
    // that paint land, so the fade never uncovers a still-blank page.
    requestAnimationFrame(() => requestAnimationFrame(() => {
        // performance.now() counts from the start of the navigation, which
        // is also when the overlay first painted — so it is how long the
        // visitor has actually been looking at it.
        const shownFor = window.performance?.now() ?? MIN_VISIBLE_MS;

        window.setTimeout(() => {
            el.classList.add('is-done');
            document.body.classList.remove('is-booting');
            document.body.removeAttribute('aria-busy');

            // Drop it from the DOM once the fade has played out, so the
            // overlay is not left over the page as an invisible layer.
            window.setTimeout(() => el.remove(), fadeMs);
        }, Math.max(0, MIN_VISIBLE_MS - shownFor));
    }));
}
