import { ref, onMounted, onUnmounted } from 'vue';

export function useInView(options = {}) {
    const target = ref(null);
    const isInView = ref(false);
    const hasAnimated = ref(false);

    let observer = null;

    const defaultOptions = {
        threshold: 0.15,
        rootMargin: '0px 0px -60px 0px',
        once: true,
        ...options,
    };

    onMounted(() => {
        if (!target.value) return;

        // Without observer support there is nothing to reveal *on*, so show
        // the content rather than leaving it stuck at opacity-0.
        if (typeof IntersectionObserver === 'undefined') {
            isInView.value = true;
            hasAnimated.value = true;
            return;
        }

        observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                isInView.value = true;
                if (defaultOptions.once) {
                    hasAnimated.value = true;
                    observer.unobserve(entry.target);
                }
            } else if (!defaultOptions.once) {
                isInView.value = false;
            }
        }, {
            threshold: defaultOptions.threshold,
            rootMargin: defaultOptions.rootMargin,
        });

        observer.observe(target.value);
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
        }
    });

    return { target, isInView, hasAnimated };
}
