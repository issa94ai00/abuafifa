<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import InputError from '@/Components/InputError.vue';
import { compressImage } from '@/Composables/useImageCompression';

const props = defineProps({
    product: { type: Object, default: null },
    cover_url: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    existingImages: { type: Array, default: () => [] },
});

const isEdit = !!props.product;

const form = useForm({
    name_ar: props.product?.name_ar ?? '',
    name_en: props.product?.name_en ?? '',
    slug: props.product?.slug ?? '',
    sku: props.product?.sku ?? '',
    category_id: props.product?.category_id ?? '',
    price: props.product?.price ?? '',
    sale_price: props.product?.sale_price ?? '',
    currency: props.product?.currency ?? 'JOD',
    availability_status: props.product?.availability_status ?? 'available',
    short_description_ar: props.product?.short_description_ar ?? '',
    short_description_en: props.product?.short_description_en ?? '',
    description_ar: props.product?.description_ar ?? '',
    description_en: props.product?.description_en ?? '',
    dimensions_ar: props.product?.dimensions_ar ?? '',
    dimensions_en: props.product?.dimensions_en ?? '',
    material_ar: props.product?.material_ar ?? '',
    material_en: props.product?.material_en ?? '',
    color_ar: props.product?.color_ar ?? '',
    color_en: props.product?.color_en ?? '',
    sort_order: props.product?.sort_order ?? 0,
    is_featured: !!props.product?.is_featured,
    is_new: !!props.product?.is_new,
    is_active: props.product?.is_active ?? true,
    cover_image: null,
});

// ------------------------------------------------------------- cover upload
const newCoverUrl = ref(null);

const coverPreview = computed(() => {
    if (newCoverUrl.value) return newCoverUrl.value;
    return props.cover_url ?? null;
});

async function onCoverChange(event) {
    const picked = event.target.files?.[0];
    if (!picked) return;
    const file = await compressImage(picked);
    form.cover_image = file;
    newCoverUrl.value = URL.createObjectURL(file);
}

// ------------------------------------------------------------- gallery upload
const removedIds = ref([]);
const newGallery = ref([]); // { file, preview }

async function onGalleryChange(event) {
    // Copy before resetting the input: `files` is live, so clearing the
    // value would empty the very list we are about to read.
    const picked = Array.from(event.target.files ?? []);
    event.target.value = '';

    // Sequential, so a batch of full-size photos is not all decoded at once.
    for (const original of picked) {
        const file = await compressImage(original);
        newGallery.value.push({
            file,
            preview: URL.createObjectURL(file),
        });
    }
}

function removeNewImage(index) {
    const item = newGallery.value[index];
    if (item?.preview) URL.revokeObjectURL(item.preview);
    newGallery.value.splice(index, 1);
}

function removeExistingImage(imageId) {
    if (!removedIds.value.includes(imageId)) {
        removedIds.value.push(imageId);
    }
}

// ------------------------------------------------------------- submit
function normalize() {
    form.transform((data) => {
        const clean = { ...data };

        for (const key of [
            'name_en', 'slug', 'sku', 'sale_price', 'short_description_ar',
            'short_description_en', 'description_ar', 'description_en',
            'dimensions_ar', 'dimensions_en', 'material_ar', 'material_en',
            'color_ar', 'color_en',
        ]) {
            if (clean[key] === '') clean[key] = null;
        }

        clean.price = clean.price === '' ? null : clean.price;
        clean.cover_image = form.cover_image;
        clean.gallery_images = newGallery.value.map((g) => g.file);
        clean.remove_image_ids = isEdit ? removedIds.value : [];

        return clean;
    });
}

function submit() {
    normalize();
    if (isEdit) {
        form.put(route('admin.products.update', props.product.id));
    } else {
        form.post(route('admin.products.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'تعديل المنتج' : 'إضافة منتج'" />

    <AdminLayout>
        <div class="mx-auto max-w-5xl">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-bold text-surface-900 sm:text-2xl">
                        {{ isEdit ? 'تعديل المنتج' : 'إضافة منتج جديد' }}
                    </h2>
                    <p class="mt-0.5 text-sm text-surface-400">
                        {{ isEdit ? `تُحدَّث تعديلاتك فور الحفظ.` : 'أدخل بيانات المنتج ثم احفظه.' }}
                    </p>
                </div>
                <Link :href="route('admin.products.index')" class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-3.5 py-2 text-sm font-semibold text-surface-600 transition hover:bg-surface-50">
                    <AdminIcon name="chevron-right" size="sm" />
                    العودة إلى المنتجات
                </Link>
            </div>

            <form @submit.prevent="submit" novalidate>
                <!-- Server / global errors -->
                <div v-if="form.errors.error" class="mt-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    <AdminIcon name="alert" size="sm" />
                    {{ form.errors.error }}
                </div>

                <div class="mt-5 space-y-5">
                    <!-- الأساسيات -->
                    <section class="card-admin p-5 sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                                <AdminIcon name="box" size="sm" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-surface-900">البيانات الأساسية</h3>
                                <p class="text-xs text-surface-400">الاسم، التصنيف، والتعريفات الأساسية.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">
                                    الاسم بالعربية <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.name_ar" type="text" class="input-admin" placeholder="مثال: كانتون خشبي" />
                                <InputError :message="form.errors.name_ar" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">الاسم بالإنجليزية</label>
                                <input v-model="form.name_en" type="text" class="input-admin" placeholder="e.g. Wooden cabinet" />
                                <InputError :message="form.errors.name_en" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">التصنيف <span class="text-red-500">*</span></label>
                                <select v-model="form.category_id" class="input-admin cursor-pointer">
                                    <option value="">اختر التصنيف...</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name_ar }}</option>
                                </select>
                                <InputError :message="form.errors.category_id" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">قديم المنتج (SLUG)</label>
                                <input v-model="form.slug" type="text" class="input-admin" dir="ltr" placeholder="يُولَّد تلقائياً عند الحاجة" />
                                <InputError :message="form.errors.slug" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">رمز المنتج (SKU)</label>
                                <input v-model="form.sku" type="text" class="input-admin" dir="ltr" placeholder="مثال: AB-1001" />
                                <InputError :message="form.errors.sku" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">ترتيب العرض</label>
                                <input v-model="form.sort_order" type="number" min="0" class="input-admin" />
                                <InputError :message="form.errors.sort_order" class="mt-1.5" />
                            </div>
                        </div>
                    </section>

                    <!-- التسعير والحالة -->
                    <section class="card-admin p-5 sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-gold-50 text-gold-600">
                                <AdminIcon name="tag" size="sm" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-surface-900">التسعير والحالة</h3>
                                <p class="text-xs text-surface-400">السعر، التخفيض، وتوفر المنتج.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">
                                    السعر <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.price" type="number" step="0.01" min="0" class="input-admin" placeholder="0" />
                                <InputError :message="form.errors.price" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">سعر التخفيض</label>
                                <input v-model="form.sale_price" type="number" step="0.01" min="0" class="input-admin" placeholder="اختياري" />
                                <InputError :message="form.errors.sale_price" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">العملة</label>
                                <select v-model="form.currency" class="input-admin cursor-pointer">
                                    <option value="JOD">دينار أردني (JOD)</option>
                                    <option value="SYP">ليرة سورية (SYP)</option>
                                    <option value="USD">دولار أمريكي (USD)</option>
                                    <option value="EUR">يورو (EUR)</option>
                                </select>
                                <InputError :message="form.errors.currency" class="mt-1.5" />
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">حالة التوفر</label>
                                <select v-model="form.availability_status" class="input-admin cursor-pointer">
                                    <option value="available">متوفر</option>
                                    <option value="on_order">يُطلب مسبقاً</option>
                                    <option value="sold_out">نفدت الكمية</option>
                                </select>
                                <InputError :message="form.errors.availability_status" class="mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-surface-700">خيارات العرض</label>
                                <div class="flex flex-wrap gap-x-6 gap-y-3">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2.5 text-sm font-medium text-surface-700"
                                        @click="form.is_active = !form.is_active"
                                    >
                                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition" :class="form.is_active ? 'bg-emerald-500' : 'bg-surface-300'">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="form.is_active ? 'translate-x-1' : 'translate-x-[26px]'"></span>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5" :class="form.is_active ? 'text-emerald-700' : 'text-surface-400'">
                                            <AdminIcon name="eye" size="sm" />
                                            ظاهر للتسوق
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2.5 text-sm font-medium text-surface-700"
                                        @click="form.is_featured = !form.is_featured"
                                    >
                                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition" :class="form.is_featured ? 'bg-gold-500' : 'bg-surface-300'">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="form.is_featured ? 'translate-x-1' : 'translate-x-[26px]'"></span>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5" :class="form.is_featured ? 'text-gold-600' : 'text-surface-400'">
                                            <AdminIcon name="star" size="sm" />
                                            مميز
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2.5 text-sm font-medium text-surface-700"
                                        @click="form.is_new = !form.is_new"
                                    >
                                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition" :class="form.is_new ? 'bg-brand-500' : 'bg-surface-300'">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="form.is_new ? 'translate-x-1' : 'translate-x-[26px]'"></span>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5" :class="form.is_new ? 'text-brand-600' : 'text-surface-400'">
                                            <AdminIcon name="sparkles" size="sm" />
                                            وصل حديثاً
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- الوصف -->
                    <section class="card-admin p-5 sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-sky-50 text-sky-600">
                                <AdminIcon name="edit" size="sm" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-surface-900">الوصف</h3>
                                <p class="text-xs text-surface-400">وصف مختصر ووصف تفصيلي للمنتج.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">وصف مختصر (عربي)</label>
                                <textarea v-model="form.short_description_ar" rows="3" class="input-admin resize-none" placeholder="سطر أو سطرين للعرض في البطاقات"></textarea>
                                <InputError :message="form.errors.short_description_ar" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">وصف مختصر (إنجليزي)</label>
                                <textarea v-model="form.short_description_en" rows="3" class="input-admin resize-none" placeholder="Short summary shown in cards"></textarea>
                                <InputError :message="form.errors.short_description_en" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف التفصيلي (عربي)</label>
                                <textarea v-model="form.description_ar" rows="5" class="input-admin resize-none" placeholder="التفاصيل الكاملة للمنتج..."></textarea>
                                <InputError :message="form.errors.description_ar" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف التفصيلي (إنجليزي)</label>
                                <textarea v-model="form.description_en" rows="5" class="input-admin resize-none" placeholder="Full product details..."></textarea>
                                <InputError :message="form.errors.description_en" class="mt-1.5" />
                            </div>
                        </div>
                    </section>

                    <!-- التفاصيل الإضافية -->
                    <section class="card-admin p-5 sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-violet-50 text-violet-600">
                                <AdminIcon name="layers" size="sm" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-surface-900">المواصفات</h3>
                                <p class="text-xs text-surface-400">الأبعاد، الخامة، واللون.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">الأبعاد (عربي)</label>
                                <input v-model="form.dimensions_ar" type="text" class="input-admin" placeholder="مثال: 80 × 40 × 180 سم" />
                                <InputError :message="form.errors.dimensions_ar" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">الأبعاد (إنجليزي)</label>
                                <input v-model="form.dimensions_en" type="text" class="input-admin" placeholder="e.g. 80 × 40 × 180 cm" />
                                <InputError :message="form.errors.dimensions_en" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">الخامة (عربي)</label>
                                <input v-model="form.material_ar" type="text" class="input-admin" placeholder="مثال: خشب بلوط" />
                                <InputError :message="form.errors.material_ar" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">الخامة (إنجليزي)</label>
                                <input v-model="form.material_en" type="text" class="input-admin" placeholder="e.g. Oak wood" />
                                <InputError :message="form.errors.material_en" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">اللون (عربي)</label>
                                <input v-model="form.color_ar" type="text" class="input-admin" placeholder="مثال: بيج فاتح" />
                                <InputError :message="form.errors.color_ar" class="mt-1.5" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">اللون (إنجليزي)</label>
                                <input v-model="form.color_en" type="text" class="input-admin" placeholder="e.g. Light beige" />
                                <InputError :message="form.errors.color_en" class="mt-1.5" />
                            </div>
                        </div>
                    </section>

                    <!-- صورة الغلاف -->
                    <section class="card-admin p-5 sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                                <AdminIcon name="image" size="sm" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-surface-900">صورة الغلاف</h3>
                                <p class="text-xs text-surface-400">الصورة الرئيسية المعروضة في بطاقات المنتج.</p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="grid h-40 w-full shrink-0 place-items-center overflow-hidden rounded-2xl bg-surface-100 ring-1 ring-surface-200 sm:w-64">
                                <img v-if="coverPreview" :src="coverPreview" alt="معاينة الغلاف" class="h-full w-full object-cover" />
                                <span v-else class="flex flex-col items-center gap-1 text-xs text-surface-400">
                                    <AdminIcon name="image" size="lg" />
                                    بدون غلاف
                                </span>
                            </div>

                            <div class="flex-1">
                                <label for="cover-upload" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-surface-300 bg-surface-50 px-4 py-2.5 text-sm font-semibold text-surface-600 transition hover:border-brand-400 hover:text-brand-600">
                                    <AdminIcon name="plus" size="sm" />
                                    {{ coverPreview ? 'استبدال الصورة' : 'اختيار صورة' }}
                                </label>
                                <input id="cover-upload" type="file" accept="image/jpeg,image/png,image/webp,image/avif" class="sr-only" @change="onCoverChange" />
                                <p class="mt-2 text-xs text-surface-400">صيغ JPG أو PNG أو WebP — بحد أقصى 5MB.</p>
                                <InputError :message="form.errors.cover_image" class="mt-1.5" />
                            </div>
                        </div>
                    </section>

                    <!-- معرض الصور -->
                    <section class="card-admin p-5 sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-rose-50 text-rose-600">
                                <AdminIcon name="collection" size="sm" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-surface-900">معرض الصور</h3>
                                <p class="text-xs text-surface-400">صور إضافية تُعرض في صفحة المنتج.</p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div v-if="existingImages.length || newGallery.length" class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                                <div v-for="img in existingImages" :key="img.id" class="group relative aspect-square overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200" :class="{ 'ring-red-300 opacity-40': removedIds.includes(img.id) }">
                                    <img :src="img.url" :alt="img.alt || 'صورة المنتج'" class="h-full w-full object-cover" />
                                    <button
                                        type="button"
                                        class="absolute top-1.5 end-1.5 grid h-7 w-7 place-items-center rounded-lg bg-white/90 text-red-600 shadow-sm transition hover:bg-red-600 hover:text-white"
                                        :title="removedIds.includes(img.id) ? 'تراجع عن الحذف' : 'إزالة الصورة'"
                                        @click="removedIds.includes(img.id)
                                            ? removedIds.splice(removedIds.indexOf(img.id), 1)
                                            : removeExistingImage(img.id)"
                                    >
                                        <AdminIcon name="trash" size="sm" />
                                    </button>
                                    <span v-if="removedIds.includes(img.id)" class="absolute inset-x-0 bottom-0 bg-red-500/90 py-0.5 text-center text-[10px] font-bold text-white">سيتم الحذف</span>
                                </div>

                                <div v-for="(item, index) in newGallery" :key="item.preview" class="group relative aspect-square overflow-hidden rounded-xl ring-1 ring-brand-300">
                                    <img :src="item.preview" :alt="'صورة جديدة ' + (index + 1)" class="h-full w-full object-cover" />
                                    <button
                                        type="button"
                                        class="absolute top-1.5 end-1.5 grid h-7 w-7 place-items-center rounded-lg bg-white/90 text-surface-600 shadow-sm transition hover:bg-red-600 hover:text-white"
                                        title="إزالة"
                                        @click="removeNewImage(index)"
                                    >
                                        <AdminIcon name="close" size="sm" />
                                    </button>
                                </div>
                            </div>
                            <p v-else class="mb-4 rounded-xl border border-dashed border-surface-200 px-4 py-6 text-center text-sm text-surface-400">
                                لا توجد صور معرض بعد.
                            </p>

                            <label for="gallery-upload" class="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-surface-300 bg-surface-50 px-4 py-2.5 text-sm font-semibold text-surface-600 transition hover:border-brand-400 hover:text-brand-600">
                                <AdminIcon name="plus" size="sm" />
                                إضافة صور
                            </label>
                            <input id="gallery-upload" type="file" multiple accept="image/jpeg,image/png,image/webp,image/avif" class="sr-only" @change="onGalleryChange" />
                            <InputError :message="form.errors.gallery_images" class="mt-1.5" />
                            <p class="mt-2 text-xs text-surface-400">يمكنك اختيار عدة صور دفعة واحدة — بحد أقصى 20 صورة و 5MB للصورة.</p>
                        </div>
                    </section>
                </div>

                <!-- Footer actions -->
                <div class="sticky bottom-4 z-10 mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-surface-100 bg-white/95 p-3.5 shadow-premium-lg backdrop-blur">
                    <p v-if="form.isDirty" class="hidden text-xs text-surface-400 sm:block">
                        لديك تعديلات غير محفوظة.
                    </p>
                    <div class="flex flex-1 items-center justify-end gap-3">
                        <Link :href="route('admin.products.index')" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700">
                            إلغاء
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                        >
                            <AdminIcon :name="form.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': form.processing }" />
                            {{ form.processing ? 'جارٍ الحفظ...' : isEdit ? 'حفظ التعديلات' : 'إضافة المنتج' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>