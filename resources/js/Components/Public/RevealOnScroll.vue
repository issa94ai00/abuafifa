<template>
    <div
        ref="target"
        class="transition-all duration-700 ease-out will-change-transform motion-reduce:transition-none"
        :class="classObject"
        :style="{ transitionDelay: shouldAnimate ? `${delay}ms` : '0ms' }"
    >
        <slot />
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useInView } from '@/Composables/useInView';

const props = defineProps({
    animation: {
        type: String,
        default: 'fade-in-up',
        validator: (v) => ['fade-in', 'fade-in-up', 'fade-in-down', 'slide-in-right', 'slide-in-left', 'scale-in'].includes(v),
    },
    delay: { type: Number, default: 0 },
});

const { target, isInView } = useInView();

const baseClasses = {
    'fade-in': 'opacity-0',
    'fade-in-up': 'opacity-0 translate-y-8',
    'fade-in-down': 'opacity-0 -translate-y-8',
    'slide-in-right': 'opacity-0 translate-x-8',
    'slide-in-left': 'opacity-0 -translate-x-8',
    'scale-in': 'opacity-0 scale-95',
};

const visibleClasses = {
    'fade-in': 'opacity-100',
    'fade-in-up': 'opacity-100 translate-y-0',
    'fade-in-down': 'opacity-100 translate-y-0',
    'slide-in-right': 'opacity-100 translate-x-0',
    'slide-in-left': 'opacity-100 translate-x-0',
    'scale-in': 'opacity-100 scale-100',
};

/**
 * Reveal-on-scroll is decoration, so someone who asked for reduced motion
 * gets the content outright — no starting offset, no stagger. Matching what
 * the hero and the nav already do.
 */
const prefersReducedMotion = ref(false);
let motionQuery = null;

function syncMotionPreference(event) {
    prefersReducedMotion.value = event.matches;
}

onMounted(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return;

    motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    prefersReducedMotion.value = motionQuery.matches;
    motionQuery.addEventListener('change', syncMotionPreference);
});

onUnmounted(() => {
    motionQuery?.removeEventListener('change', syncMotionPreference);
});

const shouldAnimate = computed(() => !prefersReducedMotion.value);

const classObject = computed(() => [
    isInView.value || prefersReducedMotion.value
        ? visibleClasses[props.animation]
        : baseClasses[props.animation],
]);
</script>
