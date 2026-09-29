<script setup>
import { computed, reactive, ref, watch, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import ProductGrid from '@/Components/Public/ProductGrid.vue';
import QuickViewModal from '@/Components/Public/QuickViewModal.vue';
import Breadcrumbs from '@/Components/Public/Breadcrumbs.vue';
import Pagination from '@/Components/Public/Pagination.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
});

const page = usePage();

const query = computed(() => new URLSearchParams(window.location.search));

const filters = reactive({
    category: query.value.get('category') || '',
    featured: query.value.get('featured') === '1',
    is_new: query.value.get('is_new') === '1',
    offer: query.value.get('offer') === '1',
    availability: query.value.get('availability') || '',
    min_price: query.value.get('min_price') || '',
    max_price: query.value.get('max_price') || '',
    sort: query.value.get('sort') || 'latest',
    per_page: query.value.get('per_page') || '12',
});

const search = ref(query.value.get('term') || '');

const mobileFiltersOpen = ref(false);
const priceExpanded = ref(true);

const quickViewProduct = ref(null);
const isQuickViewOpen = ref(false);

// Navigation loading (skeleton) state
const showSkeleton = ref(false);
let skeletonTimer = null;

function handleInertiaStart() {
    clearTimeout(skeletonTimer);
    skeletonTimer = setTimeout(() => {
        showSkeleton.value = true;
    }, 250);
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

function openQuickView(product) {
    quickViewProduct.value = product;
    isQuickViewOpen.value = true;
}

function closeQuickView() {
    isQuickViewOpen.value = false;
    quickViewProduct.value = null;
}

const productsList = computed(() => props.products?.data || []);
const lastPage = computed(() => props.products?.last_page || 1);
const total = computed(() => props.products?.total || 0);

const sortOptions = [
    { value: 'latest', label: 'الأحدث' },
    { value: 'price_asc', label: 'السعر: من الأقل للأعلى' },
    { value: 'price_desc', label: 'السعر: من الأعلى للأقل' },
    { value: 'name', label: 'الاسم' },
    { value: 'popular', label: 'الأكثر مشاهدة' },
];

const perPageOptions = [12, 24, 48];

const activeFilterCount = computed(() => {
    let count = 0;
    if (filters.featured) count++;
    if (filters.is_new) count++;
    if (filters.offer) count++;
    if (filters.availability) count++;
    if (filters.min_price || filters.max_price) count++;
    if (search.value.trim()) count++;
    return count;
});

function buildQuery() {
    const params = {};

    if (filters.category) params.category = filters.category;
    if (filters.featured) params.featured = '1';
    if (filters.is_new) params.is_new = '1';
    if (filters.offer) params.offer = '1';
    if (filters.availability) params.availability = filters.availability;
    if (filters.min_price) params.min_price = filters.min_price;
    if (filters.max_price) params.max_price = filters.max_price;
    if (search.value.trim()) params.term = search.value.trim();

    params.sort = filters.sort;
    params.per_page = filters.per_page;

    return params;
}

function applyFilters() {
    router.get('/products', buildQuery(), {
        preserveState: true,
        preserveScroll: true,
    });
    mobileFiltersOpen.value = false;
}

function resetFilters() {
    filters.category = '';
    filters.featured = false;
    filters.is_new = false;
    filters.offer = false;
    filters.availability = '';
    filters.min_price = '';
    filters.max_price = '';
    search.value = '';
    applyFilters();
}

function changeSort() {
    router.get('/products', buildQuery(), {
        preserveState: true,
        preserveScroll: true,
    });
}

function changePerPage() {
    router.get('/products', buildQuery(), {
        preserveState: true,
        preserveScroll: true,
    });
}

watch(() => page.url, () => {
    mobileFiltersOpen.value = false;
});
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Page header -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <div class="absolute inset-0 opacity-[0.07] bg-[url('https://images.unsplash.com/photo-1567016432779-094069958ea5?auto=format&fit=crop&w=1600&q=40')] bg-cover bg-center"></div>
            <div class="section-container pt-28 pb-16 sm:pt-32 sm:pb-20 relative">
                <RevealOnScroll animation="fade-in-up">
                    <h1 class="font-display text-3xl sm:text-4xl font-bold text-white mb-3">منتجاتنا</h1>
                    <p class="text-surface-300 text-base sm:text-lg max-w-2xl leading-relaxed">
                        تشكيلة متجددة من الأثاث الفاخر للصالونات وغرف النوم والسفرة والمكاتب.
                    </p>
                </RevealOnScroll>
            </div>
        </section>

        <div class="section-container py-10 sm:py-14">
            <Breadcrumbs :items="[{ label: 'المنتجات' }]" />

            <!-- Toolbar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
                <p class="text-sm text-surface-500">
                    عرض <span class="font-semibold text-surface-800">{{ productsList.length }}</span> من أصل <span class="font-semibold text-surface-800">{{ total }}</span> منتجاً
                </p>

                <div class="flex items-center gap-3 flex-wrap">
                    <button
                        type="button"
                        class="lg:hidden inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-surface-200 text-sm font-medium text-surface-700 hover:bg-surface-50 transition-colors"
                        :aria-expanded="mobileFiltersOpen"
                        @click="mobileFiltersOpen = !mobileFiltersOpen"
                    >
                        <Icon name="search" class="w-4 h-4" />
                        تصفية
                        <span
                            v-if="activeFilterCount"
                            class="min-w-[20px] h-5 px-1 inline-flex items-center justify-center rounded-full bg-brand-600 text-white text-xs font-bold"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </button>

                    <div class="relative">
                        <label for="sort" class="sr-only">ترتيب النتائج</label>
                        <select
                            id="sort"
                            v-model="filters.sort"
                            class="appearance-none bg-white border border-surface-200 rounded-xl px-4 py-2.5 pr-10 text-sm font-medium text-surface-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                            @change="changeSort"
                        >
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <Icon name="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 pointer-events-none" />
                    </div>

                    <div class="relative">
                        <label for="per_page" class="sr-only">عدد النتائج في الصفحة</label>
                        <select
                            id="per_page"
                            v-model="filters.per_page"
                            class="appearance-none bg-white border border-surface-200 rounded-xl px-4 py-2.5 pr-10 text-sm font-medium text-surface-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                            @change="changePerPage"
                        >
                            <option v-for="n in perPageOptions" :key="n" :value="String(n)">{{ n }} / صفحة</option>
                        </select>
                        <Icon name="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 pointer-events-none" />
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-[260px_1fr] gap-8 items-start">
                <!-- Filters sidebar -->
                <aside class="lg:sticky lg:top-28">
                    <div
                        class="bg-white rounded-2xl border border-surface-100 shadow-premium p-5"
                        :class="mobileFiltersOpen ? 'block' : 'hidden lg:block'"
                    >
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="font-display font-semibold text-surface-900">تصفية النتائج</h2>
                            <button
                                v-if="activeFilterCount"
                                type="button"
                                class="text-xs text-brand-600 hover:text-brand-700 font-medium"
                                @click="resetFilters"
                            >
                                مسح الكل
                            </button>
                        </div>

                        <!-- Search -->
                        <form class="mb-6" @submit.prevent="applyFilters">
                            <label for="term" class="block text-xs font-semibold text-surface-500 mb-2">بحث</label>
                            <div class="relative">
                                <input
                                    id="term"
                                    v-model="search"
                                    type="search"
                                    placeholder="ابحث عن منتج..."
                                    class="w-full bg-surface-50 border border-surface-200 rounded-xl pr-4 pl-10 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                />
                                <button
                                    type="submit"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-surface-400 hover:text-brand-600 transition-colors"
                                    aria-label="بحث"
                                >
                                    <Icon name="search" class="w-4 h-4" />
                                </button>
                            </div>
                        </form>

                        <!-- Categories -->
                        <div class="mb-6">
                            <h3 class="text-xs font-semibold text-surface-500 mb-3">التصنيفات</h3>
                            <div class="space-y-1">
                                <label v-for="category in categories" :key="category.id" class="flex items-center gap-2.5 py-1.5 cursor-pointer group">
                                    <input
                                        type="radio"
                                        name="category"
                                        :value="String(category.id)"
                                        v-model="filters.category"
                                        class="w-4 h-4 rounded border-surface-300 text-brand-600 focus:ring-brand-500"
                                        @change="applyFilters"
                                    />
                                    <span class="text-sm text-surface-700 group-hover:text-brand-700 transition-colors">
                                        {{ category.name_ar }}
                                        <span class="text-xs text-surface-400">({{ category.products_count }})</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Quick filters -->
                        <div class="space-y-2 mb-6">
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    v-model="filters.featured"
                                    class="w-4 h-4 rounded border-surface-300 text-brand-600 focus:ring-brand-500"
                                    @change="applyFilters"
                                />
                                <span class="text-sm text-surface-700 group-hover:text-brand-700 transition-colors">المميزة</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    v-model="filters.is_new"
                                    class="w-4 h-4 rounded border-surface-300 text-brand-600 focus:ring-brand-500"
                                    @change="applyFilters"
                                />
                                <span class="text-sm text-surface-700 group-hover:text-brand-700 transition-colors">وصل حديثاً</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    v-model="filters.offer"
                                    class="w-4 h-4 rounded border-surface-300 text-brand-600 focus:ring-brand-500"
                                    @change="applyFilters"
                                />
                                <span class="text-sm text-surface-700 group-hover:text-brand-700 transition-colors">تشمل عروضاً</span>
                            </label>
                        </div>

                        <!-- Availability -->
                        <div class="mb-6">
                            <h3 class="text-xs font-semibold text-surface-500 mb-3">التوفر</h3>
                            <div class="space-y-1">
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer group">
                                    <input
                                        type="radio"
                                        name="availability"
                                        value=""
                                        v-model="filters.availability"
                                        class="w-4 h-4 rounded border-surface-300 text-brand-600 focus:ring-brand-500"
                                        @change="applyFilters"
                                    />
                                    <span class="text-sm text-surface-700">الكل</span>
                                </label>
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer group">
                                    <input
                                        type="radio"
                                        name="availability"
                                        value="available"
                                        v-model="filters.availability"
                                        class="w-4 h-4 rounded border-surface-300 text-brand-600 focus:ring-brand-500"
                                        @change="applyFilters"
                                    />
                                    <span class="text-sm text-surface-700">متوفر</span>
                                </label>
                                <label class="flex items-center gap-2.5 py-1.5 cursor-pointer group">
                                    <input
                                        type="radio"
                                        name="availability"
                                        value="sold_out"
                                        v-model="filters.availability"
                                        class="w-4 h-4 rounded border-surface-300 text-brand-600 focus:ring-brand-500"
                                        @change="applyFilters"
                                    />
                                    <span class="text-sm text-surface-700">نفدت الكمية</span>
                                </label>
                            </div>
                        </div>

                        <!-- Price range -->
                        <div class="border-t border-surface-100 pt-5">
                            <button
                                type="button"
                                class="w-full flex items-center justify-between text-xs font-semibold text-surface-500 mb-3"
                                :aria-expanded="priceExpanded"
                                @click="priceExpanded = !priceExpanded"
                            >
                                نطاق السعر
                                <Icon
                                    name="chevron-down"
                                    class="w-4 h-4 transition-transform"
                                    :class="{ 'rotate-180': priceExpanded }"
                                />
                            </button>
                            <template v-if="priceExpanded">
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="filters.min_price"
                                        type="number"
                                        min="0"
                                        placeholder="من"
                                        class="w-full bg-surface-50 border border-surface-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                    />
                                    <span class="text-surface-400 text-sm">—</span>
                                    <input
                                        v-model="filters.max_price"
                                        type="number"
                                        min="0"
                                        placeholder="إلى"
                                        class="w-full bg-surface-50 border border-surface-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="w-full mt-3 py-2 rounded-xl bg-surface-100 text-sm font-medium text-surface-700 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                                    @click="applyFilters"
                                >
                                    تطبيق
                                </button>
                            </template>
                        </div>
                    </div>
                </aside>

                <!-- Products grid -->
                <div>
                    <ProductGrid
                        :products="productsList"
                        :loading="showSkeleton"
                        :has-filters="activeFilterCount > 0"
                        empty-title="لا توجد نتائج مطابقة"
                        :empty-description="activeFilterCount ? 'جرّب تعديل الفلاتر أو البحث بكلمات أخرى.' : 'لا توجد منتجات بعد، ترقّبوا القادم!'"
                        @quick-view="openQuickView"
                        @reset="resetFilters"
                    />

                    <!-- Pagination -->
                    <div v-if="productsList.length && lastPage > 1" class="mt-12">
                        <Pagination :paginator="products" />
                    </div>
                </div>

                <!-- Quick view modal -->
                <QuickViewModal
                    :product="quickViewProduct"
                    :open="isQuickViewOpen"
                    @close="closeQuickView"
                />
            </div>
        </div>
    </PublicLayout>
</template>