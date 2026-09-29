<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { usePrice } from '@/Composables/usePrice';
import FavoriteButton from './FavoriteButton.vue';
import Icon from './Icon.vue';

const props = defineProps({
    product: { type: Object, required: true },
});

const emit = defineEmits(['quick-view']);

const { formatPrice } = usePrice();

const imgLoaded = ref(false);

const name = computed(() => props.product.name_ar || props.product.name);
const coverUrl = computed(() => props.product.cover_url || props.product.cover_thumb_url);

const effectivePrice = computed(() =>
    props.product.effective_price ?? props.product.sale_price ?? props.product.price
);

const originalPrice = computed(() => props.product.price);

const hasDiscount = computed(() =>
    effectivePrice.value && originalPrice.value && effectivePrice.value < originalPrice.value
);

const isAvailable = computed(() => props.product.availability !== 'sold_out');
</script>

<template>
    <article class="card-premium group relative !rounded-2xl overflow-hidden flex flex-col">
        <!-- Image wrapper -->
        <div class="relative aspect-[4/3] overflow-hidden bg-surface-100">
            <Link
                :href="`/products/${product.slug}`"
                class="absolute inset-0"
                :aria-label="`عرض تفاصيل ${name}`"
            >
                <img
                    v-if="coverUrl"
                    :src="coverUrl"
                    :alt="name"
                    loading="lazy"
                    class="img-cover transition-all duration-500 ease-out group-hover:scale-105"
                    :class="imgLoaded ? 'opacity-100' : 'opacity-0'"
                    @load="imgLoaded = true"
                />
                <div v-else class="w-full h-full bg-gradient-warm flex items-center justify-center">
                    <Icon name="info" class="w-10 h-10 text-surface-300" />
                </div>
                <div v-if="coverUrl && !imgLoaded" class="absolute inset-0 bg-surface-200 animate-pulse"></div>
            </Link>

            <!-- Status badges -->
            <div class="absolute top-3 inset-x-3 flex items-start justify-between gap-2">
                <div class="flex flex-wrap gap-1.5">
                    <span
                        v-if="product.discount_pct"
                        class="px-2.5 py-1 rounded-md bg-brand-600 text-white text-xs font-bold shadow-sm"
                    >
                        -{{ product.discount_pct }}%
                    </span>
                    <span
                        v-if="product.is_new"
                        class="px-2.5 py-1 rounded-md bg-surface-950/80 backdrop-blur-sm text-gold-300 text-xs font-medium border border-gold-400/30"
                    >
                        جديد
                    </span>
                </div>

                <!-- Favorite -->
                <FavoriteButton :product-id="product.id" />
            </div>

            <!-- Not available overlay -->
            <div
                v-if="!isAvailable"
                class="absolute inset-0 bg-surface-950/60 backdrop-blur-[2px] flex flex-col items-center justify-center gap-2.5 z-10 p-4 text-center"
            >
                <span class="px-4 py-2 rounded-full bg-white text-surface-900 text-sm font-semibold shadow-premium">
                    نفدت الكمية
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs text-white/90">
                    <Icon name="clock" class="w-3.5 h-3.5 text-gold-300" />
                    يمكن طلبه — التسليم خلال 15 يوماً
                </span>
            </div>

            <!-- Quick actions on hover -->
            <div
                class="absolute bottom-3 inset-x-3 hidden lg:flex flex-col gap-2"
            >
                <div class="flex gap-2 transition-all duration-300 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100">
                    <button
                        type="button"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-white/95 backdrop-blur-sm text-surface-800 text-sm font-medium hover:bg-white transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500"
                        @click="emit('quick-view', product)"
                        :disabled="!isAvailable"
                    >
                        <Icon name="eye" class="w-4 h-4" />
                        {{ isAvailable ? 'عرض سريع' : 'غير متوفر' }}
                    </button>
                    <Link
                        :href="`/products/${product.slug}`"
                        class="flex-none inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-gold text-surface-950 hover:scale-105 transition-transform motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-brand-500"
                        :aria-label="`عرض تفاصيل ${name}`"
                    >
                        <Icon name="arrow-left" class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="flex flex-col flex-1 p-4 sm:p-5">
            <Link
                v-if="product.category"
                :href="`/categories/${product.category.slug}`"
                class="text-[11px] font-medium text-brand-600 hover:text-brand-700 mb-1.5 transition-colors w-fit"
            >
                {{ product.category.name }}
            </Link>

            <Link
                :href="`/products/${product.slug}`"
                class="font-display font-semibold text-surface-900 hover:text-brand-700 transition-colors leading-snug line-clamp-2 mb-2"
            >
                {{ name }}
            </Link>

            <!-- Price -->
            <div class="mt-auto pt-3 flex items-end gap-2 flex-wrap">
                <template v-if="effectivePrice">
                    <span
                        v-if="hasDiscount"
                        class="text-sm text-surface-400 line-through decoration-surface-400/60"
                    >
                        {{ formatPrice(originalPrice, product.currency) }}
                    </span>
                    <span class="text-lg font-bold text-brand-700">
                        {{ formatPrice(effectivePrice, product.currency) }}
                    </span>
                </template>
                <span v-else class="text-sm text-surface-400">السعر حسب الطلب</span>
            </div>

            <!-- Mobile quick view (always visible) -->
            <div class="mt-3 lg:hidden flex gap-2">
                <button
                    type="button"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 rounded-lg bg-surface-100 text-surface-700 text-sm font-medium hover:bg-brand-50 hover:text-brand-700 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                    @click="emit('quick-view', product)"
                >
                    <Icon name="eye" class="w-4 h-4" />
                    عرض سريع
                </button>
                <Link
                    :href="`/products/${product.slug}`"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 rounded-lg bg-brand-600 text-white text-sm font-medium hover:bg-brand-700 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    التفاصيل
                    <Icon name="arrow-left" class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </article>
</template>