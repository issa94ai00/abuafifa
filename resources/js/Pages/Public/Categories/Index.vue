<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import CategoryCard from '@/Components/Public/CategoryCard.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
});

const totalProducts = computed(() =>
    props.categories.reduce((sum, c) => sum + (c.products_count || 0), 0)
);
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <div class="absolute inset-0 opacity-[0.07] bg-[url('https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1600&q=40')] bg-cover bg-center"></div>
            <div class="section-container pt-32 pb-20 sm:pt-36 sm:pb-24 relative">
                <RevealOnScroll animation="fade-in-up">
                    <span class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium mb-3">
                        <span class="h-px w-8 bg-gradient-to-l from-gold-500 to-transparent"></span>
                        تصفح حسب النمط
                    </span>
                    <h1 class="font-display text-3xl sm:text-5xl font-bold text-white mb-4">
                        تصاميم لكل ذوق
                    </h1>
                    <p class="text-surface-300 text-base sm:text-lg max-w-2xl leading-relaxed">
                        اختر التصنيف الذي يلهمك واستكشف تشكيلة متنوعة من الأثاث الفاخر المصنوع بأناقة وحرفية.
                    </p>
                </RevealOnScroll>
            </div>
        </section>

        <!-- Grid -->
        <section class="section-container py-14 sm:py-20">
            <RevealOnScroll v-if="categories.length" animation="fade-in-up">
                <p class="text-sm text-surface-500 mb-6">
                    {{ categories.length }} تصنيفاً — {{ totalProducts }} منتجاً متوفراً
                </p>
            </RevealOnScroll>

            <div v-if="categories.length" class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                <RevealOnScroll
                    v-for="(category, index) in categories"
                    :key="category.id"
                    :delay="index * 60"
                >
                    <CategoryCard :category="category" />
                </RevealOnScroll>
            </div>

            <!-- Empty state -->
            <RevealOnScroll v-else animation="fade-in-up">
                <div class="card-premium rounded-2xl p-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-surface-100 flex items-center justify-center mb-5">
                        <Icon name="info" class="w-8 h-8 text-surface-400" />
                    </div>
                    <h2 class="font-display text-xl font-bold text-surface-900 mb-2">لا توجد تصنيفات بعد</h2>
                    <p class="text-surface-500 text-sm max-w-md mx-auto mb-6">
                        سنضيف تصنيفات جديدة قريباً. ترقبوا الجديد!
                    </p>
                    <Link href="/products" class="btn-primary">تصفح جميع المنتجات</Link>
                </div>
            </RevealOnScroll>
        </section>
    </PublicLayout>
</template>