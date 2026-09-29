<template>
    <div class="mb-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <span
                    v-if="eyebrow"
                    class="inline-flex items-center gap-2 text-brand-600 text-sm font-medium mb-2"
                    :class="{ 'text-gold-400': dark }"
                >
                    <span class="h-px w-8 bg-gradient-gold"></span>
                    {{ eyebrow }}
                </span>
                <h2
                    class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-surface-900"
                    :class="{ 'text-white': dark }"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="subtitle"
                    class="mt-3 text-surface-500 max-w-2xl leading-relaxed"
                    :class="{ 'text-surface-300': dark }"
                >
                    {{ subtitle }}
                </p>
            </div>

            <Link
                v-if="linkText"
                :href="linkHref"
                class="hidden sm:inline-flex items-center gap-2 font-medium text-sm transition-colors group focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 rounded-md"
                :class="dark ? 'text-gold-400 hover:text-gold-300' : 'text-brand-600 hover:text-brand-700'"
            >
                <span>{{ linkText }}</span>
                <Icon name="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform motion-reduce:transition-none" />
            </Link>
        </div>

        <!--
            The same destination on small screens. It sits under the heading
            rather than in the row above, where it would squeeze the title.
        -->
        <Link
            v-if="linkText"
            :href="linkHref"
            class="sm:hidden mt-4 inline-flex items-center gap-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 rounded-md"
            :class="dark ? 'text-gold-400 hover:text-gold-300' : 'text-brand-600 hover:text-brand-700'"
        >
            <span>{{ linkText }}</span>
            <Icon name="arrow-left" class="w-4 h-4" />
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    linkText: { type: String, default: '' },
    linkHref: { type: String, default: '#' },
    dark: { type: Boolean, default: false },
});
</script>
