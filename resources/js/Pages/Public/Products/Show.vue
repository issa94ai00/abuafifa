<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import Breadcrumbs from '@/Components/Public/Breadcrumbs.vue';
import ProductGrid from '@/Components/Public/ProductGrid.vue';
import FavoriteButton from '@/Components/Public/FavoriteButton.vue';
import QuickViewModal from '@/Components/Public/QuickViewModal.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';
import { usePrice } from '@/Composables/usePrice';

const props = defineProps({
    product: { type: Object, required: true },
    product_full: { type: Object, default: () => ({}) },
    related: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
});

const page = usePage();
const { formatPrice } = usePrice();

const name = computed(() => props.product.name_ar || props.product.name);
const coverUrl = computed(() => props.product.cover_url || props.product.cover_thumb_url);

const quickViewProduct = ref(null);
const isQuickViewOpen = ref(false);

const media = computed(() => {
    const list = [];
    if (coverUrl.value) {
        list.push({ id: 'cover', url: coverUrl.value, thumb: props.product.cover_thumb_url || coverUrl.value, alt: name.value });
    }
    (props.product_full?.gallery || []).forEach((img) => {
        if (img.thumb && !list.some((i) => i.url === img.url)) {
            list.push({ id: img.id, url: img.url, thumb: img.thumb, alt: img.alt || name.value });
        }
    });
    return list;
});

const activeMedia = ref(media.value[0] || null);

watch(media, (list) => {
    if (!activeMedia.value || !list.some((i) => i.id === activeMedia.value.id)) {
        activeMedia.value = list[0] || null;
    }
});

const settings = computed(() => page.props.settings || {});

const whatsappLink = computed(() => {
    const number = String(settings.value.whatsapp || '').replace(/\D/g, '');
    const text = encodeURIComponent(`مرحباً، أودّ الاستفسار عن منتج "${name.value}"${props.product.sku ? ` (رمز ${props.product.sku})` : ''} من معرض أبو عفيفة للمفروشات.`);
    return number ? `https://wa.me/${number}?text=${text}` : '/contact';
});

const availability = computed(() => props.product_full?.availability || props.product.availability_label || 'متوفر');
const isAvailable = computed(() => props.product.availability !== 'sold_out');

const hasDiscount = computed(() => {
    const p = props.product.price;
    const e = props.product.effective_price;
    return !!e && e < p;
});
const savings = computed(() => {
    if (!hasDiscount.value) return 0;
    return Math.max(0, (props.product.price || 0) - props.product.effective_price);
});

const specs = computed(() => {
    const list = [];
    if (props.product_full?.sku) list.push({ label: 'رمز المنتج', value: props.product_full.sku, icon: 'tag' });
    if (props.product_full?.material) list.push({ label: 'الخامة', value: props.product_full.material, icon: 'shield' });
    if (props.product_full?.dimensions) list.push({ label: 'الأبعاد', value: props.product_full.dimensions, icon: 'info' });
    return list;
});

const colors = computed(() =>
    (props.product_full?.color || '')
        .split(/[,\/؛]/)
        .map((c) => c.trim())
        .filter(Boolean)
);

const colorSwatchClasses = {
    أبيض: 'bg-white',
    أسود: 'bg-surface-950',
    بني: 'bg-[#7c5a3a]',
    'بني داكن': 'bg-[#5a3f28]',
    خشبي: 'bg-[#a9744f]',
    رمادي: 'bg-surface-400',
    بيج: 'bg-[#d6c7a9]',
    كريمي: 'bg-[#efe6d2]',
    أخضر: 'bg-emerald-600',
    أزرق: 'bg-blue-600',
    ذهبي: 'bg-gold-500',
    وردي: 'bg-pink-300',
    أزرق: 'bg-blue-500',
};

function colorSwatch(color) {
    const exact = colorSwatchClasses[color];
    if (exact) return exact;
    const partial = Object.keys(colorSwatchClasses).find((k) => color.includes(k) && color !== k);
    return (partial && colorSwatchClasses[partial]) || 'bg-gradient-to-br from-surface-300 to-surface-400';
}

const perks = [
    { icon: 'truck', title: 'توصيل آمن', description: 'حتى باب منزلك' },
    { icon: 'shield', title: 'جودة مضمونة', description: 'خامات مختارة بعناية' },
    { icon: 'clock', title: 'خدمة سريعة', description: 'استجابة فورية لاستفساراتك' },
];

// ---------------------------------------------------------------- lightbox
const isLightboxOpen = ref(false);
const lightboxIndex = ref(0);
const activeImage = computed(() => media.value[lightboxIndex.value] || null);

function openLightbox() {
    if (!media.value.length) return;
    lightboxIndex.value = media.value.findIndex((i) => i.id === activeMedia.value?.id);
    if (lightboxIndex.value < 0) lightboxIndex.value = 0;
    isLightboxOpen.value = true;
}

function closeLightbox() {
    isLightboxOpen.value = false;
}

function step(dir) {
    if (!media.value.length) return;
    lightboxIndex.value = (lightboxIndex.value + dir + media.value.length) % media.value.length;
}

function handleKeydown(e) {
    if (!isLightboxOpen.value) return;
    if (e.key === 'Escape') closeLightbox();
    else if (e.key === 'ArrowLeft') step(1);
    else if (e.key === 'ArrowRight') step(-1);
}

watch(isLightboxOpen, (open) => {
    if (open) {
        document.addEventListener('keydown', handleKeydown);
        document.body.style.overflow = 'hidden';
    } else {
        document.removeEventListener('keydown', handleKeydown);
        document.body.style.overflow = '';
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Dark band: gives the see-through navbar something to sit on, the
             way every other page's hero does. -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <img
                v-if="activeMedia"
                :src="activeMedia.url"
                :alt="name"
                aria-hidden="true"
                class="absolute inset-0 w-full h-full object-cover opacity-15"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-surface-950 via-surface-950/80 to-surface-950/40"></div>

            <!-- Top accent line + floating dots -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/30 to-transparent"></div>
            <div class="absolute top-24 right-[8%] w-1.5 h-1.5 rounded-full bg-gold-400/35 animate-pulse"></div>
            <div class="absolute top-32 left-[14%] w-2 h-2 rounded-full bg-gold-500/20 animate-pulse" style="animation-delay: 1s"></div>

            <div class="section-container pt-28 pb-4 sm:pt-36 sm:pb-6 relative">
                <Breadcrumbs
                    light
                    :items="[
                        { label: 'الرئيسية', href: '/' },
                        { label: product.category?.name || 'المنتجات', href: product.category ? `/categories/${product.category.slug}` : '/products' },
                        { label: name },
                    ]"
                />
            </div>
        </section>

        <div class="section-container py-10 sm:py-14">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-start">
                <!-- Gallery -->
                <div>
                    <div class="relative rounded-3xl overflow-hidden bg-surface-100 aspect-[4/3] shadow-premium group">
                        <img
                            v-if="activeMedia"
                            :src="activeMedia.url"
                            :alt="activeMedia.alt || name"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
                            :key="activeMedia.id"
                        />
                        <div v-else class="w-full h-full bg-gradient-warm flex items-center justify-center">
                            <Icon name="info" class="w-12 h-12 text-surface-300" />
                        </div>

                        <!-- Click-to-enlarge -->
                        <button
                            v-if="media.length"
                            type="button"
                            class="absolute inset-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-gold-400"
                            :aria-label="`تكبير الصورة ${activeImage?.alt || ''}`"
                            @click="openLightbox"
                        ></button>

                        <!-- Zoom cue -->
                        <div v-if="media.length" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 scale-90 group-hover:scale-100 pointer-events-none">
                            <span class="w-12 h-12 rounded-full bg-white/15 backdrop-blur-sm border border-white/25 flex items-center justify-center text-white">
                                <Icon name="search" class="w-5 h-5" />
                            </span>
                        </div>

                        <span
                            v-if="product.discount_pct && product.availability !== 'sold_out'"
                            class="absolute top-4 right-4 px-3 py-1.5 rounded-lg bg-brand-600 text-white text-sm font-bold shadow-sm"
                        >
                            -{{ product.discount_pct }}%
                        </span>
                        <span
                            v-if="product.is_new"
                            class="absolute top-4 left-4 px-3 py-1.5 rounded-lg bg-surface-950/80 text-gold-300 text-xs font-medium border border-gold-400/30 backdrop-blur-sm"
                        >
                            جديد
                        </span>

                        <!-- Counter pill -->
                        <span
                            v-if="media.length > 1"
                            class="absolute bottom-4 left-4 px-2.5 py-1 rounded-md bg-surface-950/60 backdrop-blur-sm text-white text-xs font-medium tabular-nums"
                        >
                            {{ media.findIndex((i) => i.id === activeMedia?.id) + 1 }} / {{ media.length }}
                        </span>
                    </div>

                    <!-- Thumbnails -->
                    <div v-if="media.length > 1" class="flex gap-3 mt-4 overflow-x-auto pb-2" dir="rtl">
                        <button
                            v-for="(image, index) in media"
                            :key="image.id"
                            type="button"
                            class="group relative w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden shrink-0 border-2 transition-all focus:outline-none"
                            :class="activeMedia && activeMedia.id === image.id
                                ? 'border-brand-500 ring-2 ring-brand-500/20'
                                : 'border-transparent hover:border-surface-300'"
                            :aria-label="`عرض الصورة ${index + 1}: ${image.alt || ''}`"
                            @click="activeMedia = image"
                        >
                            <img
                                :src="image.thumb"
                                :alt="image.alt || name"
                                loading="lazy"
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <span
                                v-if="activeMedia && activeMedia.id === image.id"
                                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-brand-600 flex items-center justify-center text-white"
                            >
                                <Icon name="check" class="w-3 h-3" />
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Details -->
                <div class="lg:sticky lg:top-24">
                    <!-- Category eyebrow -->
                    <Link
                        v-if="product.category"
                        :href="`/categories/${product.category.slug}`"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors group mb-3"
                    >
                        <span>{{ product.category.name }}</span>
                        <span class="h-px w-6 bg-gradient-gold"></span>
                        <Icon name="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform" />
                    </Link>

                    <h1 class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-surface-900 leading-snug mb-4">
                        <span class="bg-gradient-to-b from-surface-900 via-surface-900 to-surface-600 bg-clip-text text-transparent">{{ name }}</span>
                    </h1>

                    <p
                        v-if="product.short_description"
                        class="text-surface-600 leading-relaxed mb-6"
                    >
                        {{ product.short_description }}
                    </p>

                    <!-- Price card -->
                    <div class="bg-surface-50 border border-surface-100 rounded-2xl p-5 mb-6">
                        <div class="flex items-end gap-3 flex-wrap">
                            <span
                                v-if="hasDiscount"
                                class="text-lg text-surface-400 line-through"
                            >
                                {{ formatPrice(product.price, product.currency) }}
                            </span>
                            <span
                                v-if="product.effective_price"
                                class="text-3xl font-bold text-brand-700 font-display"
                            >
                                {{ formatPrice(product.effective_price, product.currency) }}
                            </span>
                            <span v-else class="inline-flex items-center gap-2 text-base text-surface-500">
                                <Icon name="info" class="w-4 h-4 text-brand-600" />
                                السعر حسب الطلب
                            </span>
                        </div>

                        <!-- Savings line -->
                        <p v-if="hasDiscount" class="flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 rounded-lg px-2.5 py-1.5 mt-3 w-fit">
                            <Icon name="check" class="w-3.5 h-3.5" />
                            توفّر {{ formatPrice(savings, product.currency) }} مع هذا العرض
                        </p>
                    </div>

                    <!-- Availability -->
                    <div class="flex items-center gap-3 mb-6 flex-wrap">
                        <span
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-sm font-medium"
                            :class="isAvailable ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'"
                        >
                            <span class="w-2 h-2 rounded-full" :class="isAvailable ? 'bg-emerald-500' : 'bg-red-500'"></span>
                            {{ availability }}
                        </span>
                        <span
                            v-if="product.views_count"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-surface-100 text-surface-500 text-sm"
                        >
                            <Icon name="eye" class="w-4 h-4" />
                            <span class="tabular-nums">{{ product.views_count }}</span> مشاهدة
                        </span>
                    </div>

                    <!-- Out-of-stock lead time note -->
                    <p v-if="!isAvailable" class="flex items-center gap-2 text-sm text-surface-600 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 mb-6">
                        <Icon name="truck" class="w-4 h-4 shrink-0 text-amber-600" />
                        القطعة متاحة للطلب المسبق — التسليم خلال 15 يوماً.
                    </p>

                    <!-- Color chips (if available) -->
                    <div v-if="colors.length" class="mb-6">
                        <p class="text-xs font-semibold text-surface-500 mb-2">الألوان المتاحة</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="color in colors"
                                :key="color"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-100 text-surface-700 text-sm font-medium"
                            >
                                <span class="w-2.5 h-2.5 rounded-full border border-black/10 shrink-0"
                                    :class="colorSwatch(color)"
                                ></span>
                                {{ color }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 mb-8">
                        <a
                            :href="whatsappLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1 inline-flex items-center justify-center gap-2 bg-[#25D366] text-white px-6 py-3.5 rounded-xl font-medium transition-all duration-300 hover:bg-[#1fb858] hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2"
                        >
                            <Icon name="whatsapp" class="w-5 h-5" />
                            اطلب عبر واتساب
                        </a>
                        <FavoriteButton :product-id="product.id" variant="solid" class="!px-5" />
                    </div>
                </div>
            </div>

            <!-- Perks strip -->
            <div class="grid grid-cols-1 xs:grid-cols-3 gap-3 mt-14">
                <div
                    v-for="perk in perks"
                    :key="perk.icon"
                    class="flex xs:flex-col xs:text-center items-center gap-4 xs:gap-2.5 bg-surface-50 rounded-2xl px-5 py-4 transition-all duration-300 hover:bg-surface-100"
                >
                    <span class="w-11 h-11 shrink-0 rounded-xl bg-white shadow-sm border border-surface-100 flex items-center justify-center text-brand-600">
                        <Icon :name="perk.icon" class="w-5 h-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-surface-900">{{ perk.title }}</p>
                        <p class="text-xs text-surface-500 mt-0.5">{{ perk.description }}</p>
                    </div>
                </div>
            </div>

            <!-- Specs + Description -->
            <div class="grid lg:grid-cols-2 gap-10 mt-14">
                <!-- Specs -->
                <div v-if="specs.length" class="border border-surface-100 rounded-2xl overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-4 bg-surface-50/60 border-b border-surface-100">
                        <Icon name="tag" class="w-4 h-4 text-brand-600" />
                        <h2 class="font-display font-semibold text-surface-900">المواصفات</h2>
                    </div>
                    <div class="divide-y divide-surface-100">
                        <div v-for="spec in specs" :key="spec.label" class="flex items-center gap-3 px-5 py-3.5">
                            <span class="flex items-center gap-2 w-36 shrink-0 text-sm font-medium text-surface-500">
                                <Icon :name="spec.icon" class="w-4 h-4 text-brand-600" />
                                {{ spec.label }}
                            </span>
                            <span class="flex-1 text-sm text-surface-800">{{ spec.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="product_full?.description" class="border border-surface-100 rounded-2xl overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-4 bg-surface-50/60 border-b border-surface-100">
                        <Icon name="info" class="w-4 h-4 text-brand-600" />
                        <h2 class="font-display font-semibold text-surface-900">الوصف</h2>
                    </div>
                    <p class="text-surface-600 leading-relaxed whitespace-pre-line px-5 py-4">{{ product_full.description }}</p>
                </div>
            </div>
        </div>

        <!-- Related products -->
        <section v-if="related.length" class="relative border-t border-surface-100 bg-surface-50/50">
            <div class="section-container py-16 sm:py-20">
                <RevealOnScroll animation="fade-in-up">
                    <div class="flex items-end justify-between gap-4 mb-10">
                        <div>
                            <span class="inline-flex items-center gap-2 text-brand-600 text-sm font-medium mb-2">
                                <span class="h-px w-8 bg-gradient-gold"></span>
                                قد يعجبك أيضاً
                            </span>
                            <h2 class="font-display text-2xl sm:text-3xl font-bold text-surface-900">منتجات مشابهة</h2>
                        </div>
                        <Link
                            v-if="product.category"
                            :href="`/categories/${product.category.slug}`"
                            class="inline-flex items-center gap-2 text-brand-600 hover:text-brand-700 font-medium text-sm transition-colors group"
                        >
                            <span>كل منتجات التصنيف</span>
                            <Icon name="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                        </Link>
                    </div>
                </RevealOnScroll>

                <ProductGrid
                    :products="related"
                    @quick-view="quickViewProduct = $event; isQuickViewOpen = true"
                />
            </div>
        </section>

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
                v-if="isLightboxOpen"
                class="fixed inset-0 z-50 bg-surface-950/95 backdrop-blur-sm flex items-center justify-center p-4 select-none"
                role="dialog"
                aria-modal="true"
                @click.self="closeLightbox"
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

                <!-- Prev / Next (RTL aware) -->
                <button
                    v-if="media.length > 1"
                    type="button"
                    class="absolute left-4 sm:left-8 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-400"
                    aria-label="الصورة التالية"
                    @click="step(1)"
                >
                    <Icon name="chevron-left" class="w-5 h-5 rotate-180" />
                </button>
                <button
                    v-if="media.length > 1"
                    type="button"
                    class="absolute right-4 sm:right-8 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-400"
                    aria-label="الصورة السابقة"
                    @click="step(-1)"
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
                            v-if="activeImage"
                            :key="activeImage.url"
                            :src="activeImage.url"
                            :alt="activeImage.alt || name"
                            class="max-h-[78vh] max-w-[90vw] object-contain rounded-xl shadow-premium-lg"
                        />
                    </Transition>
                    <span
                        v-if="media.length > 1"
                        class="mt-4 px-2.5 py-0.5 rounded-full bg-white/10 text-xs text-surface-300 tabular-nums"
                    >
                        {{ lightboxIndex + 1 }} / {{ media.length }}
                    </span>
                </figure>
            </div>
        </Transition>

        <!-- Quick view modal -->
        <QuickViewModal
            :product="quickViewProduct"
            :open="isQuickViewOpen"
            @close="isQuickViewOpen = false; quickViewProduct = null"
        />
    </PublicLayout>
</template>