<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { usePrice } from '@/Composables/usePrice';
import FavoriteButton from './FavoriteButton.vue';
import Icon from './Icon.vue';

const props = defineProps({
    product: { type: Object, required: true },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const page = usePage();
const { formatPrice } = usePrice();

const name = computed(() => props.product.name_ar || props.product.name);
const coverUrl = computed(() => props.product.cover_url || props.product.cover_thumb_url);

const effectivePrice = computed(() =>
    props.product.effective_price ?? props.product.sale_price ?? props.product.price
);

const hasDiscount = computed(() =>
    effectivePrice.value && props.product.price && effectivePrice.value < props.product.price
);

const isAvailable = computed(() => props.product.availability !== 'sold_out');

const whatsappLink = computed(() => {
    const number = String(page.props.settings?.whatsapp || '').replace(/\D/g, '');
    const text = encodeURIComponent(`مرحباً، أودّ الاستفسار عن منتج "${name.value}" من معرض أبو عفيفة للمفروشات.`);
    return number ? `https://wa.me/${number}?text=${text}` : '/contact';
});

const specs = computed(() => {
    const list = [];
    if (props.product.sku) list.push({ label: 'رمز المنتج', value: props.product.sku });
    if (props.product.material) list.push({ label: 'الخامة', value: props.product.material });
    if (props.product.dimensions) list.push({ label: 'الأبعاد', value: props.product.dimensions });
    if (props.product.color) list.push({ label: 'اللون', value: props.product.color });
    return list;
});

function close() {
    emit('close');
}

function handleEscape(e) {
    if (e.key === 'Escape') close();
}

watch(() => props.open, (open) => {
    if (open) {
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleEscape);
    } else {
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleEscape);
    }
});

onMounted(() => {
    if (props.open) {
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleEscape);
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" :aria-label="`عرض سريع - ${name}`">
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-surface-950/70 backdrop-blur-sm" @click="close"></div>

            <div
                class="relative bg-white rounded-2xl shadow-premium-lg w-full max-w-3xl overflow-hidden animate-scale-in"
                style="animation-duration: 0.25s"
            >
                <button
                    type="button"
                    class="absolute top-4 left-4 z-20 p-2.5 rounded-full bg-white/90 shadow-premium text-surface-600 hover:text-surface-900 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500"
                    aria-label="إغلاق"
                    @click="close"
                >
                    <Icon name="close" />
                </button>

                <div class="grid sm:grid-cols-2">
                    <!-- Image -->
                    <div class="relative aspect-[4/3] sm:aspect-auto sm:min-h-[380px] bg-surface-100">
                        <img
                            v-if="coverUrl"
                            :src="coverUrl"
                            :alt="name"
                            class="absolute inset-0 w-full h-full object-cover"
                        />
                        <div v-else class="absolute inset-0 bg-gradient-warm flex items-center justify-center">
                            <Icon name="info" class="w-12 h-12 text-surface-300" />
                        </div>

                        <span
                            v-if="product.discount_pct"
                            class="absolute top-4 right-4 px-2.5 py-1 rounded-md bg-brand-600 text-white text-sm font-bold"
                        >
                            -{{ product.discount_pct }}%
                        </span>
                        <span
                            v-if="product.is_new"
                            class="absolute top-4 left-4 px-2.5 py-1 rounded-md bg-surface-950/80 text-gold-300 text-xs font-medium border border-gold-400/30 backdrop-blur-sm"
                        >
                            جديد
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="p-6 sm:p-8 flex flex-col">
                        <p
                            v-if="product.category"
                            class="text-xs font-medium text-brand-600 mb-2"
                        >
                            {{ product.category.name }}
                        </p>
                        <h3 class="font-display text-xl sm:text-2xl font-bold text-surface-900 leading-snug mb-3">
                            {{ name }}
                        </h3>

                        <p
                            v-if="product.short_description"
                            class="text-sm text-surface-500 leading-relaxed line-clamp-3 mb-4"
                        >
                            {{ product.short_description }}
                        </p>

                        <!-- Availability -->
                        <div class="flex items-center gap-2 mb-4">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium"
                                :class="isAvailable ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="isAvailable ? 'bg-emerald-500' : 'bg-red-500'"
                                ></span>
                                {{ isAvailable ? 'متوفر' : 'نفدت الكمية' }}
                            </span>
                        </div>

                        <!-- Out-of-stock lead time note -->
                        <p v-if="!isAvailable" class="flex items-start gap-2 text-xs text-surface-500 mb-4">
                            <Icon name="truck" class="w-4 h-4 shrink-0 text-brand-600 mt-0.5" />
                            القطعة متاحة للطلب المسبق — التسليم خلال 15 يوماً.
                        </p>

                        <!-- Price -->
                        <div class="flex items-end gap-2 flex-wrap mb-4">
                            <span
                                v-if="hasDiscount"
                                class="text-base text-surface-400 line-through"
                            >
                                {{ formatPrice(product.price, product.currency) }}
                            </span>
                            <span class="text-2xl font-bold text-brand-700">
                                {{ formatPrice(effectivePrice, product.currency) }}
                            </span>
                        </div>

                        <!-- Quick specs -->
                        <div v-if="specs.length" class="grid grid-cols-2 gap-2 mb-5">
                            <div v-for="spec in specs" :key="spec.label" class="bg-surface-50 rounded-lg px-3 py-2">
                                <p class="text-[10px] font-semibold text-surface-400 mb-0.5">{{ spec.label }}</p>
                                <p class="text-xs font-medium text-surface-800 line-clamp-1">{{ spec.value }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-auto space-y-2.5">
                            <div class="flex gap-2">
                                <FavoriteButton :product-id="product.id" variant="ghost" class="!bg-surface-100" />
                                <Link
                                    :href="`/products/${product.slug}`"
                                    class="flex-1 btn-primary !px-4 !py-3 text-sm"
                                    @click="close"
                                >
                                    عرض التفاصيل
                                </Link>
                            </div>
                            <a
                                :href="whatsappLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center justify-center gap-2 bg-[#25D366] text-white !py-3 text-sm w-full rounded-xl font-medium transition-all duration-300 hover:bg-[#1fb858] hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2"
                                @click="close"
                            >
                                <Icon name="whatsapp" class="w-4 h-4" />
                                اطلب عبر واتساب
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>