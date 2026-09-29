<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    paginator: { type: Object, required: true },
});

const links = computed(() => {
    const raw = props.paginator.links || [];
    return raw.filter(l => l.url !== null);
});
</script>

<template>
    <nav v-if="links.length > 1" class="flex items-center justify-center gap-1.5" aria-label="التنقل بين الصفحات">
        <Link
            v-for="(link, i) in links"
            :key="i"
            :href="link.url"
            class="min-w-[40px] h-10 inline-flex items-center justify-center rounded-xl text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
            :class="[
                link.active
                    ? 'bg-brand-600 text-white shadow-sm'
                    : 'text-surface-700 hover:bg-surface-100',
            ]"
            :aria-label="`صفحة ${link.label.replace(/&[^;]+;/g, '')}`"
            :aria-current="link.active ? 'page' : undefined"
            preserve-scroll
        >
            <span v-html="link.label" />
        </Link>
    </nav>
</template>
