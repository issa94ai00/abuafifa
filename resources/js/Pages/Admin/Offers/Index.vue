<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import AdminToast from '@/Components/Admin/AdminToast.vue';
import OfferFormModal from '@/Components/Admin/OfferFormModal.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    offers: Object,
    stats: Object,
    filters: Object,
    products: { type: Array, default: () => [] },
    currency: { type: String, default: 'د.أ' },
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

// -------------------------------------------------------------- filter state
const trashMode = ref(!!props.filters?.trash);
const search = ref(props.filters?.search ?? '');
const state = ref(props.filters?.state ?? '');
const sort = ref(props.filters?.sort ?? 'latest');
const perPage = ref(props.filters?.per_page ?? 12);

const searchInput = ref(null);
const busy = ref(false);

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

// Keep the controls in sync when the user navigates back/forward, since
// Inertia reuses this component instead of remounting it.
watch(
    () => props.filters,
    (filters) => {
        trashMode.value = !!filters?.trash;
        search.value = filters?.search ?? '';
        state.value = filters?.state ?? '';
        sort.value = filters?.sort ?? 'latest';
        perPage.value = filters?.per_page ?? 12;
    },
);

function applyFilters(overrides = {}) {
    router.get(
        route('admin.offers.index'),
        {
            ...(search.value ? { search: search.value } : {}),
            ...(!trashMode.value && state.value ? { state: state.value } : {}),
            ...(trashMode.value ? { trash: 1 } : {}),
            ...(sort.value !== 'latest' ? { sort: sort.value } : {}),
            ...(Number(perPage.value) !== 12 ? { per_page: perPage.value } : {}),
            page: 1,
            ...overrides,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => (busy.value = true),
            onFinish: () => (busy.value = false),
        },
    );
}

function setTrashMode(value) {
    if (trashMode.value === value) return;
    trashMode.value = value;
    // The state filter describes live offers only; it is meaningless in trash.
    if (value) state.value = '';
    applyFilters();
}

function setState(value) {
    // Clicking the active card again clears the filter.
    state.value = state.value === value ? '' : value;
    trashMode.value = false;
    applyFilters();
}

function resetFilters() {
    search.value = '';
    state.value = '';
    sort.value = 'latest';
    perPage.value = 12;
    trashMode.value = false;
    clearTimeout(searchTimer);
    applyFilters();
}

const hasActiveFilters = computed(
    () => !!(search.value || state.value || sort.value !== 'latest' || Number(perPage.value) !== 12),
);

// "/" focuses the search box, the shortcut admins expect from a list screen.
function onKeydown(event) {
    if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) return;

    const tag = event.target?.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || event.target?.isContentEditable) return;

    event.preventDefault();
    searchInput.value?.focus();
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    clearTimeout(searchTimer);
});

// --------------------------------------------------------------- create/edit
const formOpen = ref(false);
const editingOffer = ref(null);

function openCreate() {
    editingOffer.value = null;
    formOpen.value = true;
}

function openEdit(offer) {
    editingOffer.value = offer;
    formOpen.value = true;
}

function closeForm() {
    formOpen.value = false;
    editingOffer.value = null;
}

// ------------------------------------------------------------- delete/restore
const confirmDelete = ref(null);
const deletingId = ref(null);
const restoringId = ref(null);
const togglingId = ref(null);

function deleteOffer() {
    if (!confirmDelete.value) return;
    deletingId.value = confirmDelete.value.id;
    router.delete(route('admin.offers.destroy', confirmDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => (confirmDelete.value = null),
        onFinish: () => (deletingId.value = null),
    });
}

function restoreOffer(offer) {
    restoringId.value = offer.id;
    router.post(route('admin.offers.restore', offer.id), {}, {
        preserveScroll: true,
        onFinish: () => (restoringId.value = null),
    });
}

function toggleActive(offer) {
    togglingId.value = offer.id;
    router.patch(route('admin.offers.toggle-active', offer.id), {}, {
        preserveScroll: true,
        onFinish: () => (togglingId.value = null),
    });
}

// ------------------------------------------------------------------ pagination
function pageLinks() {
    const links = props.offers?.links ?? [];
    const pages = links.filter((l) => l.url && Number.isInteger(Number(l.label)));

    const current = props.offers?.current_page ?? 1;
    const lastPage = props.offers?.last_page ?? 1;
    const around = 1;

    let first = Number(pages[0]?.label ?? 1);
    let last = Number(pages[pages.length - 1]?.label ?? lastPage);

    first = Math.max(first, current - around);
    last = Math.min(last, current + around);

    const visible = pages.filter((l) => {
        const n = Number(l.label);
        return n === 1 || n === lastPage || n === current || (n >= first && n <= last);
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

// --------------------------------------------------------------------- display
const STATES = {
    active: { label: 'نشط الآن', icon: 'check', cls: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
    upcoming: { label: 'قادم', icon: 'clock', cls: 'bg-amber-50 text-amber-700 ring-amber-200' },
    ended: { label: 'منتهي', icon: 'calendar', cls: 'bg-surface-100 text-surface-500 ring-surface-200' },
    hidden: { label: 'مخفي', icon: 'eye-off', cls: 'bg-surface-100 text-surface-600 ring-surface-200' },
};

function stateOf(offer) {
    return STATES[offer.state] ?? STATES.hidden;
}

const statCards = computed(() => [
    { key: '', label: 'إجمالي العروض', value: props.stats?.total ?? 0, icon: 'tag', tone: 'bg-brand-50 text-brand-600', ring: 'ring-brand-400' },
    { key: 'active', label: 'نشطة الآن', value: props.stats?.active ?? 0, icon: 'check', tone: 'bg-emerald-50 text-emerald-600', ring: 'ring-emerald-400' },
    { key: 'upcoming', label: 'قادمة', value: props.stats?.upcoming ?? 0, icon: 'clock', tone: 'bg-amber-50 text-amber-600', ring: 'ring-amber-400' },
    { key: 'ended', label: 'منتهية', value: props.stats?.ended ?? 0, icon: 'calendar', tone: 'bg-surface-100 text-surface-500', ring: 'ring-surface-400' },
    { key: 'hidden', label: 'مخفية', value: props.stats?.hidden ?? 0, icon: 'eye-off', tone: 'bg-surface-100 text-surface-600', ring: 'ring-surface-400' },
]);

function isCardActive(key) {
    if (trashMode.value) return false;
    return key === '' ? state.value === '' : state.value === key;
}

const dateFormatter = new Intl.DateTimeFormat('ar-JO', { year: 'numeric', month: 'short', day: 'numeric' });

function formatDate(value) {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : dateFormatter.format(date);
}

function formatMoney(value) {
    if (value === null || value === undefined) return '—';
    return `${new Intl.NumberFormat('ar-JO', { maximumFractionDigits: 2 }).format(value)} ${props.currency}`;
}

/** Human summary of the offer window, e.g. "يبقى 12 يوم". */
function windowLabel(offer) {
    const now = Date.now();
    const start = offer.starts_at ? new Date(offer.starts_at).getTime() : null;
    const end = offer.ends_at ? new Date(offer.ends_at).getTime() : null;

    if (!start && !end) return 'دائم';

    const days = (ms) => Math.max(0, Math.ceil(ms / 86_400_000));

    if (start && start > now) return `يبدأ بعد ${days(start - now)} يوم`;
    if (end && end < now) return `انتهى منذ ${days(now - end)} يوم`;
    if (end) return `يبقى ${days(end - now)} يوم`;

    return 'بدون تاريخ انتهاء';
}

/** 0–100 progress through the offer window, or null when it has no bounds. */
function windowProgress(offer) {
    if (offer.state !== 'active' || !offer.starts_at || !offer.ends_at) return null;

    const start = new Date(offer.starts_at).getTime();
    const end = new Date(offer.ends_at).getTime();
    if (!(end > start)) return null;

    return Math.min(100, Math.max(0, ((Date.now() - start) / (end - start)) * 100));
}

function discountLabel(offer) {
    if (offer.discount_type === 'custom_price') {
        return offer.price_override !== null ? formatMoney(offer.price_override) : 'سعر خاص';
    }
    return offer.badge_label ?? '—';
}

const emptyMessage = computed(() => {
    if (trashMode.value) return 'العروض المحذوفة تظهر هنا ويمكن استعادتها في أي وقت.';
    if (hasActiveFilters.value) return 'لم يتم العثور على عروض مطابقة للفلاتر الحالية.';
    return 'ابدأ بإضافة أول عرض ليظهر في صفحة العروض بالموقع.';
});
</script>

<template>
    <Head title="العروض" />

    <AdminLayout>
        <template #header>
            <p class="text-[11px] font-medium leading-4 text-surface-400">لوحة التحكم</p>
            <h1 class="truncate text-base font-bold text-surface-900 sm:text-lg">العروض</h1>
        </template>

        <!-- Stats / quick filters -->
        <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            <button
                v-for="(card, i) in statCards"
                :key="card.key || 'all'"
                type="button"
                class="card-admin group animate-fade-in-up p-4 text-start transition-all duration-300 hover:-translate-y-0.5 hover:shadow-premium-lg"
                :class="isCardActive(card.key) ? `ring-2 ${card.ring}` : ''"
                :aria-pressed="isCardActive(card.key)"
                :style="{ animationDelay: `${i * 0.04}s` }"
                @click="setState(card.key)"
            >
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl transition" :class="card.tone">
                        <AdminIcon :name="card.icon" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <span class="block text-2xl font-bold tabular-nums text-surface-900">{{ card.value }}</span>
                        <span class="block truncate text-xs font-medium text-surface-400">{{ card.label }}</span>
                    </div>
                </div>
            </button>
        </section>

        <!-- Toolbar -->
        <section class="card-admin mt-5 animate-fade-in-up p-4 sm:p-5" :style="{ animationDelay: '0.05s' }">
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex items-center gap-1 rounded-xl bg-surface-100 p-1">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                        :class="trashMode ? 'text-surface-500 hover:text-surface-700' : 'bg-white text-surface-900 shadow-sm ring-1 ring-black/5'"
                        @click="setTrashMode(false)"
                    >
                        <AdminIcon name="tag" size="sm" />
                        العروض
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                        :class="trashMode ? 'bg-white text-surface-900 shadow-sm ring-1 ring-black/5' : 'text-surface-500 hover:text-surface-700'"
                        @click="setTrashMode(true)"
                    >
                        <AdminIcon name="trash" size="sm" />
                        المحذوفات
                        <span v-if="stats?.trashed" class="inline-flex min-w-[18px] items-center justify-center rounded-full bg-red-100 px-1.5 text-[11px] font-bold text-red-700">
                            {{ stats.trashed }}
                        </span>
                    </button>
                </div>

                <div class="relative min-w-[200px] flex-1">
                    <AdminIcon name="search" size="sm" class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-surface-400" />
                    <input
                        ref="searchInput"
                        v-model="search"
                        type="search"
                        :placeholder="trashMode ? 'بحث في المحذوفات...' : 'بحث بالعنوان أو الرابط...'"
                        class="input-admin px-10"
                        aria-label="بحث"
                    />
                    <kbd class="pointer-events-none absolute end-3.5 top-1/2 hidden -translate-y-1/2 rounded border border-surface-200 bg-surface-50 px-1.5 text-[11px] font-semibold text-surface-400 sm:block">/</kbd>
                </div>

                <select v-model="sort" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[150px]" aria-label="ترتيب" @change="applyFilters()">
                    <option value="latest">الأحدث أولاً</option>
                    <option value="oldest">الأقدم أولاً</option>
                    <option value="title">حسب العنوان</option>
                    <option value="order">حسب ترتيب العرض</option>
                    <option value="ending">الأقرب انتهاءً</option>
                </select>

                <select v-model="perPage" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[110px]" aria-label="عدد الصفوف" @change="applyFilters()">
                    <option :value="12">12 / صفحة</option>
                    <option :value="24">24 / صفحة</option>
                    <option :value="48">48 / صفحة</option>
                    <option :value="96">96 / صفحة</option>
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

                <button
                    v-if="can('offer.create') && !trashMode"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98]"
                    @click="openCreate"
                >
                    <AdminIcon name="plus" size="sm" />
                    إضافة عرض
                </button>
            </div>
        </section>

        <!-- List -->
        <section class="card-admin relative mt-5 animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.1s' }">
            <div v-if="busy" class="absolute inset-x-0 top-0 z-10 h-0.5 overflow-hidden bg-brand-100">
                <div class="h-full w-1/3 animate-pulse bg-brand-600"></div>
            </div>

            <!-- Desktop table -->
            <div v-if="offers?.data?.length" class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-surface-100">
                    <thead class="bg-surface-50/70">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">العرض</th>
                            <th scope="col" class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">الحالة</th>
                            <th scope="col" class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">الخصم</th>
                            <th scope="col" class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">المدة</th>
                            <th scope="col" class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">المنتجات</th>
                            <th scope="col" class="px-5 py-3.5 text-end text-xs font-bold uppercase tracking-wider text-surface-400">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        <tr
                            v-for="offer in offers.data"
                            :key="offer.id"
                            class="group transition-colors hover:bg-surface-50/60"
                            :class="{ 'opacity-60': offer.trashed }"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3.5">
                                    <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-surface-100 text-surface-400 ring-1 ring-surface-200">
                                        <img v-if="offer.image_thumb_url" :src="offer.image_thumb_url" :alt="offer.title_ar" class="h-full w-full object-cover" loading="lazy" />
                                        <AdminIcon v-else name="tag" size="lg" />
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="truncate text-sm font-semibold text-surface-900">{{ offer.title_ar }}</span>
                                            <AdminIcon v-if="offer.trashed" name="trash" size="xs" class="text-red-400" />
                                        </div>
                                        <p v-if="offer.title_en" class="mt-0.5 truncate text-xs text-surface-400" dir="ltr">{{ offer.title_en }}</p>
                                        <p v-else-if="offer.slug" class="mt-0.5 truncate text-xs text-surface-300" dir="ltr">/{{ offer.slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="badge-admin ring-1 ring-inset" :class="stateOf(offer).cls">
                                    <AdminIcon :name="stateOf(offer).icon" size="xs" />
                                    {{ stateOf(offer).label }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-bold tabular-nums text-surface-900">{{ discountLabel(offer) }}</div>
                                <div class="mt-0.5 text-xs text-surface-400">{{ offer.discount_type_label }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm tabular-nums text-surface-700">
                                    {{ formatDate(offer.starts_at) ?? 'بلا بداية' }}
                                    <span class="text-surface-300">←</span>
                                    {{ formatDate(offer.ends_at) ?? 'بلا نهاية' }}
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="text-xs text-surface-400">{{ windowLabel(offer) }}</span>
                                    <span v-if="windowProgress(offer) !== null" class="h-1 w-16 overflow-hidden rounded-full bg-surface-200">
                                        <span class="block h-full rounded-full bg-emerald-500" :style="{ width: `${windowProgress(offer)}%` }"></span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                    :class="offer.products_count ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-surface-50 text-surface-400 ring-surface-200'"
                                >
                                    <AdminIcon name="box" size="xs" />
                                    {{ offer.products_count }} منتج
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <template v-if="offer.trashed">
                                        <button
                                            v-if="can('offer.edit')"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50"
                                            :disabled="restoringId === offer.id"
                                            @click="restoreOffer(offer)"
                                        >
                                            <AdminIcon :name="restoringId === offer.id ? 'refresh' : 'restore'" size="sm" :class="{ 'animate-spin': restoringId === offer.id }" />
                                            استعادة
                                        </button>
                                        <span v-else class="text-xs text-surface-400">—</span>
                                    </template>
                                    <template v-else>
                                        <button
                                            v-if="can('offer.edit')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg transition disabled:opacity-50"
                                            :class="offer.is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-surface-400 hover:bg-surface-100'"
                                            :title="offer.is_active ? 'إخفاء العرض' : 'إظهار العرض'"
                                            :disabled="togglingId === offer.id"
                                            @click="toggleActive(offer)"
                                        >
                                            <AdminIcon :name="togglingId === offer.id ? 'refresh' : offer.is_active ? 'eye' : 'eye-off'" size="sm" :class="{ 'animate-spin': togglingId === offer.id }" />
                                        </button>
                                        <button
                                            v-if="can('offer.edit')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                            title="تعديل العرض"
                                            @click="openEdit(offer)"
                                        >
                                            <AdminIcon name="edit" size="sm" />
                                        </button>
                                        <Link
                                            :href="route('admin.offers.show', offer.id)"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                            title="عرض التفاصيل"
                                        >
                                            <AdminIcon name="eye" size="sm" />
                                        </Link>
                                        <button
                                            v-if="can('offer.delete')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-red-50 hover:text-red-600"
                                            title="حذف العرض"
                                            @click="confirmDelete = offer"
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
            <div v-if="offers?.data?.length" class="divide-y divide-surface-100 lg:hidden">
                <article
                    v-for="offer in offers.data"
                    :key="offer.id"
                    class="p-4"
                    :class="{ 'opacity-60': offer.trashed }"
                >
                    <div class="flex items-start gap-3">
                        <span class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl bg-surface-100 text-surface-400 ring-1 ring-surface-200">
                            <img v-if="offer.image_thumb_url" :src="offer.image_thumb_url" :alt="offer.title_ar" class="h-full w-full object-cover" loading="lazy" />
                            <AdminIcon v-else name="tag" size="lg" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="min-w-0 truncate text-sm font-semibold text-surface-900">{{ offer.title_ar }}</h3>
                                <span class="badge-admin shrink-0 ring-1 ring-inset" :class="stateOf(offer).cls">
                                    <AdminIcon :name="stateOf(offer).icon" size="xs" />
                                    {{ stateOf(offer).label }}
                                </span>
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-surface-500">
                                <span class="font-bold text-surface-900">{{ discountLabel(offer) }}</span>
                                <span class="inline-flex items-center gap-1">
                                    <AdminIcon name="box" size="xs" />
                                    {{ offer.products_count }} منتج
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <AdminIcon name="clock" size="xs" />
                                    {{ windowLabel(offer) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-1 border-t border-surface-100 pt-3">
                        <template v-if="offer.trashed">
                            <button
                                v-if="can('offer.edit')"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50"
                                :disabled="restoringId === offer.id"
                                @click="restoreOffer(offer)"
                            >
                                <AdminIcon :name="restoringId === offer.id ? 'refresh' : 'restore'" size="sm" :class="{ 'animate-spin': restoringId === offer.id }" />
                                استعادة
                            </button>
                        </template>
                        <template v-else>
                            <button
                                v-if="can('offer.edit')"
                                type="button"
                                class="grid h-9 w-9 place-items-center rounded-lg transition disabled:opacity-50"
                                :class="offer.is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-surface-400 hover:bg-surface-100'"
                                :aria-label="offer.is_active ? 'إخفاء العرض' : 'إظهار العرض'"
                                :disabled="togglingId === offer.id"
                                @click="toggleActive(offer)"
                            >
                                <AdminIcon :name="togglingId === offer.id ? 'refresh' : offer.is_active ? 'eye' : 'eye-off'" size="sm" :class="{ 'animate-spin': togglingId === offer.id }" />
                            </button>
                            <button
                                v-if="can('offer.edit')"
                                type="button"
                                class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                aria-label="تعديل العرض"
                                @click="openEdit(offer)"
                            >
                                <AdminIcon name="edit" size="sm" />
                            </button>
                            <Link
                                :href="route('admin.offers.show', offer.id)"
                                class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                aria-label="عرض التفاصيل"
                            >
                                <AdminIcon name="eye" size="sm" />
                            </Link>
                            <button
                                v-if="can('offer.delete')"
                                type="button"
                                class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-red-50 hover:text-red-600"
                                aria-label="حذف العرض"
                                @click="confirmDelete = offer"
                            >
                                <AdminIcon name="trash" size="sm" />
                            </button>
                        </template>
                    </div>
                </article>
            </div>

            <!-- Empty state -->
            <div v-if="!offers?.data?.length" class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                    <AdminIcon :name="trashMode ? 'trash' : 'tag'" size="xl" />
                </span>
                <h3 class="mt-4 text-sm font-bold text-surface-900">
                    {{ trashMode ? 'سلة المحذوفات فارغة' : hasActiveFilters ? 'لا نتائج' : 'لا توجد عروض بعد' }}
                </h3>
                <p class="mt-1 max-w-sm text-sm text-surface-400">{{ emptyMessage }}</p>
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
                    <button
                        v-if="!trashMode && !hasActiveFilters && can('offer.create')"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700"
                        @click="openCreate"
                    >
                        <AdminIcon name="plus" size="sm" />
                        إضافة أول عرض
                    </button>
                </div>
            </div>

            <!-- Footer / pagination -->
            <div v-if="offers?.data?.length" class="flex flex-col items-center justify-between gap-4 border-t border-surface-100 px-5 py-4 sm:flex-row">
                <p class="text-xs text-surface-400">
                    عرض <span class="font-semibold text-surface-600">{{ offers.from ?? 0 }}–{{ offers.to ?? 0 }}</span>
                    من إجمالي <span class="font-semibold text-surface-600">{{ offers.total ?? 0 }}</span> عرض
                </p>

                <nav v-if="offers.last_page > 1" class="flex items-center gap-1" aria-label="تنقل بين الصفحات">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!offers.prev_page_url" aria-label="السابق" @click="goTo(offers.prev_page_url)">
                        <AdminIcon name="chevron-right" size="sm" />
                    </button>
                    <template v-for="link in pageLinks()" :key="link.key ?? link.label">
                        <span v-if="link.ellipsis" class="px-1 text-surface-400">…</span>
                        <button
                            v-else
                            type="button"
                            class="h-9 min-w-9 rounded-lg px-2 text-sm font-semibold transition"
                            :class="link.active ? 'bg-brand-600 text-white shadow-gold' : 'text-surface-600 hover:bg-surface-100'"
                            :aria-current="link.active ? 'page' : undefined"
                            @click="goTo(link.url)"
                        >
                            {{ link.label }}
                        </button>
                    </template>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!offers.next_page_url" aria-label="التالي" @click="goTo(offers.next_page_url)">
                        <AdminIcon name="chevron-left" size="sm" />
                    </button>
                </nav>
            </div>
        </section>

        <!-- Create / edit -->
        <OfferFormModal
            :show="formOpen"
            :offer="editingOffer"
            :products="products"
            :currency="currency"
            @close="closeForm"
        />

        <!-- Delete confirmation -->
        <Modal :show="!!confirmDelete" max-width="md" @close="confirmDelete = null">
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600">
                        <AdminIcon name="trash" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-surface-900">حذف العرض</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم نقل «<span class="font-semibold text-surface-900">{{ confirmDelete?.title_ar }}</span>» إلى سلة المحذوفات
                            ولن يظهر في الموقع. يمكنك استعادته في أي وقت من تبويب «المحذوفات».
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
                        @click="deleteOffer"
                    >
                        <AdminIcon :name="deletingId === confirmDelete?.id ? 'refresh' : 'trash'" size="sm" :class="{ 'animate-spin': deletingId === confirmDelete?.id }" />
                        {{ deletingId === confirmDelete?.id ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>

        <AdminToast />
    </AdminLayout>
</template>
