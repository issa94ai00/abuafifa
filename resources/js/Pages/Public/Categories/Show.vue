<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import Breadcrumbs from '@/Components/Public/Breadcrumbs.vue';
import ProductGrid from '@/Components/Public/ProductGrid.vue';
import QuickViewModal from '@/Components/Public/QuickViewModal.vue';
import Pagination from '@/Components/Public/Pagination.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    category: { type: Object, required: true },
    products: { type: Object, required: true },
    seo: { type: Object, default: () => ({}) },
});

const page = usePage();

const query = computed(() => new URLSearchParams(window.location.search));

const sort = ref(query.value.get('sort') || 'latest');
const per_page = ref(query.value.get('per_page') || '12');

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

const name = computed(() => props.category.name_ar || props.category.name_en);
const description = computed(() => props.category.description_ar || props.category.description_en || '');
const coverUrl = computed(() => props.category.image?.url);
const productsList = computed(() => props.products?.data || []);
const lastPage = computed(() => props.products?.last_page || 1);
const total = computed(() => props.products?.total || 0);

const settings = computed(() => page.props.settings || {});
const whatsappNumber = computed(() => String(settings.value.whatsapp || '').replace(/\D/g, ''));
const whatsappLink = computed(() => {
    if (!whatsappNumber.value) return '/contact';
    const text = encodeURIComponent(`مرحباً، أريد تصفح تشكيلة "${name.value}" من معرض أبو عفيفة للمفروشات.`);
    return `https://wa.me/${whatsappNumber.value}?text=${text}`;
});

const sortOptions = [
    { value: 'latest', label: 'الأحدث' },
    { value: 'price_asc', label: 'السعر: من الأقل للأعلى' },
    { value: 'price_desc', label: 'السعر: من الأعلى للأقل' },
    { value: 'name', label: 'الاسم' },
    { value: 'popular', label: 'الأكثر مشاهدة' },
];

const perPageOptions = [12, 24, 48];

function navigate() {
    router.get(`/categories/${props.category.slug}`, { sort: sort.value, per_page: per_page.value }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function changeSort() { navigate(); }
function changePerPage() { navigate(); }

function openQuickView(product) {
    quickViewProduct.value = product;
    isQuickViewOpen.value = true;
}

function closeQuickView() {
    isQuickViewOpen.value = false;
    quickViewProduct.value = null;
}

watch(() => page.url, () => { showSkeleton.value = false; });
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <img
                v-if="coverUrl"
                :src="coverUrl"
                :alt="name"
                loading="eager"
                class="absolute inset-0 w-full h-full object-cover opacity-20"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-surface-950 via-surface-950/80 to-surface-950/40"></div>

            <!-- Top accent line -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/30 to-transparent"></div>

            <!-- Floating gold dots -->
            <div class="absolute top-28 right-[8%] w-1.5 h-1.5 rounded-full bg-gold-400/35 animate-pulse"></div>
            <div class="absolute top-40 left-[12%] w-2 h-2 rounded-full bg-gold-500/20 animate-pulse" style="animation-delay: 1s"></div>
            <div class="absolute bottom-28 right-[20%] w-1 h-1 rounded-full bg-gold-400/25 animate-pulse" style="animation-delay: 2s"></div>

            <div class="section-container pt-32 pb-20 sm:pt-36 sm:pb-24 relative">
                <Breadcrumbs light :items="[{ label: 'الرئيسية', href: '/' }, { label: 'التصنيفات', href: '/categories' }, { label: name }]" />

                <RevealOnScroll animation="fade-in-up">
                    <!-- Eyebrow -->
                    <span class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium mb-4">
                        <span class="h-px w-8 bg-gradient-to-l from-gold-500 to-transparent"></span>
                        تصفح حسب الغرفة
                        <span class="h-px w-8 bg-gradient-to-r from-gold-500 to-transparent"></span>
                    </span>

                    <h1 class="font-display text-3xl sm:text-5xl font-bold text-white mb-4 leading-tight">
                        <span class="bg-gradient-to-b from-white via-white to-surface-300 bg-clip-text text-transparent">{{ name }}</span>
                    </h1>

                    <!-- Product count pill -->
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/10 rounded-full px-4 py-1.5 text-sm text-surface-200 mb-5">
                        <Icon name="sparkles" class="w-3.5 h-3.5 text-gold-400" />
                        <span>{{ category.products_count || total }} منتج متاح</span>
                    </div>

                    <p v-if="description" class="text-surface-300 text-base sm:text-lg max-w-2xl leading-relaxed mb-8">
                        {{ description }}
                    </p>

                    <!-- Hero CTAs -->
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="#products"
                            class="inline-flex items-center gap-2 bg-gold-500 hover:bg-gold-600 text-surface-950 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-300 hover:scale-[1.02] shadow-lg shadow-gold-500/20"
                        >
                            <Icon name="eye" class="w-4 h-4" />
                            تصفح التشكيلة
                        </a>
                        <a
                            :href="whatsappLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/15 text-white px-6 py-3 rounded-xl font-medium text-sm transition-all duration-300 hover:border-gold-500/30"
                        >
                            <Icon name="whatsapp" class="w-4 h-4 text-[#25D366]" />
                            استشارة عبر واتساب
                        </a>
                    </div>
                </RevealOnScroll>
            </div>

            <!-- Bottom fade -->
            <div class="absolute bottom-0 inset-x-0 h-24 bg-gradient-to-t from-surface-50 to-transparent"></div>
        </section>

        <!-- Products -->
        <section id="products" class="section-container py-12 sm:py-16">
            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <!-- Result count -->
                <div class="inline-flex items-center gap-2 bg-surface-100 rounded-full px-4 py-2">
                    <Icon name="sparkles" class="w-3.5 h-3.5 text-brand-600" />
                    <p class="text-sm text-surface-600">
                        عرض <span class="font-bold text-surface-900">{{ productsList.length }}</span> من أصل
                        <span class="font-bold text-surface-900">{{ total }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <label for="cat-sort" class="sr-only">ترتيب النتائج</label>
                    <div class="relative">
                        <select
                            id="cat-sort"
                            v-model="sort"
                            class="appearance-none bg-white border border-surface-200 rounded-xl px-4 py-2.5 pr-10 text-sm font-medium text-surface-700 shadow-sm hover:border-surface-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400 transition-all"
                            @change="changeSort"
                        >
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <Icon name="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 pointer-events-none" />
                    </div>

                    <label for="cat-perpage" class="sr-only">عدد النتائج في الصفحة</label>
                    <div class="relative">
                        <select
                            id="cat-perpage"
                            v-model="per_page"
                            class="appearance-none bg-white border border-surface-200 rounded-xl px-4 py-2.5 pr-10 text-sm font-medium text-surface-700 shadow-sm hover:border-surface-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-400 transition-all"
                            @change="changePerPage"
                        >
                            <option v-for="n in perPageOptions" :key="n" :value="String(n)">{{ n }} / صفحة</option>
                        </select>
                        <Icon name="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 pointer-events-none" />
                    </div>
                </div>
            </div>

            <ProductGrid
                :products="productsList"
                :loading="showSkeleton"
                empty-icon="info"
                empty-title="لا توجد منتجات في هذا التصنيف بعد"
                empty-description="نحن نجدد تشكيلتنا باستمرار، تابعنا قريباً."
                @quick-view="openQuickView"
            >
                <template #empty-action>
                    <Link href="/products" class="btn-primary">تصفح جميع المنتجات</Link>
                </template>
            </ProductGrid>

            <!-- Pagination -->
            <div v-if="productsList.length && lastPage > 1" class="mt-12">
                <Pagination :paginator="products" />
            </div>
        </section>

        <!-- CTA -->
        <section class="section-container pb-16">
            <div class="relative bg-gradient-to-l from-surface-900 via-surface-950 to-surface-900 rounded-2xl p-8 sm:p-12 overflow-hidden text-center">
                <div class="absolute inset-0 opacity-10 bg-[url('/images/home/texture-contact.jpg')] bg-cover bg-center"></div>
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/30 to-transparent"></div>
                <div class="relative">
                    <h3 class="font-display text-xl sm:text-2xl font-bold text-white mb-3">لم تجد ما يناسب مساحتك؟</h3>
                    <p class="text-surface-400 text-sm max-w-md mx-auto mb-6">فريقنا جاهز لمساعدتك في اختيار الأثاث المثالي أو تلبية طلبك الخاص.</p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <a
                            :href="whatsappLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 bg-[#25D366] text-white px-6 py-3 rounded-xl font-medium text-sm transition-all duration-300 hover:bg-[#1fb858] hover:scale-[1.02]"
                        >
                            <Icon name="whatsapp" class="w-4 h-4" />
                            استشارة مجانية عبر واتساب
                        </a>
                        <Link
                            href="/products"
                            class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/15 text-white px-6 py-3 rounded-xl font-medium text-sm transition-all duration-300"
                        >
                            <Icon name="eye" class="w-4 h-4" />
                            تصفح جميع المنتجات
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick view modal -->
        <QuickViewModal
            :product="quickViewProduct"
            :open="isQuickViewOpen"
            @close="closeQuickView"
        />
    </PublicLayout>
</template>
