<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { compressImage } from '@/Composables/useImageCompression';

const props = defineProps({
    categories: Object,
    allCategories: { type: Array, default: () => [] },
    filters: Object,
    stats: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

// -------------------------------------------------------------- filter state
const trashMode = ref(!!props.filters?.trash);
const search = ref(props.filters?.search ?? '');
const parentFilter = ref(props.filters?.parent_id ?? '');

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

function applyFilters(overrides = {}) {
    router.get(
        route('admin.categories.index'),
        {
            ...(search.value ? { search: search.value } : {}),
            ...(parentFilter.value ? { parent_id: parentFilter.value } : {}),
            ...(trashMode.value ? { trash: 1 } : {}),
            page: 1,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters() {
    search.value = '';
    parentFilter.value = '';
    trashMode.value = false;
    clearTimeout(searchTimer);
    applyFilters();
}

const hasActiveFilters = computed(() => !!(search.value || parentFilter.value));

// ------------------------------------------------------------- stats
const statCards = computed(() => [
    {
        key: 'total',
        label: 'إجمالي التصنيفات',
        value: props.stats?.total ?? 0,
        icon: 'folder',
        tone: 'bg-brand-50 text-brand-600 group-hover:ring-brand-300',
    },
    {
        key: 'active',
        label: 'نشطة للعرض',
        value: props.stats?.active ?? 0,
        icon: 'check',
        tone: 'bg-emerald-50 text-emerald-600 group-hover:ring-emerald-300',
    },
    {
        key: 'inactive',
        label: 'مخفية',
        value: props.stats?.inactive ?? 0,
        icon: 'eye-off',
        tone: 'bg-surface-100 text-surface-600 group-hover:ring-surface-300',
    },
    {
        key: 'trashed',
        label: 'سلة المحذوفات',
        value: props.stats?.trashed ?? 0,
        icon: 'trash',
        tone: 'bg-red-50 text-red-600 group-hover:ring-red-300',
        href: route('admin.categories.index', { trash: 1 }),
    },
]);

// ------------------------------------------------------------- delete / restore
const confirmDelete = ref(null);
const deletingId = ref(null);

function openDelete(category) {
    confirmDelete.value = category;
}

function deleteCategory() {
    if (!confirmDelete.value) return;
    deletingId.value = confirmDelete.value.id;
    router.delete(route('admin.categories.destroy', confirmDelete.value.id), {
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

function restoreCategory(category) {
    restoringId.value = category.id;
    router.post(route('admin.categories.restore', category.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            restoringId.value = null;
        },
    });
}

// ------------------------------------------------------------- create / edit modal
const categoryForm = useForm({
    name_ar: '',
    name_en: '',
    slug: '',
    description_ar: '',
    description_en: '',
    parent_id: '',
    sort_order: 0,
    is_active: true,
    image: null,
});

const editingCategory = ref(null);
const categoryModalOpen = ref(false);
const newImageUrl = ref(null);

const imagePreview = computed(() => newImageUrl.value || editingCategory.value?.image_url || null);

function openCreate() {
    editingCategory.value = null;
    newImageUrl.value = null;
    categoryForm.clearErrors();
    categoryForm.defaults({
        name_ar: '', name_en: '', slug: '', description_ar: '', description_en: '',
        parent_id: '', sort_order: 0, is_active: true, image: null,
    });
    categoryForm.reset();
    categoryModalOpen.value = true;
}

function openEdit(category) {
    editingCategory.value = category;
    newImageUrl.value = null;
    categoryForm.clearErrors();
    categoryForm.defaults({
        name_ar: category.name_ar,
        name_en: category.name_en ?? '',
        slug: category.slug ?? '',
        description_ar: category.description_ar ?? '',
        description_en: category.description_en ?? '',
        parent_id: category.parent ? category.parent.id : '',
        sort_order: category.sort_order ?? 0,
        is_active: category.is_active,
        image: null,
    });
    categoryForm.reset();
    categoryModalOpen.value = true;
}

async function onImageChange(event) {
    const picked = event.target.files?.[0];
    if (!picked) return;
    // Shrink to the server's own ceiling before the upload starts.
    const file = await compressImage(picked);
    categoryForm.image = file;
    if (newImageUrl.value) URL.revokeObjectURL(newImageUrl.value);
    newImageUrl.value = URL.createObjectURL(file);
}

function descendantIds(startId) {
    const ids = new Set();
    const find = (pid) => {
        for (const c of props.allCategories || []) {
            if (!ids.has(c.id) && c.parent_id === pid) {
                ids.add(c.id);
                find(c.id);
            }
        }
    };
    find(startId);
    return ids;
}

const parentOptions = computed(() => {
    const exclude = new Set();
    if (editingCategory.value) {
        exclude.add(editingCategory.value.id);
        for (const id of descendantIds(editingCategory.value.id)) exclude.add(id);
    }
    return (props.allCategories || []).filter((c) => !c.deleted_at && !exclude.has(c.id));
});

function saveCategory() {
    categoryForm.transform((data) => {
        const clean = { ...data };
        for (const k of ['name_en', 'slug', 'description_ar', 'description_en']) {
            if (clean[k] === '') clean[k] = null;
        }
        clean.parent_id = clean.parent_id === '' ? null : clean.parent_id;
        clean.image = categoryForm.image;
        return clean;
    });

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            categoryModalOpen.value = false;
        },
    };

    if (editingCategory.value) {
        categoryForm.put(route('admin.categories.update', editingCategory.value.id), options);
    } else {
        categoryForm.post(route('admin.categories.store'), options);
    }
}

// ------------------------------------------------------------- pagination
function pageLinks() {
    const links = props.categories?.links ?? [];
    const pages = links.filter((l) => l.url && Number.isInteger(Number(l.label)));

    const current = props.categories?.current_page ?? 1;
    const lastPage = props.categories?.last_page ?? 1;
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

const filterParentOptions = computed(() =>
    (props.allCategories || []).filter((c) => !c.deleted_at),
);
</script>

<template>
    <Head title="التصنيفات" />

    <AdminLayout>
        <!-- Stats -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Link
                v-for="(card, i) in statCards"
                :key="card.key"
                :href="card.href || route('admin.categories.index')"
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
                <div class="inline-flex items-center gap-1 rounded-xl bg-surface-100 p-1">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                        :class="trashMode ? 'text-surface-500 hover:text-surface-700' : 'bg-white text-surface-900 shadow-sm ring-1 ring-black/5'"
                        @click="(trashMode = false), applyFilters()"
                    >
                        <AdminIcon name="folder" size="sm" />
                        التصنيفات
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
                        :placeholder="trashMode ? 'بحث في المحذوفات...' : 'بحث بالاسم أو الرابط...'"
                        class="input-admin ps-10"
                    />
                </div>

                <select v-model="parentFilter" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[160px]" @change="applyFilters()">
                    <option value="">كل المستويات</option>
                    <option value="0">رئيسية (بدون أب)</option>
                    <option v-for="c in filterParentOptions" :key="c.id" :value="c.id">{{ c.name_ar }}</option>
                </select>

                <button
                    v-if="can('category.create')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98]"
                    @click="openCreate"
                >
                    <AdminIcon name="plus" size="sm" />
                    إضافة تصنيف
                </button>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                    @click="resetFilters"
                >
                    <AdminIcon name="refresh" size="sm" />
                    مسح الفلاتر
                </button>
            </div>
        </section>

        <!-- Categories table -->
        <section class="card-admin mt-6 animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.1s' }">
            <div v-if="categories?.data?.length" class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-surface-100">
                    <thead class="bg-surface-50/70">
                        <tr>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">التصنيف</th>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">الأب</th>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">المنتجات</th>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">الحالة</th>
                            <th class="px-5 py-3.5 text-end text-xs font-bold uppercase tracking-wider text-surface-400">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        <tr
                            v-for="cat in categories.data"
                            :key="cat.id"
                            class="transition-colors hover:bg-surface-50/60"
                            :class="{ 'opacity-60': cat.trashed }"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div
                                        v-if="cat.image_thumb_url"
                                        class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200"
                                    >
                                        <img :src="cat.image_thumb_url" :alt="cat.name_ar" class="h-full w-full object-cover" loading="lazy" />
                                    </div>
                                    <div v-else class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-surface-200">
                                        <AdminIcon name="folder" size="lg" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate text-sm font-semibold text-surface-900">{{ cat.name_ar }}</span>
                                            <span
                                                v-if="cat.children_count"
                                                class="inline-flex items-center gap-1 rounded-full bg-surface-100 px-2 py-0.5 text-[11px] font-semibold text-surface-500"
                                                title="تصنيفات فرعية"
                                            >
                                                <AdminIcon name="folder" size="xs" />
                                                {{ cat.children_count }}
                                            </span>
                                            <AdminIcon v-if="cat.trashed" name="trash" size="xs" class="text-red-400" title="محذوف" />
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-surface-400">
                                            <span v-if="cat.name_en">{{ cat.name_en }} · </span>
                                            <span dir="ltr">/{{ cat.slug || '—' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span v-if="cat.parent" class="inline-flex items-center gap-1.5 rounded-full bg-surface-100 px-2.5 py-1 text-xs font-semibold text-surface-600">
                                    <AdminIcon name="chevron-right" size="xs" />
                                    {{ cat.parent.name_ar }}
                                </span>
                                <span v-else class="text-xs text-surface-400">رئيسي</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm font-bold tabular-nums text-surface-900">{{ cat.products_count }}</span>
                                <span class="ms-1 text-xs text-surface-400">منتج</span>
                            </td>
                            <td class="px-5 py-4">
                                <span v-if="cat.is_active" class="badge-admin bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">
                                    ظاهر
                                </span>
                                <span v-else class="badge-admin bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200">
                                    مخفي
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <template v-if="cat.trashed">
                                        <button
                                            v-if="can('category.edit')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50"
                                            title="استعادة التصنيف"
                                            :disabled="restoringId === cat.id"
                                            @click="restoreCategory(cat)"
                                        >
                                            <AdminIcon :name="restoringId === cat.id ? 'refresh' : 'restore'" size="sm" />
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            v-if="can('category.edit')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-sky-50 hover:text-sky-600"
                                            title="تعديل"
                                            @click="openEdit(cat)"
                                        >
                                            <AdminIcon name="edit" size="sm" />
                                        </button>
                                        <button
                                            v-if="can('category.delete')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-red-50 hover:text-red-600"
                                            title="حذف"
                                            @click="openDelete(cat)"
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
            <div v-if="categories?.data?.length" class="divide-y divide-surface-100 lg:hidden">
                <article
                    v-for="cat in categories.data"
                    :key="cat.id"
                    class="p-4"
                    :class="{ 'opacity-60': cat.trashed }"
                >
                    <div class="flex items-start gap-3">
                        <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-50 text-brand-600 ring-1 ring-surface-200">
                            <img v-if="cat.image_thumb_url" :src="cat.image_thumb_url" :alt="cat.name_ar" class="h-full w-full object-cover" loading="lazy" />
                            <AdminIcon v-else name="folder" size="lg" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-1.5">
                                <h3 class="min-w-0 flex-1 text-sm font-semibold leading-5 text-surface-900">{{ cat.name_ar }}</h3>
                                <AdminIcon v-if="cat.trashed" name="trash" size="xs" class="mt-0.5 shrink-0 text-red-400" />
                            </div>
                            <p class="mt-0.5 truncate text-xs text-surface-400" dir="ltr">/{{ cat.slug || '—' }}</p>

                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span v-if="cat.is_active" class="badge-admin bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">ظاهر</span>
                                <span v-else class="badge-admin bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200">مخفي</span>
                                <span class="badge-admin bg-surface-100 text-surface-600">
                                    <AdminIcon name="box" size="xs" />
                                    {{ cat.products_count }} منتج
                                </span>
                                <span v-if="cat.children_count" class="badge-admin bg-surface-100 text-surface-600">
                                    <AdminIcon name="folder" size="xs" />
                                    {{ cat.children_count }} فرعي
                                </span>
                            </div>

                            <p class="mt-1.5 text-xs text-surface-400">
                                <template v-if="cat.parent">ضمن: {{ cat.parent.name_ar }}</template>
                                <template v-else>تصنيف رئيسي</template>
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-1 border-t border-surface-100 pt-3">
                        <template v-if="cat.trashed">
                            <button
                                v-if="can('category.edit')"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50"
                                :disabled="restoringId === cat.id"
                                @click="restoreCategory(cat)"
                            >
                                <AdminIcon :name="restoringId === cat.id ? 'refresh' : 'restore'" size="sm" :class="{ 'animate-spin': restoringId === cat.id }" />
                                استعادة
                            </button>
                        </template>
                        <template v-else>
                            <button
                                v-if="can('category.edit')"
                                type="button"
                                class="grid h-10 w-10 place-items-center rounded-lg text-surface-500 transition hover:bg-sky-50 hover:text-sky-600"
                                aria-label="تعديل"
                                @click="openEdit(cat)"
                            >
                                <AdminIcon name="edit" size="sm" />
                            </button>
                            <button
                                v-if="can('category.delete')"
                                type="button"
                                class="grid h-10 w-10 place-items-center rounded-lg text-surface-500 transition hover:bg-red-50 hover:text-red-600"
                                aria-label="حذف"
                                @click="openDelete(cat)"
                            >
                                <AdminIcon name="trash" size="sm" />
                            </button>
                        </template>
                    </div>
                </article>
            </div>

            <!-- Empty states -->
            <div v-if="!categories?.data?.length" class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                    <AdminIcon :name="trashMode ? 'trash' : 'folder'" size="xl" />
                </span>
                <h3 class="mt-4 text-sm font-bold text-surface-900">
                    {{ trashMode ? 'سلة المحذوفات فارغة' : 'لا توجد تصنيفات' }}
                </h3>
                <p class="mt-1 max-w-sm text-sm text-surface-400">
                    {{ trashMode ? 'التصنيفات المحذوفة ستظهر هنا لإمكانية استعادتها.' : hasActiveFilters ? 'لم يتم العثور على تصنيفات مطابقة للفلاتر الحالية.' : 'نظّم متجرك بإضافة تصنيفات للمنتجات.' }}
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
                    <button
                        v-if="!trashMode && can('category.create')"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700"
                        @click="openCreate"
                    >
                        <AdminIcon name="plus" size="sm" />
                        إضافة تصنيف
                    </button>
                </div>
            </div>

            <!-- Footer / pagination -->
            <div v-if="categories?.data?.length" class="flex flex-col items-center justify-between gap-4 border-t border-surface-100 px-5 py-4 sm:flex-row">
                <p class="text-xs text-surface-400">
                    عرض <span class="font-semibold text-surface-600">{{ categories.from ?? 0 }}–{{ categories.to ?? 0 }}</span>
                    من إجمالي <span class="font-semibold text-surface-600">{{ categories.total ?? 0 }}</span> تصنيف
                </p>

                <nav v-if="categories.last_page > 1" class="flex items-center gap-1">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!categories.prev_page_url" aria-label="السابق" @click="goTo(categories.prev_page_url)">
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
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!categories.next_page_url" aria-label="التالي" @click="goTo(categories.next_page_url)">
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
                        <h3 class="text-base font-bold text-surface-900">حذف التصنيف</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم نقل «<span class="font-semibold text-surface-900">{{ confirmDelete?.name_ar }}</span>» إلى سلة المحذوفات.
                            يمكنك استعادته في أي وقت من تبويب «المحذوفات».
                        </p>
                        <p v-if="confirmDelete?.children_count" class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                            يحتوي على {{ confirmDelete?.children_count }} تصنيف فرعي ولا يمكن حذفه إلا بعد نقلها أو حذفها.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                        @click="confirmDelete = null"
                    >
                        إلغاء
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-premium transition hover:bg-red-700 disabled:opacity-60"
                        :disabled="deletingId === confirmDelete?.id"
                        @click="deleteCategory"
                    >
                        <AdminIcon :name="deletingId === confirmDelete?.id ? 'refresh' : 'trash'" size="sm" />
                        {{ deletingId === confirmDelete?.id ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Create / edit modal -->
        <Modal :show="categoryModalOpen" max-width="lg" @close="categoryModalOpen = false">
            <div class="max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-surface-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon name="folder" size="sm" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-surface-900">
                                {{ editingCategory ? 'تعديل التصنيف' : 'إضافة تصنيف جديد' }}
                            </h3>
                            <p class="text-xs text-surface-400">
                                {{ editingCategory ? `تعديل «${editingCategory.name_ar}»` : 'أدخل بيانات التصنيف ثم احفظه.' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-surface-100 hover:text-surface-700" aria-label="إغلاق" @click="categoryModalOpen = false">
                        <AdminIcon name="close" size="sm" />
                    </button>
                </div>

                <form id="category-form" class="p-6" @submit.prevent="saveCategory" novalidate>
                    <div v-if="categoryForm.errors.error" class="mb-4 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <AdminIcon name="alert" size="sm" />
                        {{ categoryForm.errors.error }}
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">
                                الاسم بالعربية <span class="text-red-500">*</span>
                            </label>
                            <input v-model="categoryForm.name_ar" type="text" class="input-admin" placeholder="مثال: كنب" />
                            <InputError :message="categoryForm.errors.name_ar" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">الاسم بالإنجليزية</label>
                            <input v-model="categoryForm.name_en" type="text" class="input-admin" placeholder="e.g. Sofas" />
                            <InputError :message="categoryForm.errors.name_en" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">الرابط (SLUG)</label>
                            <input v-model="categoryForm.slug" type="text" class="input-admin" dir="ltr" placeholder="يُولَّد تلقائياً" />
                            <InputError :message="categoryForm.errors.slug" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">التصنيف الأب</label>
                            <select v-model="categoryForm.parent_id" class="input-admin cursor-pointer">
                                <option value="">بدون أب (تصنيف رئيسي)</option>
                                <option v-for="c in parentOptions" :key="c.id" :value="c.id">
                                    {{ c.name_ar }}
                                    <template v-if="c.name_en"> ({{ c.name_en }})</template>
                                </option>
                            </select>
                            <InputError :message="categoryForm.errors.parent_id" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">ترتيب العرض</label>
                            <input v-model="categoryForm.sort_order" type="number" min="0" class="input-admin" />
                            <InputError :message="categoryForm.errors.sort_order" class="mt-1.5" />
                        </div>
                        <div class="flex items-end pb-1.5">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2.5 text-sm font-medium text-surface-700"
                                @click="categoryForm.is_active = !categoryForm.is_active"
                            >
                                <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition" :class="categoryForm.is_active ? 'bg-emerald-500' : 'bg-surface-300'">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="categoryForm.is_active ? 'translate-x-1' : 'translate-x-[26px]'"></span>
                                </span>
                                <span class="inline-flex items-center gap-1.5" :class="categoryForm.is_active ? 'text-emerald-700' : 'text-surface-400'">
                                    <AdminIcon name="eye" size="sm" />
                                    ظاهر للعرض
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف (عربي)</label>
                            <textarea v-model="categoryForm.description_ar" rows="3" class="input-admin resize-none" placeholder="وصف التصنيف..."></textarea>
                            <InputError :message="categoryForm.errors.description_ar" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف (إنجليزي)</label>
                            <textarea v-model="categoryForm.description_en" rows="3" class="input-admin resize-none" placeholder="Category description..."></textarea>
                            <InputError :message="categoryForm.errors.description_en" class="mt-1.5" />
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="grid h-28 w-full shrink-0 place-items-center overflow-hidden rounded-2xl bg-surface-100 ring-1 ring-surface-200 sm:w-44">
                            <img v-if="imagePreview" :src="imagePreview" alt="معاينة الصورة" class="h-full w-full object-cover" />
                            <span v-else class="flex flex-col items-center gap-1 text-xs text-surface-400">
                                <AdminIcon name="image" size="lg" />
                                بدون صورة
                            </span>
                        </div>
                        <div class="flex-1">
                            <label for="category-image" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-surface-300 bg-surface-50 px-4 py-2.5 text-sm font-semibold text-surface-600 transition hover:border-brand-400 hover:text-brand-600">
                                <AdminIcon name="plus" size="sm" />
                                {{ imagePreview ? 'استبدال الصورة' : 'اختيار صورة' }}
                            </label>
                            <input id="category-image" type="file" accept="image/jpeg,image/png,image/webp,image/avif" class="sr-only" @change="onImageChange" />
                            <p class="mt-2 text-xs text-surface-400">صيغ JPG أو PNG أو WebP — بحد أقصى 5MB.</p>
                            <InputError :message="categoryForm.errors.image" class="mt-1.5" />
                        </div>
                    </div>
                </form>

                <div class="flex items-center justify-end gap-3 border-t border-surface-100 px-6 py-4">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                        @click="categoryModalOpen = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        form="category-form"
                        :disabled="categoryForm.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                    >
                        <AdminIcon :name="categoryForm.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': categoryForm.processing }" />
                        {{ categoryForm.processing ? 'جارٍ الحفظ...' : editingCategory ? 'حفظ التعديلات' : 'إضافة التصنيف' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>