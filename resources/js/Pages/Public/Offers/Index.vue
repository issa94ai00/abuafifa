<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import OfferCard from '@/Components/Public/OfferCard.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import EmptyState from '@/Components/Public/EmptyState.vue';
import Icon from '@/Components/Public/Icon.vue';

defineProps({
    offers: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
});
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <div
                class="absolute inset-0 opacity-[0.07] bg-cover bg-center"
                style="background-image: url('/images/home/texture-offers-1200.jpg')"
            ></div>

            <!-- Floating decorative shapes -->
            <div class="absolute top-24 right-[12%] w-2 h-2 rounded-full bg-gold-400/40 animate-pulse" aria-hidden="true"></div>
            <div class="absolute top-40 left-[18%] w-1.5 h-1.5 rounded-full bg-gold-300/30 animate-pulse" style="animation-delay: 1s" aria-hidden="true"></div>
            <div class="absolute bottom-36 right-[22%] w-1 h-1 rounded-full bg-gold-500/50 animate-pulse" style="animation-delay: 0.5s" aria-hidden="true"></div>
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[40rem] h-[40rem] rounded-full bg-gold-500/10 blur-3xl" aria-hidden="true"></div>
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-500/25 to-transparent" aria-hidden="true"></div>

            <div class="section-container pt-32 pb-20 sm:pt-40 sm:pb-28 relative">
                <RevealOnScroll animation="fade-in-up">
                    <span class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium mb-4">
                        <span class="h-px w-8 bg-gradient-to-l from-gold-500 to-transparent"></span>
                        لحظات لا تُفوّت
                        <span class="h-px w-8 bg-gradient-to-r from-gold-500 to-transparent"></span>
                    </span>
                    <h1 class="font-display text-3xl sm:text-5xl md:text-6xl font-bold text-white leading-tight mb-5">
                        عروض مميزة
                        <span class="block text-transparent bg-clip-text bg-gradient-to-l from-gold-300 to-gold-500 mt-2">
                            بخصومات حقيقية
                        </span>
                    </h1>
                    <p class="text-surface-300 text-base sm:text-lg max-w-2xl leading-relaxed">
                        استمتع بخصومات حصرية على تشكيلة مختارة بعناية من أرقى المفروشات والأثاث الفاخر.
                    </p>

                    <!-- Offer count -->
                    <div v-if="offers.length" class="mt-8 inline-flex items-center gap-2.5 px-5 py-2.5 rounded-full bg-white/5 backdrop-blur-sm border border-white/10">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        <span class="text-surface-300 text-sm">
                            {{ offers.length }} {{ offers.length === 1 ? 'عرضاً متاحاً الآن' : 'عروض متاحة الآن' }}
                        </span>
                    </div>
                </RevealOnScroll>
            </div>

            <!-- Bottom edge fade -->
            <div class="absolute bottom-0 inset-x-0 h-24 bg-gradient-to-t from-surface-50 to-transparent" aria-hidden="true"></div>
        </section>

        <!-- Offers grid -->
        <section class="section-container py-14 sm:py-20">
            <div v-if="offers.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
                <RevealOnScroll
                    v-for="(offer, index) in offers"
                    :key="offer.id"
                    :delay="index * 80"
                    animation="fade-in-up"
                >
                    <OfferCard :offer="offer" />
                </RevealOnScroll>
            </div>

            <RevealOnScroll v-else animation="fade-in-up">
                <div class="card-premium rounded-2xl">
                    <EmptyState
                        icon="tag"
                        title="لا توجد عروض حالياً"
                        description="تابعنا لتكون أول من يعرف عن العروض القادمة."
                        action-text="تصفح المنتجات"
                    >
                        <template #action>
                            <a href="/products" class="btn-primary">
                                <Icon name="sparkles" class="w-4 h-4" />
                                تصفح المنتجات
                            </a>
                        </template>
                    </EmptyState>
                </div>
            </RevealOnScroll>
        </section>

        <!-- CTA -->
        <section class="section-container pb-16 sm:pb-20">
            <RevealOnScroll animation="fade-in-up">
                <div class="relative bg-gradient-gold rounded-3xl overflow-hidden shadow-premium-lg">
                    <div
                        class="absolute inset-0 bg-cover bg-center opacity-15 mix-blend-overlay"
                        style="background-image: url('/images/home/texture-cta-1200.jpg')"
                        aria-hidden="true"
                    ></div>
                    <div class="relative px-8 sm:px-12 lg:px-16 py-14 sm:py-20 text-center">
                        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-surface-950 mb-4">
                            ابحث عن قطعة أحلامك
                        </h2>
                        <p class="text-surface-800 max-w-2xl mx-auto mb-8 leading-relaxed">
                            تصفح تشكيلتنا الكاملة من الأثاث الفاخر، وتواصل معنا لاستشارة مجانية قبل أن تدخل العروض مرحلة الإغلاق.
                        </p>
                        <div class="flex flex-wrap justify-center gap-4">
                            <Link href="/products" class="btn-dark shadow-premium">
                                <Icon name="sparkles" class="w-5 h-5" />
                                تصفح المنتجات
                            </Link>
                            <Link href="/contact" class="btn-white shadow-premium">
                                تواصل معنا
                            </Link>
                        </div>
                    </div>
                </div>
            </RevealOnScroll>
        </section>
    </PublicLayout>
</template>