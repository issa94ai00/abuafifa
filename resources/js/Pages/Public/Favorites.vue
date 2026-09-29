<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import ProductGrid from '@/Components/Public/ProductGrid.vue';
import QuickViewModal from '@/Components/Public/QuickViewModal.vue';
import Pagination from '@/Components/Public/Pagination.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    products: { type: Object, required: true },
    seo: { type: Object, default: () => ({}) },
});

const productsList = computed(() => props.products?.data || []);
const total = computed(() => props.products?.total || 0);
const lastPage = computed(() => props.products?.last_page || 1);

const quickViewProduct = ref(null);
const isQuickViewOpen = ref(false);
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <div class="absolute inset-0 opacity-[0.06] bg-[url('https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1600&q=40')] bg-cover bg-center"></div>
            <div class="section-container pt-28 pb-16 sm:pt-32 sm:pb-20 relative">
                <RevealOnScroll animation="fade-in-up">
                    <h1 class="font-display text-3xl sm:text-4xl font-bold text-white mb-3">منتجاتي المفضلة</h1>
                    <p class="text-surface-300 text-base sm:text-lg max-w-2xl leading-relaxed">
                        احتفظ بمنتجاتك المفضلة هنا واطلبها متى شئت.
                    </p>
                </RevealOnScroll>
            </div>
        </section>

        <section class="section-container py-12 sm:py-16">
            <!-- Count -->
            <div v-if="productsList.length" class="flex items-center gap-3 mb-8">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-50 text-brand-700 text-sm font-semibold">
                    <Icon name="heart-filled" class="w-4 h-4" />
                    {{ total }} منتجاً في قائمتك
                </span>
            </div>

            <!-- Grid -->
            <ProductGrid
                :products="productsList"
                empty-icon="heart"
                empty-title="قائمة مفضلتك فارغة"
                empty-description="استكشف تشكيلتنا وأضف المنتجات التي تعجبك إلى المفضلة بالضغط على أيقونة القلب."
                @quick-view="quickViewProduct = $event; isQuickViewOpen = true"
            />
            <RevealOnScroll v-if="!productsList.length" animation="fade-in-up">
                <div class="mt-6 text-center">
                    <Link href="/products" class="btn-primary">تصفح المنتجات</Link>
                </div>
            </RevealOnScroll>

            <!-- Pagination -->
            <div v-if="productsList.length && lastPage > 1" class="mt-12">
                <Pagination :paginator="products" />
            </div>
        </section>

        <!-- Quick view modal -->
        <QuickViewModal
            :product="quickViewProduct"
            :open="isQuickViewOpen"
            @close="isQuickViewOpen = false; quickViewProduct = null"
        />
    </PublicLayout>
</template>