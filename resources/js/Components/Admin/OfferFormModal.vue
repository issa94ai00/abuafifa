<script setup>
/**
 * Create / edit form for an offer. Shared by the offers list and the offer
 * detail page so both write through exactly the same payload.
 */
import { computed, nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import ImageDropzone from '@/Components/Admin/ImageDropzone.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    /** The offer being edited, or null when creating. */
    offer: { type: Object, default: null },
    /** Every product available to attach. */
    products: { type: Array, default: () => [] },
    currency: { type: String, default: 'د.أ' },
});

const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !!props.offer?.id);

const form = useForm({
    title_ar: '',
    title_en: '',
    slug: '',
    description_ar: '',
    description_en: '',
    discount_type: 'percentage',
    discount_value: '',
    price_override: '',
    starts_at: '',
    ends_at: '',
    sort_order: 0,
    is_active: true,
    image: null,
    remove_image: false,
    sync_products: 1,
    products: [],
});

// ----------------------------------------------------------------- date glue
// The server speaks UTC ISO strings; <input type="datetime-local"> speaks
// naive browser-local time. Converting on both edges keeps a date entered as
// "2pm" from drifting by the timezone offset on the way to the database.
function isoToLocalInput(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function localInputToIso(value) {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date.toISOString();
}

// ------------------------------------------------------------------- image
// Previews, drag & drop and client-side type/size checks live in the
// shared dropzone; this only tracks whether the stored image should go.
const storedImage = computed(() => (form.remove_image ? null : props.offer?.image_url ?? null));

function onImagePicked(file) {
    form.image = file;
    if (file) form.remove_image = false;
}

function onRemoveStoredImage() {
    form.image = null;
    // Only the edit form has a stored image worth asking the server to drop.
    form.remove_image = isEdit.value;
}

// --------------------------------------------------------------- populating
const titleInput = ref(null);

function populate() {
    const offer = props.offer;

    form.clearErrors();
    form.defaults({
        title_ar: offer?.title_ar ?? '',
        title_en: offer?.title_en ?? '',
        slug: offer?.slug ?? '',
        description_ar: offer?.description_ar ?? '',
        description_en: offer?.description_en ?? '',
        discount_type: offer?.discount_type ?? 'percentage',
        discount_value: offer?.discount_value ?? '',
        price_override: offer?.price_override ?? '',
        starts_at: isoToLocalInput(offer?.starts_at),
        ends_at: isoToLocalInput(offer?.ends_at),
        sort_order: offer?.sort_order ?? 0,
        is_active: offer ? !!offer.is_active : true,
        image: null,
        remove_image: false,
        sync_products: 1,
        products: [...(offer?.product_ids ?? [])],
    });
    form.reset();

    // form.image going back to null makes the dropzone drop its preview.
    productSearch.value = '';
}

watch(
    () => [props.show, props.offer?.id],
    ([visible]) => {
        if (visible) {
            populate();
            nextTick(() => titleInput.value?.focus());
        }
    },
    { immediate: true },
);

// ---------------------------------------------------------------- products
const productSearch = ref('');

const filteredProducts = computed(() => {
    const term = productSearch.value.trim().toLowerCase();
    if (!term) return props.products;

    return props.products.filter((product) =>
        [product.name_ar, product.name_en, product.slug]
            .filter(Boolean)
            .some((field) => String(field).toLowerCase().includes(term)),
    );
});

const selectedCount = computed(() => form.products.length);

function toggleProduct(id) {
    const index = form.products.indexOf(id);
    if (index === -1) form.products.push(id);
    else form.products.splice(index, 1);
}

function isSelected(id) {
    return form.products.includes(id);
}

function selectAllVisible() {
    for (const product of filteredProducts.value) {
        if (!isSelected(product.id)) form.products.push(product.id);
    }
}

function clearProducts() {
    form.products = [];
}

// ----------------------------------------------------------------- discount
const discountTypes = [
    { value: 'percentage', label: 'نسبة مئوية', hint: 'يُخصم جزء من سعر كل منتج', icon: 'trending-up' },
    { value: 'fixed', label: 'مبلغ ثابت', hint: 'يُخصم مبلغ ثابت من السعر', icon: 'tag' },
    { value: 'custom_price', label: 'سعر مخصص', hint: 'يُستبدل السعر بسعر العرض', icon: 'star' },
];

const isCustomPrice = computed(() => form.discount_type === 'custom_price');

// -------------------------------------------------------------------- dates
const dateWarning = computed(() => {
    if (!form.starts_at || !form.ends_at) return null;
    return new Date(form.ends_at) < new Date(form.starts_at)
        ? 'تاريخ الانتهاء يسبق تاريخ البدء.'
        : null;
});

// ------------------------------------------------------------------- submit
function submit() {
    form
        .transform((data) => {
            const payload = {
                ...data,
                title_en: data.title_en || null,
                slug: data.slug || null,
                description_ar: data.description_ar || null,
                description_en: data.description_en || null,
                starts_at: localInputToIso(data.starts_at),
                ends_at: localInputToIso(data.ends_at),
                sort_order: Number(data.sort_order) || 0,
                // Only the field the chosen discount type actually uses is
                // sent; the server clears the other one either way.
                discount_value: isCustomPrice.value || data.discount_value === ''
                    ? null
                    : Number(data.discount_value),
                price_override: !isCustomPrice.value || data.price_override === ''
                    ? null
                    : Number(data.price_override),
            };

            if (isEdit.value) payload._method = 'put';

            return payload;
        })
        .post(
            isEdit.value
                ? route('admin.offers.update', props.offer.id)
                : route('admin.offers.store'),
            {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    emit('saved');
                    emit('close');
                },
            },
        );
}

function close() {
    if (form.processing) return;
    emit('close');
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <div class="flex max-h-[88vh] flex-col">
            <!-- Header -->
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                        <AdminIcon name="tag" size="sm" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-bold text-surface-900">
                            {{ isEdit ? 'تعديل العرض' : 'إضافة عرض جديد' }}
                        </h3>
                        <p class="truncate text-xs text-surface-400">
                            {{ isEdit ? `تعديل «${offer.title_ar}»` : 'حدّد الخصم والمدة والمنتجات المشمولة.' }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-surface-400 transition hover:bg-surface-100 hover:text-surface-700"
                    aria-label="إغلاق"
                    @click="close"
                >
                    <AdminIcon name="close" size="sm" />
                </button>
            </div>

            <!-- Body -->
            <form id="offer-form" class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6" novalidate @submit.prevent="submit">
                <div v-if="form.errors.error" class="mb-4 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    <AdminIcon name="alert" size="sm" />
                    {{ form.errors.error }}
                </div>

                <!-- Titles -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="offer-title-ar" class="mb-1.5 block text-sm font-semibold text-surface-700">
                            عنوان العرض بالعربية <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="offer-title-ar"
                            ref="titleInput"
                            v-model="form.title_ar"
                            type="text"
                            class="input-admin"
                            placeholder="مثال: تخفيضات الموسم"
                        />
                        <InputError :message="form.errors.title_ar" class="mt-1.5" />
                    </div>
                    <div>
                        <label for="offer-title-en" class="mb-1.5 block text-sm font-semibold text-surface-700">عنوان العرض بالإنجليزية</label>
                        <input id="offer-title-en" v-model="form.title_en" type="text" class="input-admin" dir="ltr" placeholder="e.g. Season Sale" />
                        <InputError :message="form.errors.title_en" class="mt-1.5" />
                    </div>
                </div>

                <!-- Discount type -->
                <fieldset class="mt-6">
                    <legend class="mb-2 text-sm font-semibold text-surface-700">
                        نوع الخصم <span class="text-red-500">*</span>
                    </legend>
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                        <label
                            v-for="type in discountTypes"
                            :key="type.value"
                            class="flex cursor-pointer items-start gap-2.5 rounded-xl border p-3 transition"
                            :class="form.discount_type === type.value
                                ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500'
                                : 'border-surface-200 bg-white hover:border-surface-300 hover:bg-surface-50'"
                        >
                            <input v-model="form.discount_type" type="radio" :value="type.value" class="sr-only" />
                            <span
                                class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg transition"
                                :class="form.discount_type === type.value ? 'bg-brand-600 text-white' : 'bg-surface-100 text-surface-500'"
                            >
                                <AdminIcon :name="type.icon" size="sm" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-surface-900">{{ type.label }}</span>
                                <span class="mt-0.5 block text-xs leading-5 text-surface-400">{{ type.hint }}</span>
                            </span>
                        </label>
                    </div>
                    <InputError :message="form.errors.discount_type" class="mt-1.5" />
                </fieldset>

                <!-- Discount value -->
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-if="!isCustomPrice">
                        <label for="offer-discount-value" class="mb-1.5 block text-sm font-semibold text-surface-700">
                            قيمة الخصم <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="offer-discount-value"
                                v-model="form.discount_value"
                                type="number"
                                min="0"
                                :max="form.discount_type === 'percentage' ? 100 : undefined"
                                step="0.01"
                                class="input-admin pe-16"
                                placeholder="0"
                            />
                            <span class="pointer-events-none absolute end-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-surface-400">
                                {{ form.discount_type === 'percentage' ? '٪' : currency }}
                            </span>
                        </div>
                        <InputError :message="form.errors.discount_value" class="mt-1.5" />
                    </div>

                    <div v-else>
                        <label for="offer-price-override" class="mb-1.5 block text-sm font-semibold text-surface-700">
                            السعر المخصص <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="offer-price-override"
                                v-model="form.price_override"
                                type="number"
                                min="0"
                                step="0.01"
                                class="input-admin pe-16"
                                placeholder="0"
                            />
                            <span class="pointer-events-none absolute end-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-surface-400">
                                {{ currency }}
                            </span>
                        </div>
                        <InputError :message="form.errors.price_override" class="mt-1.5" />
                    </div>

                    <div>
                        <label for="offer-slug" class="mb-1.5 block text-sm font-semibold text-surface-700">الرابط (SLUG)</label>
                        <input id="offer-slug" v-model="form.slug" type="text" class="input-admin" dir="ltr" placeholder="يُولَّد تلقائياً من العنوان" />
                        <InputError :message="form.errors.slug" class="mt-1.5" />
                    </div>
                </div>

                <!-- Schedule -->
                <div class="mt-6 rounded-xl border border-surface-200 bg-surface-50/60 p-4">
                    <div class="flex items-center gap-2">
                        <AdminIcon name="calendar" size="sm" class="text-surface-400" />
                        <h4 class="text-sm font-semibold text-surface-700">مدة العرض</h4>
                        <span class="text-xs text-surface-400">— اتركها فارغة ليبقى العرض ساري المفعول دائماً</span>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="offer-starts-at" class="mb-1.5 block text-xs font-semibold text-surface-500">يبدأ في</label>
                            <input id="offer-starts-at" v-model="form.starts_at" type="datetime-local" class="input-admin" />
                            <InputError :message="form.errors.starts_at" class="mt-1.5" />
                        </div>
                        <div>
                            <label for="offer-ends-at" class="mb-1.5 block text-xs font-semibold text-surface-500">ينتهي في</label>
                            <input id="offer-ends-at" v-model="form.ends_at" type="datetime-local" class="input-admin" />
                            <InputError :message="form.errors.ends_at" class="mt-1.5" />
                        </div>
                    </div>
                    <p v-if="dateWarning" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-amber-600">
                        <AdminIcon name="alert" size="xs" />
                        {{ dateWarning }}
                    </p>
                </div>

                <!-- Descriptions -->
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="offer-desc-ar" class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف (عربي)</label>
                        <textarea id="offer-desc-ar" v-model="form.description_ar" rows="3" class="input-admin resize-none" placeholder="تفاصيل العرض كما تظهر للزبون..."></textarea>
                        <InputError :message="form.errors.description_ar" class="mt-1.5" />
                    </div>
                    <div>
                        <label for="offer-desc-en" class="mb-1.5 block text-sm font-semibold text-surface-700">الوصف (إنجليزي)</label>
                        <textarea id="offer-desc-en" v-model="form.description_en" rows="3" class="input-admin resize-none" dir="ltr" placeholder="Offer details..."></textarea>
                        <InputError :message="form.errors.description_en" class="mt-1.5" />
                    </div>
                </div>

                <!-- Image -->
                <div class="mt-6">
                    <label class="mb-1.5 block text-sm font-semibold text-surface-700">صورة العرض</label>
                    <ImageDropzone
                        :model-value="form.image"
                        :existing-url="storedImage"
                        :max-size-mb="5"
                        :progress="form.progress ? form.progress.percentage : null"
                        label="اسحب صورة العرض هنا أو اضغط للاختيار"
                        @update:model-value="onImagePicked"
                        @remove-existing="onRemoveStoredImage"
                    />
                    <InputError :message="form.errors.image" class="mt-1.5" />
                </div>

                <!-- Products -->
                <div class="mt-6 overflow-hidden rounded-xl border border-surface-200">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-surface-200 bg-surface-50/70 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <AdminIcon name="box" size="sm" class="text-surface-400" />
                            <h4 class="text-sm font-semibold text-surface-700">المنتجات المشمولة</h4>
                            <span
                                class="inline-flex min-w-[20px] items-center justify-center rounded-full px-2 text-[11px] font-bold"
                                :class="selectedCount ? 'bg-brand-100 text-brand-700' : 'bg-surface-200 text-surface-500'"
                            >
                                {{ selectedCount }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-brand-600 transition hover:bg-brand-50" @click="selectAllVisible">
                                تحديد الظاهر
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-2.5 py-1 text-xs font-semibold text-surface-500 transition hover:bg-surface-100 disabled:opacity-40"
                                :disabled="!selectedCount"
                                @click="clearProducts"
                            >
                                إلغاء الكل
                            </button>
                        </div>
                    </div>

                    <div class="border-b border-surface-100 p-3">
                        <div class="relative">
                            <AdminIcon name="search" size="sm" class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-surface-400" />
                            <input v-model="productSearch" type="search" class="input-admin ps-10" placeholder="بحث عن منتج..." />
                        </div>
                    </div>

                    <div class="max-h-64 overflow-y-auto">
                        <label
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="flex cursor-pointer items-center gap-3 border-b border-surface-100 px-4 py-2.5 transition last:border-b-0"
                            :class="isSelected(product.id) ? 'bg-brand-50/60' : 'hover:bg-surface-50'"
                        >
                            <input
                                type="checkbox"
                                class="h-4 w-4 shrink-0 rounded border-surface-300 text-brand-600 focus:ring-brand-500"
                                :checked="isSelected(product.id)"
                                @change="toggleProduct(product.id)"
                            />
                            <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-lg bg-surface-100 text-surface-400 ring-1 ring-surface-200">
                                <img v-if="product.cover_thumb_url" :src="product.cover_thumb_url" :alt="product.name_ar" class="h-full w-full object-cover" loading="lazy" />
                                <AdminIcon v-else name="box" size="sm" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-surface-900">{{ product.name_ar }}</span>
                                <span v-if="!product.is_active" class="text-[11px] font-semibold text-amber-600">منتج مخفي</span>
                            </span>
                            <span class="shrink-0 text-xs font-bold tabular-nums text-surface-500">
                                {{ product.price !== null ? `${product.price} ${currency}` : '—' }}
                            </span>
                        </label>

                        <p v-if="!filteredProducts.length" class="px-4 py-8 text-center text-sm text-surface-400">
                            {{ products.length ? 'لا توجد منتجات مطابقة للبحث.' : 'لا توجد منتجات بعد.' }}
                        </p>
                    </div>
                    <InputError :message="form.errors.products" class="px-4 pb-3" />
                </div>

                <!-- Visibility + order -->
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="offer-sort-order" class="mb-1.5 block text-sm font-semibold text-surface-700">ترتيب العرض</label>
                        <input id="offer-sort-order" v-model="form.sort_order" type="number" min="0" class="input-admin" />
                        <p class="mt-1.5 text-xs text-surface-400">الأصغر يظهر أولاً في صفحة العروض.</p>
                        <InputError :message="form.errors.sort_order" class="mt-1.5" />
                    </div>
                    <div class="flex items-start pt-7">
                        <button type="button" class="inline-flex items-center gap-2.5 text-sm font-medium text-surface-700" @click="form.is_active = !form.is_active">
                            <span
                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition"
                                :class="form.is_active ? 'bg-emerald-500' : 'bg-surface-300'"
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition"
                                    :class="form.is_active ? 'translate-x-1' : 'translate-x-[26px]'"
                                ></span>
                            </span>
                            <span class="inline-flex items-center gap-1.5" :class="form.is_active ? 'text-emerald-700' : 'text-surface-400'">
                                <AdminIcon :name="form.is_active ? 'eye' : 'eye-off'" size="sm" />
                                {{ form.is_active ? 'ظاهر في الموقع' : 'مخفي' }}
                            </span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Footer -->
            <div class="flex shrink-0 items-center justify-between gap-3 border-t border-surface-100 px-5 py-4 sm:px-6">
                <p class="hidden text-xs text-surface-400 sm:block">
                    الحقول المعلَّمة بـ <span class="text-red-500">*</span> إلزامية.
                </p>
                <div class="flex flex-1 items-center justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700 disabled:opacity-50"
                        :disabled="form.processing"
                        @click="close"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        form="offer-form"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                    >
                        <AdminIcon :name="form.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': form.processing }" />
                        {{ form.processing ? 'جارٍ الحفظ...' : isEdit ? 'حفظ التعديلات' : 'إضافة العرض' }}
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
