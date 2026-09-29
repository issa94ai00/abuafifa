<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import GalleryGrid from '@/Components/Public/GalleryGrid.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import EmptyState from '@/Components/Public/EmptyState.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    groups: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
});

const totalImages = computed(() =>
    props.groups.reduce((sum, g) => sum + (Array.isArray(g.images) ? g.images.length : 0), 0)
);
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <div
                class="absolute inset-0 opacity-[0.08] bg-cover bg-center"
                style="background-image: url('/images/home/hero-living-1280.webp')"
            ></div>

            <!-- Floating decorative shapes -->
            <div class="absolute top-24 right-[12%] w-2 h-2 rounded-full bg-gold-400/40 animate-pulse" aria-hidden="true"></div>
            <div class="absolute top-44 left-[16%] w-1.5 h-1.5 rounded-full bg-gold-300/30 animate-pulse" style="animation-delay: 1s" aria-hidden="true"></div>
            <div class="absolute bottom-36 right-[20%] w-1 h-1 rounded-full bg-gold-500/50 animate-pulse" style="animation-delay: 0.5s" aria-hidden="true"></div>
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[40rem] h-[40rem] rounded-full bg-gold-500/10 blur-3xl" aria-hidden="true"></div>
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-500/25 to-transparent" aria-hidden="true"></div>

            <div class="section-container pt-32 pb-20 sm:pt-40 sm:pb-28 relative">
                <RevealOnScroll animation="fade-in-up">
                    <span class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium mb-4">
                        <span class="h-px w-8 bg-gradient-to-l from-gold-500 to-transparent"></span>
                        من صالة العرض
                        <span class="h-px w-8 bg-gradient-to-r from-gold-500 to-transparent"></span>
                    </span>
                    <h1 class="font-display text-3xl sm:text-5xl md:text-6xl font-bold text-white leading-tight mb-5">
                        معرض الصور
                        <span class="block text-transparent bg-clip-text bg-gradient-to-l from-gold-300 to-gold-500 mt-2">
                            جمال يتحدث عن نفسه
                        </span>
                    </h1>
                    <p class="text-surface-300 text-base sm:text-lg max-w-2xl leading-relaxed">
                        لقطات من داخل معرضنا تعكس جودة وفخامة تشكيلتنا، والتصاميم التي نفخر بها.
                    </p>

                    <!-- Album count -->
                    <div v-if="groups.length" class="mt-8 inline-flex items-center gap-2.5 px-5 py-2.5 rounded-full bg-white/5 backdrop-blur-sm border border-white/10">
                        <Icon name="info" class="w-4 h-4 text-gold-400" />
                        <span class="text-surface-300 text-sm">
                            {{ groups.length }} ألبومات — {{ totalImages }} صورة
                        </span>
                    </div>
                </RevealOnScroll>
            </div>

            <!-- Bottom edge fade -->
            <div class="absolute bottom-0 inset-x-0 h-24 bg-gradient-to-t from-surface-50 to-transparent" aria-hidden="true"></div>
        </section>

        <!-- Groups -->
        <section class="section-container py-14 sm:py-20 space-y-16 sm:space-y-20">
            <div v-for="(group, groupIndex) in groups" :key="group.id">
                <RevealOnScroll animation="fade-in-up">
                    <div class="flex items-end justify-between gap-4 mb-6">
                        <div class="flex items-center gap-4">
                            <!-- Album index -->
                            <span class="hidden sm:flex w-12 h-12 shrink-0 rounded-2xl bg-gradient-to-br from-brand-50 to-gold-50 ring-1 ring-brand-100 items-center justify-center font-display font-bold text-brand-700">
                                {{ groupIndex + 1 }}
                            </span>
                            <div>
                                <h2 class="font-display text-xl sm:text-2xl font-bold text-surface-900">{{ group.title }}</h2>
                                <p v-if="group.description" class="mt-1 text-sm text-surface-500 max-w-2xl leading-relaxed">{{ group.description }}</p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex shrink-0 items-center gap-1.5 px-3 py-1.5 rounded-full bg-brand-50 text-brand-700 text-sm font-medium">
                            <Icon name="info" class="w-4 h-4" />
                            {{ (group.images || []).length }} صورة
                        </span>
                    </div>
                </RevealOnScroll>

                <GalleryGrid :images="group.images || []" />
            </div>

            <!-- Empty -->
            <RevealOnScroll v-if="!groups.length" animation="fade-in-up">
                <div class="card-premium rounded-2xl">
                    <EmptyState
                        icon="info"
                        title="لا توجد صور بعد"
                        description="سنضيف صوراً من معرضنا قريباً."
                    />
                </div>
            </RevealOnScroll>
        </section>

        <!-- CTA -->
        <section class="section-container pb-16 sm:pb-20 pt-2">
            <RevealOnScroll animation="fade-in-up">
                <div class="relative bg-gradient-gold rounded-3xl overflow-hidden shadow-premium-lg">
                    <div
                        class="absolute inset-0 bg-cover bg-center opacity-15 mix-blend-overlay"
                        style="background-image: url('/images/home/texture-cta-1200.jpg')"
                        aria-hidden="true"
                    ></div>
                    <div class="relative px-8 sm:px-12 lg:px-16 py-14 sm:py-20 text-center">
                        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-surface-950 mb-4">
                            الصور لا تكفي لوصف التجربة
                        </h2>
                        <p class="text-surface-800 max-w-2xl mx-auto mb-8 leading-relaxed">
                            زر معرضنا في العقبة واستشعر الجودة بنفسك، أو تصفح تشكيلتنا الكاملة من منزلك.
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