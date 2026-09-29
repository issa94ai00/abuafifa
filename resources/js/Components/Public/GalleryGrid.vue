<script setup>
import { ref } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    images: { type: Array, required: true },
    loading: { type: Boolean, default: false },
});

const activeIndex = ref(null);

const activeImage = ref(null);

function openImage(index) {
    activeIndex.value = index;
    activeImage.value = props.images[index] || null;
}

function closeLightbox() {
    activeIndex.value = null;
    activeImage.value = null;
}

function step(direction) {
    if (activeIndex.value === null) return;
    const next = (activeIndex.value + direction + props.images.length) % props.images.length;
    openImage(next);
}

function onKeydown(event) {
    if (event.key === 'Escape') closeLightbox();
    if (event.key === 'ArrowRight') step(1);
    if (event.key === 'ArrowLeft') step(-1);
}
</script>

<template>
    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 gap-4 h-[400px]">
        <div v-for="i in 6" :key="i" class="rounded-xl bg-surface-200 animate-pulse"></div>
    </div>

    <div v-else-if="images.length" class="columns-2 md:columns-3 gap-4 space-y-4">
        <button
            v-for="(image, index) in images"
            :key="image.id ?? index"
            type="button"
            class="group relative block w-full mb-4 break-inside-avoid rounded-xl overflow-hidden bg-surface-100 shadow-premium focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
            :aria-label="image.caption || `صورة ${index + 1} من معرض أبو عفيفة`"
            @click="openImage(index)"
        >
            <img
                v-if="image.url"
                :src="image.url"
                :alt="image.caption || 'صورة من معرض أبو عفيفة'"
                :width="image.width"
                :height="image.height"
                loading="lazy"
                decoding="async"
                class="w-full object-cover transition-transform duration-500 group-hover:scale-105"
                :class="[index % 3 === 0 ? 'aspect-[3/4]' : index % 3 === 1 ? 'aspect-square' : 'aspect-[4/5]']"
            />
            <!-- Hover overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-surface-950/70 via-surface-950/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

            <!-- Zoom cue -->
            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 scale-90 group-hover:scale-100">
                <span class="w-12 h-12 rounded-full bg-white/15 backdrop-blur-sm border border-white/25 flex items-center justify-center text-white">
                    <Icon name="search" class="w-5 h-5" />
                </span>
            </div>

            <!-- Caption -->
            <div
                v-if="image.caption"
                class="absolute inset-x-0 bottom-0 p-4 text-white text-sm leading-relaxed opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300"
            >
                {{ image.caption }}
            </div>

            <!-- Corner index -->
            <span class="absolute top-3 left-3 px-2 py-0.5 rounded-md bg-surface-950/50 backdrop-blur-sm text-white text-[11px] font-medium tabular-nums opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                {{ index + 1 }} / {{ images.length }}
            </span>
        </button>
    </div>

    <!-- Lightbox -->
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="activeImage"
            class="fixed inset-0 z-50 bg-surface-950/95 backdrop-blur-sm flex items-center justify-center p-4 select-none"
            role="dialog"
            aria-modal="true"
            @click.self="closeLightbox"
            @keydown="onKeydown"
            tabindex="-1"
        >
            <!-- Close -->
            <button
                type="button"
                class="absolute top-5 right-5 p-2.5 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-400"
                aria-label="إغلاق الصورة"
                @click="closeLightbox"
            >
                <Icon name="close" />
            </button>

            <!-- Prev / Next -->
            <!-- Reorder button order for RTL: "previous" visually on the left, "next" on the right -->
            <button
                v-if="images.length > 1"
                type="button"
                class="absolute left-4 sm:left-8 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-400"
                aria-label="الصورة التالية"
                @click="step(1)"
                @click.stop
            >
                <Icon name="chevron-left" class="w-5 h-5 rotate-180" />
            </button>
            <button
                v-if="images.length > 1"
                type="button"
                class="absolute right-4 sm:right-8 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-400"
                aria-label="الصورة السابقة"
                @click="step(-1)"
                @click.stop
            >
                <Icon name="chevron-left" class="w-5 h-5" />
            </button>

            <figure class="max-h-[90vh] max-w-[90vw] flex flex-col items-center">
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="hidden"
                >
                    <img
                        :key="activeImage.url"
                        :src="activeImage.url"
                        :alt="activeImage.caption || 'صورة من معرض أبو عفيفة'"
                        class="max-h-[78vh] max-w-[90vw] object-contain rounded-xl shadow-premium-lg"
                    />
                </Transition>
                <figcaption class="mt-4 flex flex-col items-center gap-1.5 text-center px-4">
                    <span
                        v-if="images.length > 1"
                        class="px-2.5 py-0.5 rounded-full bg-white/10 text-xs text-surface-300 tabular-nums"
                    >
                        {{ (activeIndex ?? 0) + 1 }} / {{ images.length }}
                    </span>
                    <span v-if="activeImage.caption" class="text-white text-sm leading-relaxed max-w-xl">
                        {{ activeImage.caption }}
                    </span>
                </figcaption>
            </figure>
        </div>
    </Transition>
</template>