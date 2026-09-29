<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { compressImage } from '@/Composables/useImageCompression';

const props = defineProps({ gallery: Object, images: Array });

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

const gallery = computed(() => props.gallery);
const images = computed(() => props.images ?? []);

// ------------------------------------------------------------- gallery form
const galleryFormOpen = ref(false);
const coverFile = ref(null);
const coverPreview = ref(null);

const galleryForm = useForm({
    title_ar: '',
    title_en: '',
    slug: '',
    description_ar: '',
    description_en: '',
    sort_order: 0,
    is_active: true,
    cover_image: null,
});

function openGalleryForm() {
    coverFile.value = null;
    coverPreview.value = null;
    galleryForm.clearErrors();
    galleryForm.defaults({
        title_ar: gallery.value?.title_ar ?? '',
        title_en: gallery.value?.title_en ?? '',
        slug: gallery.value?.slug ?? '',
        description_ar: gallery.value?.description_ar ?? '',
        description_en: gallery.value?.description_en ?? '',
        sort_order: gallery.value?.sort_order ?? 0,
        is_active: !!gallery.value?.is_active,
        cover_image: null,
    });
    galleryForm.reset();
    galleryFormOpen.value = true;
}

async function onCoverChange(event) {
    const picked = event.target.files?.[0];
    event.target.value = '';
    if (!picked) return;

    const file = await compressImage(picked);
    coverFile.value = file;
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    coverPreview.value = URL.createObjectURL(file);
}

function saveGallery() {
    galleryForm.transform((data) => {
        data.cover_image = coverFile.value;
        return data;
    });
    galleryForm.put(route('admin.galleries.update', props.gallery.id), {
        preserveScroll: true,
        onSuccess: () => {
            galleryFormOpen.value = false;
            if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
            coverPreview.value = null;
            coverFile.value = null;
        },
        onError: () => {},
    });
}

// ------------------------------------------------------------- image edit
const imageFormOpen = ref(false);
const editingImage = ref(null);

const imageForm = useForm({
    caption_ar: '',
    caption_en: '',
    is_featured: false,
});

function openImageForm(image) {
    editingImage.value = image;
    imageForm.clearErrors();
    imageForm.defaults({
        caption_ar: image.caption ?? '',
        caption_en: image.caption_en ?? '',
        is_featured: !!image.featured,
    });
    imageForm.reset();
    imageFormOpen.value = true;
}

function saveImage() {
    imageForm.patch(
        route('admin.galleries.images.update', { gallery: props.gallery.id, image: editingImage.value.id }),
        {
            preserveScroll: true,
            onSuccess: () => (imageFormOpen.value = false),
            onError: () => {},
        },
    );
}

// ------------------------------------------------------------- reorder
const movingId = ref(null);

function moveImage(image, direction) {
    if (movingId.value) return;
    movingId.value = image.id;
    router.post(
        route('admin.galleries.images.move', { gallery: props.gallery.id, image: image.id }),
        { direction },
        { preserveScroll: true, onFinish: () => (movingId.value = null) },
    );
}

function isFirst(index) {
    return index === 0;
}

function isLast(index) {
    return index === images.value.length - 1;
}

// ------------------------------------------------------------- remove
const removingImage = ref(null);
const removingId = ref(null);

function confirmRemove(image) {
    removingImage.value = image;
}

function removeImage() {
    if (!removingImage.value) return;
    removingId.value = removingImage.value.id;
    router.delete(route('admin.galleries.images.remove', { gallery: props.gallery.id, image: removingImage.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            removingImage.value = null;
            removingId.value = null;
        },
        onError: () => {
            removingId.value = null;
        },
    });
}

// ------------------------------------------------------------- add images
const imagesForm = useForm({
    title_ar: props.gallery?.title_ar ?? '',
    images: [],
});

const newImages = ref([]); // { file, preview }

async function onImagesChange(event) {
    // Copy before resetting the input: `files` is live, so clearing the
    // value would empty the very list we are about to read.
    const picked = Array.from(event.target.files ?? []);
    event.target.value = '';

    // Sequential, so a batch of full-size photos is not all decoded at once.
    for (const original of picked) {
        const file = await compressImage(original);
        newImages.value.push({ file, preview: URL.createObjectURL(file) });
    }
}

function removeNewImage(index) {
    const item = newImages.value[index];
    if (item?.preview) URL.revokeObjectURL(item.preview);
    newImages.value.splice(index, 1);
}

function addImages() {
    if (!newImages.value.length) return;
    imagesForm.transform((data) => {
        data.images = newImages.value.map((item) => item.file);
        return data;
    });
    imagesForm.put(route('admin.galleries.update', props.gallery.id), {
        preserveScroll: true,
        onSuccess: () => {
            newImages.value.forEach((item) => item.preview && URL.revokeObjectURL(item.preview));
            newImages.value = [];
        },
    });
}
</script>

<template>
    <Head title="تفاصيل المعرض" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-surface-900 sm:text-lg">{{ gallery?.title_ar }}</h2>
                <p class="mt-0.5 text-sm text-surface-400">مراجعة صور المعرض وإدارتها</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="can('gallery.manage') || can('gallery.edit')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98]"
                    @click="openGalleryForm"
                >
                    <AdminIcon name="edit" size="sm" />
                    تعديل المعرض
                </button>
                <Link
                    :href="route('admin.galleries.index')"
                    class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                >
                    <AdminIcon name="chevron-right" size="sm" />
                    العودة إلى المعارض
                </Link>
            </div>
        </div>

        <div v-if="gallery" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main info card -->
            <div class="card-admin animate-fade-in-up overflow-hidden lg:col-span-2">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="text-xl font-bold text-surface-900">{{ gallery.title_ar }}</h3>
                            <p v-if="gallery.title_en" class="mt-1 truncate text-sm text-surface-400">{{ gallery.title_en }}</p>
                            <p class="mt-1 text-xs text-surface-400" dir="ltr">/{{ gallery.slug || '—' }}</p>
                        </div>
                        <span v-if="gallery.is_active" class="badge-admin shrink-0 bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">ظاهر</span>
                        <span v-else class="badge-admin shrink-0 bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200">مخفي</span>
                    </div>

                    <div v-if="gallery.description_ar || gallery.description_en" class="mt-6 border-t border-surface-100 pt-6">
                        <h4 class="text-sm font-bold text-surface-900">وصف المعرض</h4>
                        <p v-if="gallery.description_ar" class="mt-2 whitespace-pre-line text-sm leading-7 text-surface-600">{{ gallery.description_ar }}</p>
                        <p v-if="gallery.description_en" class="mt-2 whitespace-pre-line text-sm leading-7 text-surface-400">{{ gallery.description_en }}</p>
                    </div>

                    <dl v-else class="mt-6 grid grid-cols-3 gap-3 border-t border-surface-100 pt-6">
                        <div class="rounded-xl bg-surface-50 px-4 py-3">
                            <dt class="text-[11px] font-semibold text-surface-400">الترتيب</dt>
                            <dd class="mt-0.5 text-sm font-bold tabular-nums text-surface-800">{{ gallery.sort_order }}</dd>
                        </div>
                        <div class="rounded-xl bg-surface-50 px-4 py-3">
                            <dt class="text-[11px] font-semibold text-surface-400">عدد الصور</dt>
                            <dd class="mt-0.5 text-sm font-bold tabular-nums text-surface-800">{{ images.length }}</dd>
                        </div>
                        <div class="rounded-xl bg-surface-50 px-4 py-3">
                            <dt class="text-[11px] font-semibold text-surface-400">الحالة</dt>
                            <dd class="mt-0.5 text-sm font-bold" :class="gallery.is_active ? 'text-emerald-600' : 'text-surface-400'">
                                {{ gallery.is_active ? 'ظاهر للزوار' : 'مخفي' }}
                            </dd>
                        </div>
                    </dl>

                    <button
                        v-if="(can('gallery.manage') || can('gallery.edit')) && (gallery.description_ar || gallery.description_en)"
                        type="button"
                        class="mt-5 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-600 transition hover:bg-brand-50"
                        @click="openGalleryForm"
                    >
                        <AdminIcon name="edit" size="xs" />
                        تعديل المعلومات
                    </button>
                </div>
            </div>

            <!-- Cover card -->
            <div class="card-admin animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.05s' }">
                <p class="border-b border-surface-100 px-5 py-4 text-sm font-bold text-surface-900">صورة الغلاف</p>
                <div class="p-5">
                    <div v-if="gallery.cover_url" class="aspect-video overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200">
                        <img :src="gallery.cover_url" :alt="gallery.title_ar" class="h-full w-full object-cover" loading="lazy" />
                    </div>
                    <div v-else class="grid aspect-video place-items-center rounded-xl bg-surface-100 text-surface-300 ring-1 ring-surface-200">
                        <AdminIcon name="collection" size="xl" />
                    </div>
                    <div class="mt-4 flex items-center justify-between rounded-xl bg-surface-50 px-4 py-3">
                        <span class="text-xs font-semibold text-surface-600">{{ images.length }} صورة في المعرض</span>
                        <span class="inline-flex items-center gap-1.5 text-xs text-surface-400">
                            <AdminIcon name="image" size="xs" />
                            ترتيب: {{ gallery.sort_order }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Images management -->
            <div class="card-admin animate-fade-in-up overflow-hidden lg:col-span-3" :style="{ animationDelay: '0.1s' }">
                <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <AdminIcon name="image" size="sm" class="text-surface-400" />
                        <h4 class="text-sm font-bold text-surface-900">صور المعرض</h4>
                        <span class="ms-1 text-xs text-surface-400">— الترتيب ظاهر كما سيُعرض للزوار</span>
                    </div>
                    <span v-if="images.length" class="badge-admin bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200">
                        {{ images.length }} صورة
                    </span>
                </div>

                <div v-if="images.length" class="grid grid-cols-2 gap-4 p-5 sm:grid-cols-3 lg:grid-cols-4">
                    <div v-for="(image, index) in images" :key="image.id" class="group relative overflow-hidden rounded-2xl bg-surface-100 ring-1 ring-surface-200">
                        <button type="button" class="block w-full cursor-zoom-in" @click="openImageForm(image)">
                            <div class="aspect-square overflow-hidden">
                                <img :src="image.url || image.thumb" :alt="image.caption || gallery.title_ar" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy" />
                            </div>
                        </button>
                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-black/70 to-transparent px-3 pb-2.5 pt-8">
                            <span class="truncate text-xs font-medium text-white">{{ image.caption || 'بدون وصف' }}</span>
                            <span v-if="image.featured" class="inline-flex shrink-0 items-center gap-1 rounded-full bg-gold-400/90 px-2 py-0.5 text-[10px] font-bold text-surface-950">
                                <AdminIcon name="star" size="xs" />
                                مميزة
                            </span>
                        </div>

                        <!-- actions -->
                        <div class="absolute left-2 top-2 flex flex-col gap-1.5">
                            <button
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-lg bg-black/50 text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-30"
                                title="تحريك لأعلى"
                                :disabled="isFirst(index) || movingId === image.id"
                                @click="moveImage(image, 'up')"
                            >
                                <AdminIcon :name="movingId === image.id ? 'refresh' : 'chevron-right'" size="sm" :class="{ 'animate-spin': movingId === image.id }" />
                            </button>
                            <button
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-lg bg-black/50 text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-30"
                                title="تحريك لأسفل"
                                :disabled="isLast(index) || movingId === image.id"
                                @click="moveImage(image, 'down')"
                            >
                                <AdminIcon name="chevron-left" size="sm" />
                            </button>
                            <button
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-lg bg-black/50 text-white transition hover:bg-sky-600"
                                title="تعديل الصورة"
                                @click="openImageForm(image)"
                            >
                                <AdminIcon name="edit" size="sm" />
                            </button>
                            <button
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-lg bg-black/50 text-white transition hover:bg-red-600 disabled:opacity-40"
                                title="حذف الصورة"
                                :disabled="removingId === image.id"
                                @click="confirmRemove(image)"
                            >
                                <AdminIcon :name="removingId === image.id ? 'refresh' : 'trash'" size="sm" :class="{ 'animate-spin': removingId === image.id }" />
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center px-6 py-14 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                        <AdminIcon name="image" size="lg" />
                    </span>
                    <p class="mt-3 text-sm text-surface-400">لا توجد صور في هذا المعرض بعد.</p>
                </div>

                <!-- Add images -->
                <div class="border-t border-surface-100 px-5 py-5">
                    <div v-if="newImages.length" class="mb-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                        <div v-for="(item, index) in newImages" :key="index" class="group relative aspect-square overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200">
                            <img :src="item.preview" alt="صورة جديدة" class="h-full w-full object-cover" />
                            <button
                                type="button"
                                class="absolute right-1.5 top-1.5 grid h-7 w-7 place-items-center rounded-lg bg-black/50 text-white opacity-0 transition group-hover:opacity-100 hover:bg-red-600"
                                :aria-label="`حذف الصورة ${index + 1}`"
                                @click="removeNewImage(index)"
                            >
                                <AdminIcon name="close" size="xs" />
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <label
                            for="gallery-more-images"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-surface-300 bg-surface-50 px-4 py-2.5 text-sm font-semibold text-surface-600 transition hover:border-brand-400 hover:text-brand-600"
                        >
                            <AdminIcon name="plus" size="sm" />
                            اختيار صور
                        </label>
                        <input id="gallery-more-images" type="file" multiple accept="image/jpeg,image/png,image/webp,image/avif" class="sr-only" @change="onImagesChange" />

                        <button
                            v-if="newImages.length"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                            :disabled="imagesForm.processing"
                            @click="addImages"
                        >
                            <AdminIcon :name="imagesForm.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': imagesForm.processing }" />
                            {{ imagesForm.processing ? 'جارٍ الرفع...' : 'إضافة الصور للمعرض' }}
                        </button>
                    </div>
                    <InputError :message="imagesForm.errors.images || imagesForm.errors['images.0']" class="mt-2" />
                    <p class="mt-2 text-xs text-surface-400">صور JPG أو PNG أو WebP بحد أقصى 5MB لكل صورة — حتى 60 صورة.</p>
                </div>
            </div>
        </div>

        <!-- Gallery edit modal -->
        <Modal :show="galleryFormOpen" max-width="lg" @close="galleryFormOpen = false">
            <div class="max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-surface-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon name="edit" size="sm" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-surface-900">تعديل المعرض</h3>
                            <p class="text-xs text-surface-400">تحديث معلومات المعرض وصورة الغلاف</p>
                        </div>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-surface-100 hover:text-surface-700" aria-label="إغلاق" @click="galleryFormOpen = false">
                        <AdminIcon name="close" size="sm" />
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-x-5 gap-y-5 p-6 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">العنوان بالعربية</label>
                        <input v-model="galleryForm.title_ar" type="text" class="input-admin" placeholder="مثال: معرض الأثاث الحديث" />
                        <InputError :message="galleryForm.errors.title_ar" class="mt-1.5" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">العنوان بالإنجليزية</label>
                        <input v-model="galleryForm.title_en" type="text" dir="ltr" class="input-admin" placeholder="Modern Furniture Gallery" />
                        <InputError :message="galleryForm.errors.title_en" class="mt-1.5" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">الرابط المختصر (Slug)</label>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm text-surface-400" dir="ltr">/</span>
                            <input v-model="galleryForm.slug" type="text" dir="ltr" class="input-admin" placeholder="modern-furniture" />
                        </div>
                        <InputError :message="galleryForm.errors.slug" class="mt-1.5" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">الترتيب</label>
                        <input v-model.number="galleryForm.sort_order" type="number" class="input-admin" min="0" />
                        <InputError :message="galleryForm.errors.sort_order" class="mt-1.5" />
                    </div>

                    <div class="sm:col-span-2">
                        <div class="flex items-center justify-between rounded-xl bg-surface-50 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-surface-700">إظهار المعرض</p>
                                <p class="text-xs text-surface-400">ظاهر للزوار في صفحة المعارض</p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="galleryForm.is_active"
                                class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200"
                                :class="galleryForm.is_active ? 'bg-emerald-500' : 'bg-surface-300'"
                                @click="galleryForm.is_active = !galleryForm.is_active"
                            >
                                <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all duration-200" :class="galleryForm.is_active ? 'start-6' : 'start-0.5'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف بالعربية</label>
                        <textarea v-model="galleryForm.description_ar" rows="3" class="input-admin resize-none" placeholder="وصف المعرض بالعربية..."></textarea>
                        <InputError :message="galleryForm.errors.description_ar" class="mt-1.5" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف بالإنجليزية</label>
                        <textarea v-model="galleryForm.description_en" rows="3" dir="ltr" class="input-admin resize-none text-start" placeholder="Gallery description in English..."></textarea>
                        <InputError :message="galleryForm.errors.description_en" class="mt-1.5" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">صورة الغلاف</label>
                        <label
                            for="gallery-cover"
                            class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-2xl border-2 border-dashed border-surface-300 bg-surface-50 px-4 py-6 text-sm font-semibold text-surface-500 transition hover:border-brand-400 hover:text-brand-600"
                        >
                            <AdminIcon name="image" size="lg" class="text-surface-300" />
                            اختر صورة الغلاف
                            <span class="text-xs font-normal text-surface-400">اتركها فارغة للإبقاء على الغلاف الحالي</span>
                        </label>
                        <input id="gallery-cover" type="file" accept="image/jpeg,image/png,image/webp,image/avif" class="sr-only" @change="onCoverChange" />
                        <InputError :message="galleryForm.errors.cover_image" class="mt-2" />
                        <div v-if="coverPreview || gallery?.cover_url" class="mt-3 aspect-video overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200">
                            <img :src="coverPreview || gallery?.cover_url" :alt="galleryForm.title_ar" class="h-full w-full object-cover" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-surface-100 px-6 py-4">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                        @click="galleryFormOpen = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                        :disabled="galleryForm.processing"
                        @click="saveGallery"
                    >
                        <AdminIcon :name="galleryForm.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': galleryForm.processing }" />
                        {{ galleryForm.processing ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Image edit modal -->
        <Modal :show="imageFormOpen" max-width="lg" @close="imageFormOpen = false">
            <div class="max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-surface-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon name="image" size="sm" />
                        </span>
                        <h3 class="text-sm font-bold text-surface-900">تعديل الصورة</h3>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-surface-100 hover:text-surface-700" aria-label="إغلاق" @click="imageFormOpen = false">
                        <AdminIcon name="close" size="sm" />
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
                    <div class="overflow-hidden rounded-2xl bg-surface-100 ring-1 ring-surface-200">
                        <img :src="editingImage?.url || editingImage?.thumb" :alt="imageForm.caption_ar" class="h-56 w-full object-cover" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">التعليق بالعربية</label>
                        <input v-model="imageForm.caption_ar" type="text" class="input-admin" placeholder="وصف يظهر أسفل الصورة" @keydown.enter.prevent="saveImage" />
                        <InputError :message="imageForm.errors.caption_ar" class="mt-1.5" />

                        <label class="mb-1.5 mt-4 block text-sm font-semibold text-surface-700">التعليق بالإنجليزية</label>
                        <input v-model="imageForm.caption_en" type="text" dir="ltr" class="input-admin" placeholder="Image caption" @keydown.enter.prevent="saveImage" />
                        <InputError :message="imageForm.errors.caption_en" class="mt-1.5" />

                        <label class="mt-5 flex cursor-pointer items-center justify-between rounded-xl bg-surface-50 px-4 py-3">
                            <span class="text-sm font-semibold text-surface-700">صورة مميزة</span>
                            <input
                                v-model="imageForm.is_featured"
                                type="checkbox"
                                class="h-5 w-5 cursor-pointer rounded border-surface-300 accent-gold-500"
                            />
                        </label>
                        <InputError :message="imageForm.errors.is_featured" class="mt-1.5" />
                        <p class="mt-2 text-xs leading-5 text-surface-400">
                            اختيار صورة مميزة يزيل التمييز عن باقي صور المعرض تلقائياً.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-surface-100 px-6 py-4">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-3.5 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                        @click="imageFormOpen = false; confirmRemove(editingImage)"
                    >
                        <AdminIcon name="trash" size="sm" />
                        حذف الصورة
                    </button>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                            @click="imageFormOpen = false"
                        >
                            إلغاء
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                            :disabled="imageForm.processing"
                            @click="saveImage"
                        >
                            <AdminIcon :name="imageForm.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': imageForm.processing }" />
                            {{ imageForm.processing ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Image removal confirmation -->
        <Modal :show="!!removingImage" max-width="md" @close="removingImage = null">
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600">
                        <AdminIcon name="trash" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-surface-900">حذف الصورة</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم حذف الصورة نهائياً من معرض «<span class="font-semibold text-surface-900">{{ gallery?.title_ar }}</span>».
                            لا يمكن التراجع عن هذا الإجراء.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                        @click="removingImage = null"
                    >
                        إلغاء
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-premium transition hover:bg-red-700 disabled:opacity-60"
                        :disabled="removingId === removingImage?.id"
                        @click="removeImage"
                    >
                        <AdminIcon :name="removingId === removingImage?.id ? 'refresh' : 'trash'" size="sm" />
                        {{ removingId === removingImage?.id ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>