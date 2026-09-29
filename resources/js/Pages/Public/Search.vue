<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import ProductGrid from '@/Components/Public/ProductGrid.vue';
import QuickViewModal from '@/Components/Public/QuickViewModal.vue';
import Pagination from '@/Components/Public/Pagination.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    term: { type: String, default: '' },
    results: { type: Object, required: true },
    seo: { type: Object, default: () => ({}) },
});

const page = usePage();

const searchInput = ref(props.term);

const resultsList = computed(() => props.results?.data || []);
const total = computed(() => props.results?.total || 0);
const lastPage = computed(() => props.results?.last_page || 1);

const hasTerm = computed(() => Boolean(props.term.trim()));

const suggestedCategories = computed(() =>
    Array.isArray(page.props.categories) ? page.props.categories.slice(0, 8) : []
);

const popularTerms = ['كنب', 'غرفة نوم', 'طاولة سفرة', 'كرسي', 'مكتب', 'خزانة'];

const quickViewProduct = ref(null);
const isQuickViewOpen = ref(false);

const showSkeleton = ref(false);
let skeletonTimer = null;

function handleInertiaStart() {
    clearTimeout(skeletonTimer);
    skeletonTimer = setTimeout(() => { showSkeleton.value = true; }, 250);
}

function handleInertiaFinish() {
    clearTimeout(skeletonTimer);
    showSkeleton.value = false;
}

onMounted(() => {
    router.on('start', handleInertiaStart);
    router.on('finish', handleInertiaFinish);
});

onUnmounted(() => {
    clearTimeout(skeletonTimer);
    router.off('start', handleInertiaStart);
    router.off('finish', handleInertiaFinish);
});

let debounceTimer = null;

function onInput() {
    clearTimeout(debounceTimer);
    const q = searchInput.value.trim();
    if (!q) return;
    debounceTimer = setTimeout(() => {
        if (q !== props.term) {
            router.get('/search', { q }, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    }, 350);
}

function submitSearch() {
    clearTimeout(debounceTimer);
    const q = searchInput.value.trim();
    if (!q) return;
    router.get('/search', { q }, { preserveState: true, preserveScroll: true });
}

function clearSearch() {
    clearTimeout(debounceTimer);
    searchInput.value = '';
}

function useSuggestion(term) {
    searchInput.value = term;
    router.get('/search', { q: term }, { preserveState: true, preserveScroll: true });
}

function openQuickView(product) {
    quickViewProduct.value = product;
    isQuickViewOpen.value = true;
}
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <div class="absolute inset-0 opacity-[0.07] bg-[url('https://images.unsplash.com/photo-1567016432779-094069958ea5?auto=format&fit=crop&w=1600&q=40')] bg-cover bg-center"></div>
            <div class="section-container pt-28 pb-16 sm:pt-32 sm:pb-20 relative">
                <RevealOnScroll animation="fade-in-up">
                    <h1 class="font-display text-3xl sm:text-4xl font-bold text-white mb-6">
                        البحث في المتجر
                    </h1>
                    <form class="max-w-2xl" @submit.prevent="submitSearch">
                        <div class="flex items-center gap-2 bg-white rounded-2xl p-2 shadow-premium-lg">
                            <Icon name="search" class="w-5 h-5 text-surface-400 mr-3 shrink-0" />
                            <input
                                v-model="searchInput"
                                type="search"
                                placeholder="ابحث عن غرف نوم، كنب، طاولات..."
                                aria-label="البحث في المتجر"
                                class="flex-1 bg-transparent border-0 p-0 text-surface-900 placeholder:text-surface-400 focus:ring-0 text-base"
                                @input="onInput"
                            />
                            <button
                                v-if="searchInput"
                                type="button"
                                class="p-2 rounded-xl text-surface-400 hover:text-surface-700 hover:bg-surface-100 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                aria-label="مسح البحث"
                                @click="clearSearch"
                            >
                                <Icon name="close" class="w-4 h-4" />
                            </button>
                            <button
                                type="submit"
                                class="btn-primary !px-6 !py-3 text-sm shrink-0"
                                :disabled="!searchInput.trim()"
                            >
                                بحث
                            </button>
                        </div>
                    </form>
                </RevealOnScroll>
            </div>
        </section>

        <section class="section-container py-12 sm:py-16">
            <!-- Suggestions panel (no active search term) -->
            <RevealOnScroll v-if="!hasTerm" animation="fade-in-up">
                <div class="max-w-3xl mx-auto space-y-8">
                    <div class="card-premium rounded-2xl p-8">
                        <h2 class="font-display text-xl font-bold text-surface-900 mb-5">تصفح حسب التصنيف</h2>
                        <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 gap-3">
                            <a
                                v-for="category in suggestedCategories"
                                :key="category.id"
                                :href="`/categories/${category.slug}`"
                                class="group flex items-center gap-3 rounded-xl border border-surface-100 p-3 hover:border-brand-200 hover:bg-brand-50/50 transition-colors"
                            >
                                <div class="w-12 h-12 rounded-lg bg-gradient-warm flex items-center justify-center overflow-hidden shrink-0">
                                    <img
                                        v-if="category.image_url"
                                        :src="category.image_url"
                                        :alt="category.name_ar"
                                        loading="lazy"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                    <span v-else class="font-display font-bold text-brand-600">{{ category.name_ar?.charAt(0) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-surface-800 group-hover:text-brand-700 truncate">{{ category.name_ar }}</p>
                                    <p v-if="category.products_count" class="text-[11px] text-surface-400">{{ category.products_count }} منتج</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="card-premium rounded-2xl p-8">
                        <h2 class="font-display text-lg font-bold text-surface-900 mb-4">عمليات بحث شائعة</h2>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="term in popularTerms"
                                :key="term"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-surface-100 text-sm text-surface-700 hover:bg-brand-50 hover:text-brand-700 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                @click="useSuggestion(term)"
                            >
                                <Icon name="search" class="w-3.5 h-3.5" />
                                {{ term }}
                            </button>
                        </div>
                    </div>
                </div>
            </RevealOnScroll>

            <!-- Results -->
            <template v-else>
                <!-- Results count -->
                <div v-if="resultsList.length && !showSkeleton" class="mb-8">
                    <p class="text-sm text-surface-500">
                        نتائج البحث عن
                        <span class="font-semibold text-brand-700">"{{ term }}"</span>
                        — <span class="font-semibold text-surface-800">{{ total }}</span> منتجاً
                    </p>
                </div>

                <ProductGrid
                    :products="resultsList"
                    :loading="showSkeleton"
                    empty-icon="search"
                    empty-title="لا توجد نتائج لبحثك"
                    :empty-description="'لم نعثر على أي منتج يطابق «' + term + '». جرّب كلمات أخرى.'"
                    @quick-view="openQuickView"
                />

                <!-- No-results suggestions -->
                <RevealOnScroll v-if="!resultsList.length && !showSkeleton" animation="fade-in-up">
                    <div class="mt-8 text-center">
                        <p class="text-sm text-surface-500 mb-3">أو جرّب إحدى عمليات البحث المقترحة:</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <button
                                v-for="term in popularTerms"
                                :key="term"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-surface-100 text-sm text-surface-700 hover:bg-brand-50 hover:text-brand-700 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                @click="useSuggestion(term)"
                            >
                                {{ term }}
                            </button>
                        </div>
                    </div>
                </RevealOnScroll>

                <!-- Pagination -->
                <div v-if="resultsList.length && lastPage > 1" class="mt-12">
                    <Pagination :paginator="results" />
                </div>
            </template>
        </section>

        <!-- Quick view modal -->
        <QuickViewModal
            :product="quickViewProduct"
            :open="isQuickViewOpen"
            @close="isQuickViewOpen = false; quickViewProduct = null"
        />
    </PublicLayout>
</template>