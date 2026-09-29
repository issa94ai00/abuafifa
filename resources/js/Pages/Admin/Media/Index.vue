<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import ImageDropzone from '@/Components/Admin/ImageDropzone.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    media: Object,
    stats: Object,
    filters: Object,
    collections: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

const collectionLabel = Object.fromEntries(
    (props.collections || []).map((c) => [c.value, c.label]),
);

// -------------------------------------------------------------- filter state
const search = ref(props.filters?.search ?? '');
const collection = ref(props.filters?.collection ?? '');

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

function applyFilters(overrides = {}) {
    router.get(
        route('admin.media.index'),
        {
            ...(search.value ? { search: search.value } : {}),
            ...(collection.value ? { collection: collection.value } : {}),
            page: 1,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters() {
    search.value = '';
    collection.value = '';
    clearTimeout(searchTimer);
    applyFilters();
}

const hasActiveFilters = computed(() => !!(search.value || collection.value));

// ------------------------------------------------------------------ stats
function formatBytes(bytes) {
    if (!bytes) return '0 KB';
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${Math.round(bytes / 1024)} KB`;
}

const statCards = computed(() => [
    {
        key: 'total',
        label: 'إجمالي الصور',
        value: props.stats?.total ?? 0,
        icon: 'image',
        tone: 'bg-brand-50 text-brand-600 group-hover:ring-brand-300',
    },
    {
        key: 'storage',
        label: 'حجم المكتبة',
        value: formatBytes(props.stats?.storage ?? 0),
        icon: 'inbox',
        tone: 'bg-gold-50 text-gold-600 group-hover:ring-gold-300',
    },
    {
        key: 'month_uploads',
        label: 'رفع هذا الشهر',
        value: props.stats?.month_uploads ?? 0,
        icon: 'calendar',
        tone: 'bg-emerald-50 text-emerald-600 group-hover:ring-emerald-300',
    },
    {
        key: 'processed',
        label: 'جاهزة للاستخدام',
        value: props.stats?.processed ?? 0,
        icon: 'check',
        tone: 'bg-surface-100 text-surface-600 group-hover:ring-surface-300',
    },
]);

function formatSize(value) {
    if (!value) return '—';
    return Array.isArray(value) ? value.join(' × ') : String(value);
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('ar-JO', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ------------------------------------------------------------------ uploads
const uploadForm = useForm({
    collection: 'default',
    files: [],
});

const uploadModalOpen = ref(false);

// Previews, drag & drop, revoking and client-side screening are the
// dropzone's job now; this page just holds the File[] it hands back.
const pendingFiles = computed(() => uploadForm.files ?? []);

function openUpload() {
    uploadForm.clearErrors();
    uploadForm.reset('collection', 'files');
    uploadModalOpen.value = true;
}

function submitUploads() {
    if (!uploadForm.files.length) return;

    uploadForm.post(route('admin.media.store'), {
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset('files');
            uploadModalOpen.value = false;
        },
    });
}

// ------------------------------------------------------------- edit metadata
const editingItem = ref(null);
const detailOpen = ref(false);

const metaForm = useForm({
    alt_ar: '',
    alt_en: '',
    caption: '',
});

const copied = ref(false);

async function copyUrl() {
    if (!editingItem.value?.url) return;
    try {
        await navigator.clipboard.writeText(editingItem.value.url);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1600);
    } catch {
        /* clipboard unavailable */
    }
}

function openDetail(item) {
    editingItem.value = item;
    copied.value = false;
    metaForm.clearErrors();
    metaForm.defaults({
        alt_ar: item.alt_ar ?? '',
        alt_en: item.alt_en ?? '',
        caption: item.caption ?? '',
    });
    metaForm.reset();
    detailOpen.value = true;
}

function saveMeta() {
    metaForm.patch(route('admin.media.update', editingItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingItem.value) {
                editingItem.value.alt_ar = metaForm.alt_ar || null;
                editingItem.value.alt_en = metaForm.alt_en || null;
                editingItem.value.caption = metaForm.caption || null;
            }
        },
    });
}

// ------------------------------------------------------------- delete
const confirmDelete = ref(null);
const deletingId = ref(null);

function openDelete(item) {
    confirmDelete.value = item;
}

function deleteMedia() {
    if (!confirmDelete.value) return;
    deletingId.value = confirmDelete.value.id;
    router.delete(route('admin.media.destroy', confirmDelete.value.id), {
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

// ------------------------------------------------------------- pagination
function pageLinks() {
    const links = props.media?.links ?? [];
    const pages = links.filter((l) => l.url && Number.isInteger(Number(l.label)));

    const current = props.media?.current_page ?? 1;
    const lastPage = props.media?.last_page ?? 1;
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
    <Head title="مكتبة الوسائط" />

    <AdminLayout>
        <!-- Stats -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                v-for="(card, i) in statCards"
                :key="card.key"
                class="card-admin animate-fade-in-up p-4 sm:p-5"
                :style="{ animationDelay: `${i * 0.05}s` }"
            >
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl ring-4 ring-transparent" :class="card.tone">
                        <AdminIcon :name="card.icon" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <span class="block truncate text-2xl font-bold tabular-nums text-surface-900">{{ card.value }}</span>
                        <span class="block truncate text-xs font-medium text-surface-400">{{ card.label }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Toolbar -->
        <section class="card-admin mt-6 animate-fade-in-up p-4 sm:p-5" :style="{ animationDelay: '0.05s' }">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-[200px] flex-1">
                    <AdminIcon name="search" size="sm" class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-surface-400" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="بحث باسم الملف أو الوصف..."
                        class="input-admin ps-10"
                    />
                </div>

                <select v-model="collection" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[160px]" @change="applyFilters()">
                    <option value="">كل المجموعات</option>
                    <option v-for="c in collections" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>

                <button
                    v-if="can('media.upload')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98]"
                    @click="openUpload"
                >
                    <AdminIcon name="plus" size="sm" />
                    رفع صور
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

        <!-- Media grid -->
        <section class="mt-6 animate-fade-in-up" :style="{ animationDelay: '0.1s' }">
            <div v-if="media?.data?.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                <div
                    v-for="item in media.data"
                    :key="item.id"
                    class="group card-admin animate-fade-in-up overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-premium-lg"
                    :style="{ animationDelay: `${0 + Math.min(item.id, 5) * 0.02}s` }"
                >
                    <button
                        type="button"
                        class="block w-full cursor-zoom-in overflow-hidden bg-surface-100"
                        @click="openDetail(item)"
                    >
                        <div class="relative aspect-square overflow-hidden">
                            <img
                                :src="item.thumb_url || item.url"
                                :alt="item.alt_ar || item.original_name"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                loading="lazy"
                            />
                            <span class="absolute right-2 top-2 inline-flex rounded-full bg-black/55 px-2 py-0.5 text-[10px] font-semibold text-white backdrop-blur-sm">
                                {{ item.extension?.toUpperCase() }}
                            </span>
                        </div>
                    </button>

                    <div class="p-3 sm:p-3.5">
                        <p class="truncate text-xs font-semibold text-surface-900" :title="item.original_name">{{ item.original_name }}</p>
                        <div class="mt-1.5 flex items-center justify-between">
                            <span class="text-[11px] text-surface-400">{{ item.readable_size || formatSize(item.size) }}</span>
                            <span class="inline-flex max-w-[45%] items-center gap-1 truncate rounded-md bg-surface-100 px-1.5 py-0.5 text-[10px] font-semibold text-surface-500">
                                {{ collectionLabel[item.collection] || item.collection }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty states -->
            <div v-else class="card-admin flex flex-col items-center justify-center px-6 py-20 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                    <AdminIcon name="image" size="xl" />
                </span>
                <h3 class="mt-4 text-sm font-bold text-surface-900">لا توجد صور</h3>
                <p class="mt-1 max-w-sm text-sm text-surface-400">
                    {{ hasActiveFilters ? 'لم يتم العثور على صور مطابقة للفلاتر الحالية.' : 'ارفع صورك إلى المكتبة لاستخدامها في المنتجات والمعارض.' }}
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
                        v-if="!hasActiveFilters && can('media.upload')"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700"
                        @click="openUpload"
                    >
                        <AdminIcon name="plus" size="sm" />
                        رفع أول صورة
                    </button>
                </div>
            </div>

            <!-- Footer / pagination -->
            <div v-if="media?.data?.length" class="mt-4 flex flex-col items-center justify-between gap-4 rounded-2xl border border-surface-100 bg-white px-5 py-4 sm:flex-row">
                <p class="text-xs text-surface-400">
                    عرض <span class="font-semibold text-surface-600">{{ media.from ?? 0 }}–{{ media.to ?? 0 }}</span>
                    من إجمالي <span class="font-semibold text-surface-600">{{ media.total ?? 0 }}</span> صورة
                </p>

                <nav v-if="media.last_page > 1" class="flex items-center gap-1">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!media.prev_page_url" aria-label="السابق" @click="goTo(media.prev_page_url)">
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
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!media.next_page_url" aria-label="التالي" @click="goTo(media.next_page_url)">
                        <AdminIcon name="chevron-left" size="sm" />
                    </button>
                </nav>
            </div>
        </section>

        <!-- Upload modal -->
        <Modal :show="uploadModalOpen" max-width="lg" @close="uploadModalOpen = false">
            <div class="max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-surface-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon name="image" size="sm" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-surface-900">رفع صور جديدة</h3>
                            <p class="text-xs text-surface-400">حتى 25 صورة بحجم أقصى 8MB للواحدة</p>
                        </div>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-surface-100 hover:text-surface-700" aria-label="إغلاق" @click="uploadModalOpen = false">
                        <AdminIcon name="close" size="sm" />
                    </button>
                </div>

                <div class="p-6">
                    <div v-if="uploadForm.errors.error" class="mb-4 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <AdminIcon name="alert" size="sm" />
                        {{ uploadForm.errors.error }}
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">المجموعة</label>
                        <select v-model="uploadForm.collection" class="input-admin cursor-pointer">
                            <option v-for="c in collections" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                        <InputError :message="uploadForm.errors.collection" class="mt-1.5" />
                    </div>

                    <div class="mt-4">
                        <ImageDropzone
                            v-model="uploadForm.files"
                            multiple
                            :max-size-mb="8"
                            :max-files="25"
                            :progress="uploadForm.progress ? uploadForm.progress.percentage : null"
                            label="اسحب الصور هنا أو اضغط للاختيار"
                        />
                        <InputError :message="uploadForm.errors.files || uploadForm.errors['files.0']" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-surface-100 px-6 py-4">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                        @click="uploadModalOpen = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                        :disabled="!pendingFiles.length || uploadForm.processing"
                        @click="submitUploads"
                    >
                        <AdminIcon :name="uploadForm.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': uploadForm.processing }" />
                        {{ uploadForm.processing ? 'جارٍ الرفع...' : `رفع ${pendingFiles.length} صورة` }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Detail / edit modal -->
        <Modal :show="detailOpen" max-width="2xl" @close="detailOpen = false">
            <div class="max-h-[85vh] overflow-y-auto">
                <div v-if="editingItem" class="flex items-center justify-between border-b border-surface-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon name="eye" size="sm" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-surface-900">تفاصيل الصورة</h3>
                            <p class="text-xs text-surface-400">{{ editingItem.original_name }}</p>
                        </div>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-surface-100 hover:text-surface-700" aria-label="إغلاق" @click="detailOpen = false">
                        <AdminIcon name="close" size="sm" />
                    </button>
                </div>

                <div v-if="editingItem" class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                    <!-- Preview -->
                    <div>
                        <div class="overflow-hidden rounded-2xl bg-surface-100 ring-1 ring-surface-200">
                            <img :src="editingItem.url || editingItem.thumb_url" :alt="editingItem.alt_ar || editingItem.original_name" class="h-64 w-full object-cover" />
                        </div>

                        <!-- Meta -->
                        <dl class="mt-4 space-y-2 rounded-2xl bg-surface-50 p-4 text-xs">
                            <div class="flex items-center justify-between">
                                <dt class="text-surface-400">الأبعاد</dt>
                                <dd class="font-semibold tabular-nums text-surface-700" dir="ltr">{{ formatSize([editingItem.width, editingItem.height]) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-surface-400">الحجم</dt>
                                <dd class="font-semibold tabular-nums text-surface-700">{{ editingItem.readable_size || formatBytes(editingItem.size) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-surface-400">الصيغة</dt>
                                <dd class="font-semibold text-surface-700" dir="ltr">{{ (editingItem.mime_type || editingItem.extension || '—').toUpperCase() }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-surface-400">المجموعة</dt>
                                <dd class="font-semibold text-surface-700">{{ collectionLabel[editingItem.collection] || editingItem.collection }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-surface-400">تاريخ الرفع</dt>
                                <dd class="font-semibold text-surface-700">{{ formatDate(editingItem.created_at) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-surface-400">الرابط</dt>
                                <dd>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 font-semibold text-brand-600 ring-1 ring-surface-200 transition hover:bg-brand-50"
                                        @click="copyUrl"
                                    >
                                        <AdminIcon :name="copied ? 'check' : 'external'" size="xs" />
                                        {{ copied ? 'تم النسخ' : 'نسخ الرابط' }}
                                    </button>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Edit -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">النص البديل (عربي)</label>
                        <input v-model="metaForm.alt_ar" type="text" class="input-admin" placeholder="وصف الصورة للبحث وإمكانية الوصول" @keydown.enter.prevent="saveMeta" />
                        <InputError :message="metaForm.errors.alt_ar" class="mt-1.5" />

                        <label class="mb-1.5 mt-4 block text-sm font-semibold text-surface-700">النص البديل (إنجليزي)</label>
                        <input v-model="metaForm.alt_en" type="text" class="input-admin" dir="ltr" placeholder="Alt text for search & accessibility" @keydown.enter.prevent="saveMeta" />
                        <InputError :message="metaForm.errors.alt_en" class="mt-1.5" />

                        <label class="mb-1.5 mt-4 block text-sm font-semibold text-surface-700">التعليق</label>
                        <textarea v-model="metaForm.caption" rows="3" class="input-admin resize-none" placeholder="تعليق اختياري على الصورة"></textarea>
                        <InputError :message="metaForm.errors.caption" class="mt-1.5" />

                        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-surface-100 pt-5">
                            <button
                                v-if="can('media.delete')"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-3.5 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                                @click="detailOpen = false; openDelete(editingItem)"
                            >
                                <AdminIcon name="trash" size="sm" />
                                حذف
                            </button>
                            <button
                                v-if="can('media.upload')"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                                :disabled="metaForm.processing"
                                @click="saveMeta"
                            >
                                <AdminIcon :name="metaForm.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': metaForm.processing }" />
                                {{ metaForm.processing ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete confirmation -->
        <Modal :show="!!confirmDelete" max-width="md" @close="confirmDelete = null">
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600">
                        <AdminIcon name="trash" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-surface-900">حذف الصورة</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم حذف «<span class="font-semibold text-surface-900">{{ confirmDelete?.original_name }}</span>» نهائياً مع ملفه من الخادم.
                            لا يمكن التراجع عن هذا الإجراء.
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
                        @click="deleteMedia"
                    >
                        <AdminIcon :name="deletingId === confirmDelete?.id ? 'refresh' : 'trash'" size="sm" />
                        {{ deletingId === confirmDelete?.id ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>