<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

defineProps({
    items: { type: Array, required: true },
    /** Rendered inside a dark hero, under the transparent navbar. */
    light: { type: Boolean, default: false },
});
</script>

<template>
    <nav
        aria-label="المسار"
        class="flex items-center gap-2 text-sm mb-6 flex-wrap"
        :class="light ? 'text-surface-300' : 'text-surface-500'"
    >
        <Link
            href="/"
            class="transition-colors"
            :class="light ? 'hover:text-gold-300' : 'hover:text-brand-600'"
        >
            الرئيسية
        </Link>
        <template v-for="(item, index) in items" :key="index">
            <Icon
                name="chevron-left"
                class="w-3.5 h-3.5 rotate-180 shrink-0"
                :class="light ? 'text-surface-400' : 'text-surface-300'"
            />
            <Link
                v-if="item.href"
                :href="item.href"
                class="transition-colors"
                :class="light ? 'hover:text-gold-300' : 'hover:text-brand-600'"
            >
                {{ item.label }}
            </Link>
            <span
                v-else
                class="font-medium"
                :class="light ? 'text-white' : 'text-surface-900'"
                aria-current="page"
            >
                {{ item.label }}
            </span>
        </template>
    </nav>
</template>
