<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const props = defineProps({
    offer: { type: Object, required: true },
});

const title = computed(() => props.offer.title_ar || props.offer.title);
const description = computed(() => props.offer.description || '');

const timer = ref({ days: 0, hours: 0, minutes: 0, seconds: 0, expired: false, short: false });
let interval = null;

function computeTimeLeft() {
    const endsAt = props.offer.ends_at;
    if (!endsAt) {
        timer.value = { ...timer.value, hidden: true };
        return;
    }
    const diff = new Date(endsAt).getTime() - Date.now();
    if (diff <= 0) {
        timer.value = { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true, hidden: false };
        return;
    }
    const days = Math.floor(diff / 86400000);
    const hours = Math.floor((diff % 86400000) / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    timer.value = { days, hours, minutes, seconds, expired: false, hidden: false, short: days <= 0 };
}

onMounted(() => {
    computeTimeLeft();
    interval = setInterval(computeTimeLeft, 1000);
});

onUnmounted(() => clearInterval(interval));

const shownTimer = computed(() => {
    const t = timer.value;
    if (t.hidden) return null;
    const parts = [];
    if (t.days > 0) parts.push({ value: String(t.days).padStart(2, '0'), label: 'يوم' });
    parts.push({ value: String(t.hours).padStart(2, '0'), label: 'ساعة' });
    parts.push({ value: String(t.minutes).padStart(2, '0'), label: 'دقيقة' });
    if (t.days <= 0) parts.push({ value: String(t.seconds).padStart(2, '0'), label: 'ثانية' });
    return parts;
});
</script>

<template>
    <Link
        :href="`/offers/${offer.slug}`"
        class="group relative block overflow-hidden rounded-2xl min-h-[320px] bg-surface-900 shadow-premium focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 transition-shadow duration-300 hover:shadow-premium-lg"
    >
        <img
            v-if="offer.image_url"
            :src="offer.image_url"
            :alt="title"
            loading="lazy"
            class="absolute inset-0 w-full h-full object-cover opacity-75 transition-all duration-500 group-hover:opacity-60 group-hover:scale-105"
        />
        <div v-else class="absolute inset-0 bg-gradient-premium"></div>

        <!-- Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-surface-950 via-surface-950/50 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-gold-500/0 via-transparent to-surface-950/30 transition-opacity duration-500 group-hover:from-gold-500/5"></div>

        <!-- Gold accent line at top -->
        <div class="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-l from-gold-500/0 via-gold-500/70 to-gold-500/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500" aria-hidden="true"></div>

        <!-- Badge -->
        <span
            v-if="offer.badge"
            class="absolute top-4 right-4 z-10 px-3.5 py-1.5 rounded-full bg-gradient-gold text-surface-950 text-sm font-bold shadow-gold"
        >
            {{ offer.badge }}
        </span>

        <!-- Content -->
        <div class="absolute inset-x-0 bottom-0 p-6 text-white">
            <h3 class="font-display text-xl sm:text-2xl font-bold mb-2 leading-snug">
                {{ title }}
            </h3>
            <p v-if="description" class="text-sm text-surface-200 leading-relaxed line-clamp-2 mb-4 max-w-md">
                {{ description }}
            </p>

            <!-- Live countdown bar -->
            <div
                v-if="shownTimer"
                class="mb-4"
                :class="{ 'opacity-60': timer.expired }"
            >
                <div class="flex items-center gap-1.5 text-xs text-gold-300 mb-2">
                    <Icon name="clock" class="w-3.5 h-3.5" />
                    <span>{{ timer.expired ? 'العرض المنتهي' : 'ينتهي العرض خلال' }}</span>
                </div>
                <div v-if="timer.expired" class="text-sm font-semibold text-red-300">
                    هذا العرض لم يعد متاحاً
                </div>
                <div v-else class="flex items-center gap-1.5" dir="rtl">
                    <template v-for="(part, i) in shownTimer" :key="part.label">
                        <div
                            v-if="i > 0"
                            class="text-gold-400/60 font-display text-sm px-0.5"
                            aria-hidden="true"
                        >
                            :
                        </div>
                        <div class="flex flex-col items-center">
                            <span
                                class="min-w-[2.5rem] bg-white/10 backdrop-blur-sm rounded-lg px-2 py-1 font-display font-bold text-sm text-white tabular-nums border border-white/10 text-center"
                            >
                                {{ part.value }}
                            </span>
                            <span class="text-[10px] text-surface-400 mt-1">{{ part.label }}</span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-between flex-wrap gap-3 pt-3 border-t border-white/10">
                <span
                    class="inline-flex items-center gap-2 text-gold-300 font-medium text-sm opacity-90 group-hover:gap-3 transition-all"
                >
                    عرض الخصم
                    <Icon name="arrow-left" class="w-4 h-4" />
                </span>
            </div>
        </div>
    </Link>
</template>