<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import Breadcrumbs from '@/Components/Public/Breadcrumbs.vue';
import ProductCard from '@/Components/Public/ProductCard.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    offer: { type: Object, required: true },
    seo: { type: Object, default: () => ({}) },
});

const title = computed(() => props.offer.title);
const description = computed(() => props.offer.description || '');
const products = computed(() => props.offer.products || []);

function formatDateRange(iso) {
    if (!iso) return null;
    return new Intl.DateTimeFormat('ar-JO', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(iso));
}
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <img
                v-if="offer.image_url"
                :src="offer.image_url"
                :alt="title"
                loading="eager"
                class="absolute inset-0 w-full h-full object-cover opacity-25"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-surface-950 via-surface-950/80 to-surface-950/40"></div>
            <div class="section-container pt-32 pb-20 sm:pt-36 sm:pb-24 relative">
                <Breadcrumbs light :items="[{ label: 'العروض', href: '/offers' }, { label: title }]" />
                <RevealOnScroll animation="fade-in-up">
                    <span
                        v-if="offer.badge"
                        class="inline-flex items-center px-4 py-1.5 rounded-full bg-gradient-gold text-surface-950 text-sm font-bold shadow-gold mb-4"
                    >
                        {{ offer.badge }}
                    </span>
                    <h1 class="font-display text-3xl sm:text-5xl font-bold text-white mb-4">{{ title }}</h1>
                    <div v-if="offer.ends_at" class="flex items-center gap-2 text-gold-400 text-sm mb-4">
                        <Icon name="clock" class="w-4 h-4" />
                        <span>ينتهي العرض في {{ formatDateRange(offer.ends_at) }}</span>
                    </div>
                    <p v-if="description" class="text-surface-300 text-base sm:text-lg max-w-2xl leading-relaxed">
                        {{ description }}
                    </p>
                </RevealOnScroll>
            </div>
        </section>

        <!-- Included products -->
        <section class="section-container py-14 sm:py-20">
            <div v-if="products.length">
                <RevealOnScroll animation="fade-in-up">
                    <div class="flex items-center gap-3 mb-10">
                        <h2 class="font-display text-2xl sm:text-3xl font-bold text-surface-900">
                            يشمل العرض
                        </h2>
                        <span class="px-3 py-1 rounded-full bg-brand-50 text-brand-700 text-sm font-medium">
                            {{ products.length }} منتجاً
                        </span>
                    </div>
                </RevealOnScroll>

                <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <ProductCard
                        v-for="product in products"
                        :key="product.id"
                        :product="product"
                    />
                </div>
            </div>

            <RevealOnScroll v-else animation="fade-in-up">
                <div class="card-premium rounded-2xl p-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-surface-100 flex items-center justify-center mb-5">
                        <Icon name="tag" class="w-8 h-8 text-surface-400" />
                    </div>
                    <h2 class="font-display text-xl font-bold text-surface-900 mb-2">لا توجد منتجات مضافة لهذا العرض</h2>
                    <a href="/products" class="btn-primary mt-6">تصفح جميع المنتجات</a>
                </div>
            </RevealOnScroll>
        </section>

        <!-- CTA -->
        <section class="section-container pb-16 sm:pb-20">
            <RevealOnScroll animation="fade-in-up">
                <div class="relative bg-gradient-gold rounded-3xl overflow-hidden px-8 sm:px-12 py-12 text-center">
                    <h2 class="font-display text-2xl sm:text-3xl font-bold text-surface-950 mb-3">
                        لا تفوّت هذه الفرصة
                    </h2>
                    <p class="text-surface-700 max-w-xl mx-auto mb-7 leading-relaxed">
                        تواصل معنا الآن للاستفادة من العرض قبل انتهائه.
                    </p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="/contact" class="btn-dark">تواصل معنا</a>
                        <Link href="/products" class="btn-white">تسوق الآن</Link>
                    </div>
                </div>
            </RevealOnScroll>
        </section>
    </PublicLayout>
</template>