<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import ImageDropzone from '@/Components/Admin/ImageDropzone.vue';

const props = defineProps({
    galleries: Object,
    stats: Object,
    filters: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

// -------------------------------------------------------------- filter state
const trashMode = ref(!!props.filters?.trash);
const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

function applyFilters(overrides = {}) {
    router.get(
        route('admin.galleries.index'),
        {
            ...(search.value ? { search: search.value } : {}),
            ...(status.value !== '' ? { status: status.value } : {}),
            ...(trashMode.value ? { trash: 1 } : {}),
            page: 1,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters() {
    search.value = '';
    status.value = '';
    trashMode.value = false;
    clearTimeout(searchTimer);
    applyFilters();
}

const hasActiveFilters = computed(() => !!(search.value || status.value !== ''));

// ------------------------------------------------------------------ stats
const statCards = computed(() => [
    {
        key: 'total',
        label: 'إجمالي المعارض',
        value: props.stats?.total ?? 0,
        icon: 'collection',
        tone: 'bg-brand-50 text-brand-600 group-hover:ring-brand-300',
    },
    {
        key: 'active',
        label: 'ظاهرة للعرض',
        value: props.stats?.active ?? 0,
        icon: 'check',
        tone: 'bg-emerald-50 text-emerald-600 group-hover:ring-emerald-300',
    },
    {
        key: 'hidden',
        label: 'مخفية',
        value: props.stats?.hidden ?? 0,
        icon: 'eye-off',
        tone: 'bg-surface-100 text-surface-600 group-hover:ring-surface-300',
    },
    {
        key: 'trashed',
        label: 'سلة المحذوفات',
        value: props.stats?.trashed ?? 0,
        icon: 'trash',
        tone: 'bg-red-50 text-red-600 group-hover:ring-red-300',
        href: route('admin.galleries.index', { trash: 1 }),
    },
]);

// ------------------------------------------------------------- delete / restore
const confirmDelete = ref(null);
const deletingId = ref(null);

function openDelete(gallery) {
    confirmDelete.value = gallery;
}

function deleteGallery() {
    if (!confirmDelete.value) return;
    deletingId.value = confirmDelete.value.id;
    router.delete(route('admin.galleries.destroy', confirmDelete.value.id), {
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

function restoreGallery(gallery) {
    restoringId.value = gallery.id;
    router.post(route('admin.galleries.restore', gallery.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            restoringId.value = null;
        },
    });
}

// ------------------------------------------------------------- create / edit modal
const galleryForm = useForm({
    title_ar: '',
    title_en: '',
    slug: '',
    description_ar: '',
    description_en: '',
    sort_order: 0,
    is_active: true,
    cover_image: null,
    images: [],
});

const editingGallery = ref(null);
const galleryModalOpen = ref(false);


// Only the stored cover; anything newly picked is previewed by the dropzone.
const storedCover = computed(() =>
    galleryForm.remove_cover ? null : editingGallery.value?.cover_thumb_url ?? null,
);

function onCoverPicked(file) {
    galleryForm.cover_image = file;
    if (file) galleryForm.remove_cover = false;
}

function onRemoveStoredCover() {
    galleryForm.cover_image = null;
    galleryForm.remove_cover = !!editingGallery.value;
}

function openCreate() {
    editingGallery.value = null;
    galleryForm.clearErrors();
    galleryForm.defaults({
        title_ar: '', title_en: '', slug: '', description_ar: '', description_en: '',
        sort_order: 0, is_active: true, cover_image: null, images: [],
    });
    galleryForm.reset();
    galleryModalOpen.value = true;
}

function openEdit(gallery) {
    editingGallery.value = gallery;
    galleryForm.clearErrors();
    galleryForm.defaults({
        title_ar: gallery.title_ar,
        title_en: gallery.title_en ?? '',
        slug: gallery.slug ?? '',
        description_ar: gallery.description_ar ?? '',
        description_en: gallery.description_en ?? '',
        sort_order: gallery.sort_order ?? 0,
        is_active: gallery.is_active,
        cover_image: null,
        images: [],
    });
    galleryForm.reset();
    galleryModalOpen.value = true;
}

function saveGallery() {
    galleryForm.transform((data) => {
        const clean = { ...data };
        for (const k of ['title_en', 'slug', 'description_ar', 'description_en']) {
            if (clean[k] === '') clean[k] = null;
        }
        clean.cover_image = galleryForm.cover_image;
        clean.images = galleryForm.images ?? [];

        // PHP only populates $_FILES for POST, so a multipart PUT arrives
        // with no files at all — editing a gallery silently discarded both
        // the new cover and any added images. Post with a spoofed method.
        if (editingGallery.value) clean._method = 'put';

        return clean;
    });

    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            galleryModalOpen.value = false;
        },
    };

    galleryForm.post(
        editingGallery.value
            ? route('admin.galleries.update', editingGallery.value.id)
            : route('admin.galleries.store'),
        options,
    );
}

// ------------------------------------------------------------- pagination
function pageLinks() {
    const links = props.galleries?.links ?? [];
    const pages = links.filter((l) => l.url && Number.isInteger(Number(l.label)));

    const current = props.galleries?.current_page ?? 1;
    const lastPage = props.galleries?.last_page ?? 1;
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
</script>

<template>
    <Head title="المعارض" />

    <AdminLayout>
        <!-- Stats -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Link
                v-for="(card, i) in statCards"
                :key="card.key"
                :href="card.href || route('admin.galleries.index')"
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
                        <AdminIcon name="collection" size="sm" />
                        المعارض
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
                        :placeholder="trashMode ? 'بحث في المحذوفات...' : 'بحث بالعنوان العربي أو الإنجليزي...'"
                        class="input-admin ps-10"
                    />
                </div>

                <select v-model="status" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[140px]" @change="applyFilters()">
                    <option value="">كل الحالات</option>
                    <option value="1">ظاهرة</option>
                    <option value="0">مخفية</option>
                </select>

                <button
                    v-if="can('gallery.manage')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98]"
                    @click="openCreate"
                >
                    <AdminIcon name="plus" size="sm" />
                    إضافة معرض
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

        <!-- Galleries table -->
        <section class="card-admin mt-6 animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.1s' }">
            <div v-if="galleries?.data?.length" class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-surface-100">
                    <thead class="bg-surface-50/70">
                        <tr>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">المعرض</th>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">الصور</th>
                            <th class="px-5 py-3.5 text-start text-xs font-bold uppercase tracking-wider text-surface-400">الحالة</th>
                            <th class="px-5 py-3.5 text-end text-xs font-bold uppercase tracking-wider text-surface-400">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        <tr
                            v-for="gallery in galleries.data"
                            :key="gallery.id"
                            class="transition-colors hover:bg-surface-50/60"
                            :class="{ 'opacity-60': gallery.deleted_at }"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3.5">
                                    <div
                                        v-if="gallery.cover?.thumb_url"
                                        class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200"
                                    >
                                        <img :src="gallery.cover.thumb_url" :alt="gallery.title_ar" class="h-full w-full object-cover" loading="lazy" />
                                    </div>
                                    <div v-else class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-600 ring-1 ring-surface-200">
                                        <AdminIcon name="collection" size="lg" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate text-sm font-semibold text-surface-900">{{ gallery.title_ar }}</span>
                                            <AdminIcon v-if="gallery.deleted_at" name="trash" size="xs" class="text-red-400" title="محذوف" />
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-surface-400">
                                            <span v-if="gallery.title_en">{{ gallery.title_en }} · </span>
                                            <span dir="ltr">/{{ gallery.slug || '—' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200">
                                    <AdminIcon name="image" size="xs" />
                                    {{ gallery.images_count }} صورة
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span v-if="gallery.is_active" class="badge-admin bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">
                                    ظاهر
                                </span>
                                <span v-else class="badge-admin bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200">
                                    مخفي
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <template v-if="gallery.deleted_at">
                                        <button
                                            v-if="can('gallery.manage')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50"
                                            title="استعادة المعرض"
                                            :disabled="restoringId === gallery.id"
                                            @click="restoreGallery(gallery)"
                                        >
                                            <AdminIcon :name="restoringId === gallery.id ? 'refresh' : 'restore'" size="sm" />
                                        </button>
                                    </template>
                                    <template v-else>
                                        <Link
                                            :href="route('admin.galleries.show', gallery.id)"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                            title="عرض التفاصيل"
                                        >
                                            <AdminIcon name="eye" size="sm" />
                                        </Link>
                                        <button
                                            v-if="can('gallery.manage')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-sky-50 hover:text-sky-600"
                                            title="تعديل"
                                            @click="openEdit(gallery)"
                                        >
                                            <AdminIcon name="edit" size="sm" />
                                        </button>
                                        <button
                                            v-if="can('gallery.manage')"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-red-50 hover:text-red-600"
                                            title="حذف"
                                            @click="openDelete(gallery)"
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
            <div v-if="galleries?.data?.length" class="divide-y divide-surface-100 lg:hidden">
                <article
                    v-for="gallery in galleries.data"
                    :key="gallery.id"
                    class="p-4"
                    :class="{ 'opacity-60': gallery.deleted_at }"
                >
                    <div class="flex items-start gap-3">
                        <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-50 text-brand-600 ring-1 ring-surface-200">
                            <img v-if="gallery.cover?.thumb_url" :src="gallery.cover.thumb_url" :alt="gallery.title_ar" class="h-full w-full object-cover" loading="lazy" />
                            <AdminIcon v-else name="collection" size="lg" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-1.5">
                                <h3 class="min-w-0 flex-1 text-sm font-semibold leading-5 text-surface-900">{{ gallery.title_ar }}</h3>
                                <AdminIcon v-if="gallery.deleted_at" name="trash" size="xs" class="mt-0.5 shrink-0 text-red-400" />
                            </div>
                            <p class="mt-0.5 truncate text-xs text-surface-400" dir="ltr">/{{ gallery.slug || '—' }}</p>

                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span v-if="gallery.is_active" class="badge-admin bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">ظاهر</span>
                                <span v-else class="badge-admin bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200">مخفي</span>
                                <span class="badge-admin bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200">
                                    <AdminIcon name="image" size="xs" />
                                    {{ gallery.images_count }} صورة
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-1 border-t border-surface-100 pt-3">
                        <template v-if="gallery.deleted_at">
                            <button
                                v-if="can('gallery.manage')"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50"
                                :disabled="restoringId === gallery.id"
                                @click="restoreGallery(gallery)"
                            >
                                <AdminIcon :name="restoringId === gallery.id ? 'refresh' : 'restore'" size="sm" :class="{ 'animate-spin': restoringId === gallery.id }" />
                                استعادة
                            </button>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('admin.galleries.show', gallery.id)"
                                class="grid h-10 w-10 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                aria-label="عرض التفاصيل"
                            >
                                <AdminIcon name="eye" size="sm" />
                            </Link>
                            <button
                                v-if="can('gallery.manage')"
                                type="button"
                                class="grid h-10 w-10 place-items-center rounded-lg text-surface-500 transition hover:bg-sky-50 hover:text-sky-600"
                                aria-label="تعديل"
                                @click="openEdit(gallery)"
                            >
                                <AdminIcon name="edit" size="sm" />
                            </button>
                            <button
                                v-if="can('gallery.manage')"
                                type="button"
                                class="grid h-10 w-10 place-items-center rounded-lg text-surface-500 transition hover:bg-red-50 hover:text-red-600"
                                aria-label="حذف"
                                @click="openDelete(gallery)"
                            >
                                <AdminIcon name="trash" size="sm" />
                            </button>
                        </template>
                    </div>
                </article>
            </div>

            <!-- Empty states -->
            <div v-if="!galleries?.data?.length" class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                    <AdminIcon :name="trashMode ? 'trash' : 'collection'" size="xl" />
                </span>
                <h3 class="mt-4 text-sm font-bold text-surface-900">
                    {{ trashMode ? 'سلة المحذوفات فارغة' : 'لا توجد معارض' }}
                </h3>
                <p class="mt-1 max-w-sm text-sm text-surface-400">
                    {{ trashMode ? 'المعارض المحذوفة ستظهر هنا لإمكانية استعادتها.' : hasActiveFilters ? 'لم يتم العثور على معارض مطابقة للفلاتر الحالية.' : 'أنشئ معرضاً لعرض صور منتجاتك وأعمالك.' }}
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
                        v-if="!trashMode && can('gallery.manage')"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700"
                        @click="openCreate"
                    >
                        <AdminIcon name="plus" size="sm" />
                        إضافة معرض
                    </button>
                </div>
            </div>

            <!-- Footer / pagination -->
            <div v-if="galleries?.data?.length" class="flex flex-col items-center justify-between gap-4 border-t border-surface-100 px-5 py-4 sm:flex-row">
                <p class="text-xs text-surface-400">
                    عرض <span class="font-semibold text-surface-600">{{ galleries.from ?? 0 }}–{{ galleries.to ?? 0 }}</span>
                    من إجمالي <span class="font-semibold text-surface-600">{{ galleries.total ?? 0 }}</span> معرض
                </p>

                <nav v-if="galleries.last_page > 1" class="flex items-center gap-1">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!galleries.prev_page_url" aria-label="السابق" @click="goTo(galleries.prev_page_url)">
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
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!galleries.next_page_url" aria-label="التالي" @click="goTo(galleries.next_page_url)">
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
                        <h3 class="text-base font-bold text-surface-900">حذف المعرض</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم نقل «<span class="font-semibold text-surface-900">{{ confirmDelete?.title_ar }}</span>» إلى سلة المحذوفات.
                            يمكنك استعادته في أي وقت من تبويب «المحذوفات».
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
                        @click="deleteGallery"
                    >
                        <AdminIcon :name="deletingId === confirmDelete?.id ? 'refresh' : 'trash'" size="sm" />
                        {{ deletingId === confirmDelete?.id ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Create / edit modal -->
        <Modal :show="galleryModalOpen" max-width="lg" @close="galleryModalOpen = false">
            <div class="max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-surface-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon name="collection" size="sm" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-surface-900">
                                {{ editingGallery ? 'تعديل المعرض' : 'إضافة معرض جديد' }}
                            </h3>
                            <p class="text-xs text-surface-400">
                                {{ editingGallery ? `تعديل «${editingGallery.title_ar}»` : 'أدخل بيانات المعرض ثم احفظه.' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-surface-100 hover:text-surface-700" aria-label="إغلاق" @click="galleryModalOpen = false">
                        <AdminIcon name="close" size="sm" />
                    </button>
                </div>

                <form id="gallery-form" class="p-6" @submit.prevent="saveGallery" novalidate>
                    <div v-if="galleryForm.errors.error" class="mb-4 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <AdminIcon name="alert" size="sm" />
                        {{ galleryForm.errors.error }}
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">
                                العنوان بالعربية <span class="text-red-500">*</span>
                            </label>
                            <input v-model="galleryForm.title_ar" type="text" class="input-admin" placeholder="مثال: غرف نوم فاخرة" />
                            <InputError :message="galleryForm.errors.title_ar" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">العنوان بالإنجليزية</label>
                            <input v-model="galleryForm.title_en" type="text" class="input-admin" placeholder="e.g. Luxury Bedrooms" />
                            <InputError :message="galleryForm.errors.title_en" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">الرابط (SLUG)</label>
                            <input v-model="galleryForm.slug" type="text" class="input-admin" dir="ltr" placeholder="يُولَّد تلقائياً" />
                            <InputError :message="galleryForm.errors.slug" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">ترتيب العرض</label>
                            <input v-model="galleryForm.sort_order" type="number" min="0" class="input-admin" />
                            <InputError :message="galleryForm.errors.sort_order" class="mt-1.5" />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف (عربي)</label>
                            <textarea v-model="galleryForm.description_ar" rows="3" class="input-admin resize-none" placeholder="وصف المعرض..."></textarea>
                            <InputError :message="galleryForm.errors.description_ar" class="mt-1.5" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف (إنجليزي)</label>
                            <textarea v-model="galleryForm.description_en" rows="3" class="input-admin resize-none" placeholder="Gallery description..."></textarea>
                            <InputError :message="galleryForm.errors.description_en" class="mt-1.5" />
                        </div>
                    </div>

                    <div class="mt-5 flex items-end gap-4">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2.5 text-sm font-medium text-surface-700"
                            @click="galleryForm.is_active = !galleryForm.is_active"
                        >
                            <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition" :class="galleryForm.is_active ? 'bg-emerald-500' : 'bg-surface-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="galleryForm.is_active ? 'translate-x-1' : 'translate-x-[26px]'"></span>
                            </span>
                            <span class="inline-flex items-center gap-1.5" :class="galleryForm.is_active ? 'text-emerald-700' : 'text-surface-400'">
                                <AdminIcon name="eye" size="sm" />
                                ظاهر للعرض
                            </span>
                        </button>
                    </div>

                    <!-- Cover image -->
                    <div class="mt-6">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">صورة الغلاف</label>
                        <ImageDropzone
                            :model-value="galleryForm.cover_image"
                            :existing-url="storedCover"
                            :max-size-mb="5"
                            label="اسحب صورة الغلاف هنا أو اضغط للاختيار"
                            @update:model-value="onCoverPicked"
                            @remove-existing="onRemoveStoredCover"
                        />
                        <InputError :message="galleryForm.errors.cover_image" class="mt-1.5" />
                    </div>

                    <!-- Gallery images -->
                    <div class="mt-6">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">صور المعرض</label>
                        <ImageDropzone
                            v-model="galleryForm.images"
                            multiple
                            :max-size-mb="5"
                            :max-files="60"
                            label="اسحب صور المعرض هنا أو اضغط للاختيار"
                        />
                        <InputError :message="galleryForm.errors.images || galleryForm.errors['images.0']" class="mt-1.5" />
                    </div>
                </form>

                <div class="flex items-center justify-end gap-3 border-t border-surface-100 px-6 py-4">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                        @click="galleryModalOpen = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        form="gallery-form"
                        :disabled="galleryForm.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                    >
                        <AdminIcon :name="galleryForm.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': galleryForm.processing }" />
                        {{ galleryForm.processing ? 'جارٍ الحفظ...' : editingGallery ? 'حفظ التعديلات' : 'إضافة المعرض' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>