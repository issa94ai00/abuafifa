<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import Modal from '@/Components/Modal.vue';
import { usePrice } from '@/Composables/usePrice';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
    stats: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

const { formatPrice } = usePrice();

// -------------------------------------------------------------- filter state
const trashMode = ref(!!props.filters?.trash);
const search = ref(props.filters?.search ?? '');
const categoryId = ref(props.filters?.category_id ?? '');
const availability = ref(props.filters?.availability ?? '');

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

function applyFilters(overrides = {}) {
    router.get(
        route('admin.products.index'),
        {
            ...(search.value ? { search: search.value } : {}),
            ...(categoryId.value ? { category_id: categoryId.value } : {}),
            ...(availability.value ? { availability: availability.value } : {}),
            ...(trashMode.value ? { trash: 1 } : {}),
            page: 1,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters() {
    search.value = '';
    categoryId.value = '';
    availability.value = '';
    trashMode.value = false;
    clearTimeout(searchTimer);
    applyFilters();
}

const hasActiveFilters = computed(
    () => !!(search.value || categoryId.value || availability.value),
);

// ------------------------------------------------------------- delete / restore
const confirmDelete = ref(null);
const deletingId = ref(null);

function openDelete(product) {
    confirmDelete.value = product;
}

function deleteProduct() {
    if (!confirmDelete.value) return;
    deletingId.value = confirmDelete.value.id;
    router.delete(route('admin.products.destroy', confirmDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDelete.value = null;
            deletingId.value = null;
        },
        onError: () => {
            deletingId.value = null;
        },
    });
}

const restoringId = ref(null);

function restoreProduct(product) {
    restoringId.value = product.id;
    router.post(route('admin.products.restore', product.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            restoringId.value = null;
        },
    });
}

// ------------------------------------------------------------------ pagination
function pageLinks() {
    const links = props.products?.links ?? [];
    const pages = links.filter((l) => l.url && Number.isInteger(Number(l.label)));

    const current = props.products?.current_page ?? 1;
    const lastPage = props.products?.last_page ?? 1;
    const around = 1;

    let first = Number(pages[0]?.label ?? 1);
    let last = Number(pages[pages.length - 1]?.label ?? lastPage);

    first = Math.max(first, current - around);
    last = Math.min(last, current + around);

    const visible = pages.filter((l) => {
        const n = Number(l.label);
        return n === 1 || n === current || (n >= first && n <= last);
    });

    const result = [];
    let prev = null;
    for (const link of visible) {
        if (prev !== null && Number(link.label) - prev > 1) {
            result.push({ key: `ellipsis-${prev}`, label: '…', url: null, active: false, ellipsis: true });
        }
        result.push({ ...link, ellipsis: false });
        prev = Number(link.label);
    }
    return result;
}

function goTo(url) {
    if (!url) return;
    const parsed = new URL(url, window.location.origin);
    router.get(parsed.pathname + parsed.search, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// ------------------------------------------------------------------ stats
const statCards = computed(() => [
    {
        key: 'total',
        label: 'إجمالي المنتجات',
        value: props.stats?.total ?? 0,
        icon: 'box',
        tone: 'bg-brand-50 text-brand-600 group-hover:ring-brand-300',
        href: route('admin.products.index'),
    },
    {
        key: 'active',
        label: 'متاحة للعرض',
        value: props.stats?.active ?? 0,
        icon: 'check',
        tone: 'bg-emerald-50 text-emerald-600 group-hover:ring-emerald-300',
        href: route('admin.products.index'),
    },
    {
        key: 'featured',
        label: 'منتجات مميزة',
        value: props.stats?.featured ?? 0,
        icon: 'star',
        tone: 'bg-gold-50 text-gold-600 group-hover:ring-gold-300',
        href: route('admin.products.index'),
    },
    {
        key: 'inactive',
        label: 'مخفية',
        value: props.stats?.inactive ?? 0,
        icon: 'eye-off',
        tone: 'bg-surface-100 text-surface-600 group-hover:ring-surface-300',
        href: route('admin.products.index'),
    },
]);

// ------------------------------------------------------------ badges / helpers
const availabilityStyles = {
    available: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    on_order: 'bg-amber-50 text-amber-700 ring-amber-200',
    sold_out: 'bg-red-50 text-red-700 ring-red-200',
};

const availabilityLabels = {
    available: 'متوفر',
    on_order: 'يُطلب مسبقاً',
    sold_out: 'نفدت الكمية',
};

function priceView(product) {
    const symbol = product.currency === 'USD' ? '$' : product.currency === 'EUR' ? '€' : product.currency === 'SYP' ? 'ل.س' : 'د.أ';
    const money = (v) => new Intl.NumberFormat('ar-JO', { maximumFractionDigits: 0 }).format(v) + ' ' + symbol;
    return {
        current: money(product.sale_price ?? product.price),
        old: product.sale_price ? money(product.price) : null,
    };
}
</script>

<template>
    <Head title="المنتجات" />

    <AdminLayout>
        <!-- Stats -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Link
                v-for="(card, i) in statCards"
                :key="card.key"
                :href="card.href"
                class="card-admin group animate-fade-in-up p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-premium-lg sm:p-5"
                :style="{ animationDelay: `${i * 0.05}s` }"
            >
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl transition ring-4 ring-transparent" :class="card.tone">
                        <AdminIcon :name="card.icon" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <span class="block text-2xl font-bold tabular-nums text-surface-900">{{ card.value }}</span>
                        <span class="block truncate text-xs font-medium text-surface-400">{{ card.label }}</span>
                    </div>
                </div>
            </Link>
        </section>

        <!-- Toolbar -->
        <section class="card-admin mt-6 animate-fade-in-up p-4 sm:p-5" :style="{ animationDelay: '0.05s' }">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Tabs -->
                <div class="inline-flex items-center gap-1 rounded-xl bg-surface-100 p-1">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                        :class="trashMode ? 'text-surface-500 hover:text-surface-700' : 'bg-white text-surface-900 shadow-sm ring-1 ring-black/5'"
                        @click="(trashMode = false), applyFilters()"
                    >
                        <AdminIcon name="box" size="sm" />
                        المنتجات
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                        :class="trashMode ? 'bg-white text-surface-900 shadow-sm ring-1 ring-black/5' : 'text-surface-500 hover:text-surface-700'"
                        @click="(trashMode = true), applyFilters()"
                    >
                        <AdminIcon name="trash" size="sm" />
                        المحذوفات
                        <span v-if="props.stats?.trashed" class="inline-flex min-w-[18px] items-center justify-center rounded-full bg-red-100 px-1.5 text-[11px] font-bold text-red-700">
                            {{ props.stats.trashed }}
                        </span>
                    </button>
                </div>

                <div class="relative min-w-[200px] flex-1">
                    <AdminIcon name="search" size="sm" class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-surface-400" />
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="trashMode ? 'بحث في المحذوفات...' : 'بحث بالاسم أو رمز المنتج (SKU)...'"
                        class="input-admin ps-10"
                    />
                </div>

                <select v-model="categoryId" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[150px]" @change="applyFilters()">
                    <option value="">كل التصنيفات</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name_ar }}</option>
                </select>

                <select v-model="availability" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[140px]" @change="applyFilters()">
                    <option value="">كل الحالات</option>
                    <option value="available">متوفر</option>
                    <option value="on_order">يُطلب مسبقاً</option>
                    <option value="sold_out">نفدت الكمية</option>
                </select>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                    @click="resetFilters"
                >
                    <AdminIcon name="refresh" size="sm" />
                    مسح الفلاتر
                </button>

                <Link
                    v-if="can('product.create') && !trashMode"
                    :href="route('admin.products.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98]"
                >
                    <AdminIcon name="plus" size="sm" />
                    إضافة منتج
                </Link>
            </div>
        </section>

        <!-- Products table -->
        <section class="card-admin mt-6 animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.1s' }">
            <div v-if="products?.data?.length" class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-surface-100">
                    <thead class="bg-surface-50/70">
                        <tr>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">المنتج</th>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">التصنيف</th>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">السعر</th>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">الحالة</th>
                            <th class="px-5 py-3.5 text-end text-xs font-bold uppercase tracking-wider text-surface-400">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="group transition-colors hover:bg-surface-50/60"
                            :class="{ 'opacity-60': product.trashed }"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div
                                        v-if="product.cover_thumb_url"
                                        class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200"
                                    >
                                        <img :src="product.cover_thumb_url" :alt="product.name_ar" class="h-full w-full object-cover" loading="lazy" />
                                    </div>
                                    <div v-else class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-surface-100 text-surface-400 ring-1 ring-surface-200">
                                        <AdminIcon name="box" size="lg" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="truncate text-sm font-semibold text-surface-900">{{ product.name_ar }}</span>
                                            <AdminIcon v-if="product.is_featured" name="star" size="xs" class="text-gold-500" title="مميز" />
                                            <AdminIcon v-if="product.is_new" name="sparkles" size="xs" class="text-brand-500" title="وصل حديثاً" />
                                            <AdminIcon v-if="product.trashed" name="trash" size="xs" class="text-red-400" title="محذوف" />
                                        </div>
                                        <p class="mt-0.5 flex items-center gap-2 truncate text-xs text-surface-400">
                                            <span v-if="product.name_en" class="truncate">{{ product.name_en }}</span>
                                            <span v-if="product.sku" class="shrink-0 rounded bg-surface-100 px-1.5 py-0.5 font-mono text-[11px] text-surface-500">SKU: {{ product.sku }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span v-if="product.category" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200">
                                    <AdminIcon name="folder" size="xs" />
                                    {{ product.category.name_ar }}
                                </span>
                                <span v-else class="text-xs text-surface-400">—</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-bold tabular-nums text-surface-900">
                                    {{ priceView(product).current }}
                                </div>
                                <div v-if="priceView(product).old" class="text-xs tabular-nums text-surface-400 line-through">
                                    {{ priceView(product).old }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="badge-admin ring-1 ring-inset" :class="availabilityStyles[product.availability_status] || 'bg-surface-100 text-surface-600 ring-surface-200'">
                                        {{ availabilityLabels[product.availability_status] || product.availability_status }}
                                    </span>
                                    <span v-if="product.is_active" class="badge-admin bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">
                                        ظاهر
                                    </span>
                                    <span v-else class="badge-admin bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200">
                                        مخفي
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <template v-if="product.trashed">
                                        <button
                                            v-if="can('product.restore')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50"
                                            title="استعادة المنتج"
                                            :disabled="restoringId === product.id"
                                            @click="restoreProduct(product)"
                                        >
                                            <AdminIcon :name="restoringId === product.id ? 'refresh' : 'restore'" size="sm" />
                                        </button>
                                    </template>
                                    <template v-else>
                                        <Link
                                            :href="route('admin.products.show', product.id)"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                            title="عرض التفاصيل"
                                        >
                                            <AdminIcon name="eye" size="sm" />
                                        </Link>
                                        <Link
                                            v-if="can('product.edit')"
                                            :href="route('admin.products.edit', product.id)"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-sky-50 hover:text-sky-600"
                                            title="تعديل"
                                        >
                                            <AdminIcon name="edit" size="sm" />
                                        </Link>
                                        <button
                                            v-if="can('product.delete')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-red-50 hover:text-red-600"
                                            title="حذف"
                                            @click="openDelete(product)"
                                        >
                                            <AdminIcon name="trash" size="sm" />
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div v-if="products?.data?.length" class="divide-y divide-surface-100 lg:hidden">
                <article
                    v-for="product in products.data"
                    :key="product.id"
                    class="p-4"
                    :class="{ 'opacity-60': product.trashed }"
                >
                    <div class="flex items-start gap-3">
                        <span class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl bg-surface-100 text-surface-400 ring-1 ring-surface-200">
                            <img v-if="product.cover_thumb_url" :src="product.cover_thumb_url" :alt="product.name_ar" class="h-full w-full object-cover" loading="lazy" />
                            <AdminIcon v-else name="box" size="lg" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-1.5">
                                <h3 class="min-w-0 flex-1 text-sm font-semibold leading-5 text-surface-900">{{ product.name_ar }}</h3>
                                <AdminIcon v-if="product.is_featured" name="star" size="xs" class="mt-0.5 shrink-0 text-gold-500" />
                                <AdminIcon v-if="product.is_new" name="sparkles" size="xs" class="mt-0.5 shrink-0 text-brand-500" />
                                <AdminIcon v-if="product.trashed" name="trash" size="xs" class="mt-0.5 shrink-0 text-red-400" />
                            </div>

                            <p class="mt-1 text-sm font-bold tabular-nums text-surface-900">
                                {{ priceView(product).current }}
                                <span v-if="priceView(product).old" class="ms-1.5 text-xs font-medium text-surface-400 line-through">
                                    {{ priceView(product).old }}
                                </span>
                            </p>

                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="badge-admin ring-1 ring-inset" :class="availabilityStyles[product.availability_status] || 'bg-surface-100 text-surface-600 ring-surface-200'">
                                    {{ availabilityLabels[product.availability_status] || product.availability_status }}
                                </span>
                                <span v-if="product.is_active" class="badge-admin bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">ظاهر</span>
                                <span v-else class="badge-admin bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200">مخفي</span>
                                <span v-if="product.category" class="badge-admin bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200">
                                    <AdminIcon name="folder" size="xs" />
                                    {{ product.category.name_ar }}
                                </span>
                            </div>

                            <p v-if="product.sku" class="mt-1.5 truncate font-mono text-[11px] text-surface-400">SKU: {{ product.sku }}</p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-1 border-t border-surface-100 pt-3">
                        <template v-if="product.trashed">
                            <button
                                v-if="can('product.restore')"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50"
                                :disabled="restoringId === product.id"
                                @click="restoreProduct(product)"
                            >
                                <AdminIcon :name="restoringId === product.id ? 'refresh' : 'restore'" size="sm" :class="{ 'animate-spin': restoringId === product.id }" />
                                استعادة
                            </button>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('admin.products.show', product.id)"
                                class="grid h-10 w-10 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                aria-label="عرض التفاصيل"
                            >
                                <AdminIcon name="eye" size="sm" />
                            </Link>
                            <Link
                                v-if="can('product.edit')"
                                :href="route('admin.products.edit', product.id)"
                                class="grid h-10 w-10 place-items-center rounded-lg text-surface-500 transition hover:bg-sky-50 hover:text-sky-600"
                                aria-label="تعديل"
                            >
                                <AdminIcon name="edit" size="sm" />
                            </Link>
                            <button
                                v-if="can('product.delete')"
                                type="button"
                                class="grid h-10 w-10 place-items-center rounded-lg text-surface-500 transition hover:bg-red-50 hover:text-red-600"
                                aria-label="حذف"
                                @click="openDelete(product)"
                            >
                                <AdminIcon name="trash" size="sm" />
                            </button>
                        </template>
                    </div>
                </article>
            </div>

            <!-- Empty states -->
            <div v-if="!products?.data?.length" class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                    <AdminIcon :name="trashMode ? 'trash' : 'box'" size="xl" />
                </span>
                <h3 class="mt-4 text-sm font-bold text-surface-900">
                    {{ trashMode ? 'سلة المحذوفات فارغة' : 'لا توجد منتجات' }}
                </h3>
                <p class="mt-1 max-w-sm text-sm text-surface-400">
                    {{ trashMode ? 'المنتجات المحذوفة ستظهر هنا لإمكانية استعادتها.' : hasActiveFilters ? 'لم يتم العثور على منتجات مطابقة للفلاتر الحالية.' : 'ابدأ بإضافة أول منتج إلى متجرك.' }}
                </p>
                <div class="mt-5 flex items-center gap-3">
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                        @click="resetFilters"
                    >
                        <AdminIcon name="refresh" size="sm" />
                        مسح الفلاتر
                    </button>
                    <Link
                        v-if="!trashMode && can('product.create')"
                        :href="route('admin.products.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700"
                    >
                        <AdminIcon name="plus" size="sm" />
                        إضافة منتج
                    </Link>
                </div>
            </div>

            <!-- Footer / pagination -->
            <div v-if="products?.data?.length" class="flex flex-col items-center justify-between gap-4 border-t border-surface-100 px-5 py-4 sm:flex-row">
                <p class="text-xs text-surface-400">
                    عرض <span class="font-semibold text-surface-600">{{ products.from ?? 0 }}–{{ products.to ?? 0 }}</span>
                    من إجمالي <span class="font-semibold text-surface-600">{{ products.total ?? 0 }}</span> منتج
                </p>

                <nav v-if="products.last_page > 1" class="flex items-center gap-1">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!products.prev_page_url" aria-label="السابق" @click="goTo(products.prev_page_url)">
                        <AdminIcon name="chevron-right" size="sm" />
                    </button>
                    <template v-for="link in pageLinks()" :key="link.key ?? link.label">
                        <span v-if="link.ellipsis" class="px-1 text-surface-400">…</span>
                        <button
                            v-else
                            type="button"
                            class="h-9 min-w-9 rounded-lg px-2 text-sm font-semibold transition"
                            :class="link.active ? 'bg-brand-600 text-white shadow-gold' : 'text-surface-600 hover:bg-surface-100'"
                            @click="goTo(link.url)"
                        >
                            {{ link.label }}
                        </button>
                    </template>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!products.next_page_url" aria-label="التالي" @click="goTo(products.next_page_url)">
                        <AdminIcon name="chevron-left" size="sm" />
                    </button>
                </nav>
            </div>
        </section>

        <!-- Delete confirmation -->
        <Modal :show="!!confirmDelete" max-width="md" @close="confirmDelete = null">
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600">
                        <AdminIcon name="trash" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-surface-900">حذف المنتج</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم نقل «<span class="font-semibold text-surface-900">{{ confirmDelete?.name_ar }}</span>» إلى سلة المحذوفات.
                            يمكنك استعادته في أي وقت من تبويب «المحذوفات».
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                        @click="confirmDelete = null"
                    >
                        إلغاء
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-premium transition hover:bg-red-700 disabled:opacity-60"
                        :disabled="deletingId === confirmDelete?.id"
                        @click="deleteProduct"
                    >
                        <AdminIcon :name="deletingId === confirmDelete?.id ? 'refresh' : 'trash'" size="sm" />
                        {{ deletingId === confirmDelete?.id ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>