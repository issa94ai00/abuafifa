<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const props = defineProps({
    category: { type: Object, required: true },
});

const imageUrl = computed(() => props.category.image_url || props.category.image_thumb);
</script>

<template>
    <Link
        :href="`/categories/${category.slug}`"
        class="group relative block overflow-hidden rounded-2xl aspect-[4/5] bg-surface-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
    >
        <!-- Image -->
        <img
            v-if="imageUrl"
            :src="imageUrl"
            :alt="category.name_ar"
            loading="lazy"
            class="img-cover transition-transform duration-500 ease-out group-hover:scale-105"
        />
        <div v-else class="absolute inset-0 bg-gradient-premium flex items-center justify-center">
            <span class="font-display font-bold text-5xl text-white/25">{{ category.name_ar?.charAt(0) }}</span>
        </div>

        <!-- Gradient overlay (no image quality degradation) -->
        <div class="absolute inset-0 bg-gradient-to-t from-surface-950/90 via-surface-950/20 to-transparent"></div>

        <!-- Content -->
        <div class="absolute inset-x-0 bottom-0 p-5 text-white">
            <h3 class="font-display font-bold text-lg sm:text-xl mb-1">
                {{ category.name_ar }}
            </h3>
            <p v-if="category.products_count" class="text-xs text-surface-200 mb-3">
                {{ category.products_count }} منتج
            </p>
            <span
                class="inline-flex items-center gap-1.5 text-sm font-medium text-gold-300 opacity-0 -translate-x-3 transition-all duration-300 group-hover:opacity-100 group-hover:translate-x-0"
            >
                اكتشف التصنيف
                <Icon name="arrow-left" class="w-4 h-4" />
            </span>
        </div>
    </Link>
</template>