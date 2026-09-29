<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Icon from './Icon.vue';

const show = ref(false);

function onScroll() {
    show.value = window.scrollY > 500;
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onUnmounted(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 scale-90"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0 scale-90"
    >
        <button
            v-if="show"
            type="button"
            class="fixed bottom-6 right-6 z-40 w-11 h-11 rounded-full bg-surface-900/90 text-white shadow-premium flex items-center justify-center hover:bg-brand-600 hover:scale-105 transition-all duration-300 backdrop-blur-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
            aria-label="العودة إلى الأعلى"
            @click="scrollToTop"
        >
            <Icon name="chevron-up" class="w-5 h-5" />
        </button>
    </Transition>
</template>