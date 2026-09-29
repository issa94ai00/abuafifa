<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import Modal from '@/Components/Modal.vue';
import { usePrice } from '@/Composables/usePrice';

const props = defineProps({ product: Object });

const { formatPrice, discountPercentage } = usePrice();

const availabilityMeta = {
    available: { label: 'متوفر', cls: 'bg-emerald-50 text-emerald-700 ring-emerald-200', icon: 'check' },
    on_order: { label: 'يُطلب مسبقاً', cls: 'bg-amber-50 text-amber-700 ring-amber-200', icon: 'clock' },
    sold_out: { label: 'نفدت الكمية', cls: 'bg-red-50 text-red-700 ring-red-200', icon: 'alert' },
};

const avail = computed(() => availabilityMeta[props.product?.availability_status] ?? availabilityMeta.available);

const price = computed(() => props.product?.price ?? 0);
const salePrice = computed(() => props.product?.sale_price ?? null);
const effective = computed(() => salePrice.value ?? price.value);
const discount = computed(() => discountPercentage(price.value, salePrice.value));

const confirmDelete = ref(false);
const deleting = ref(false);

function deleteProduct() {
    deleting.value = true;
    router.delete(route('admin.products.destroy', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDelete.value = false;
            deleting.value = false;
        },
        onError: () => {
            deleting.value = false;
        },
    });
}

const infoRows = computed(() => [
    { label: 'رمز المنتج (SKU)', value: props.product?.sku || '—', mono: true },
    { label: 'التصنيف', value: props.product?.category || '—' },
    { label: 'عدد المشاهدات', value: props.product?.views_count ?? 0 },
    { label: 'تاريخ الإنشاء', value: props.product?.created_at ? new Date(props.product.created_at).toLocaleDateString('ar-JO') : '—' },
    { label: 'آخر تحديث', value: props.product?.updated_at ? new Date(props.product.updated_at).toLocaleDateString('ar-JO') : '—' },
]);
</script>

<template>
    <Head title="تفاصيل المنتج" />

    <AdminLayout>
        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.products.index')" class="grid h-10 w-10 place-items-center rounded-xl border border-surface-200 bg-white text-surface-500 transition hover:bg-surface-50 hover:text-surface-700" title="العودة إلى المنتجات">
                        <AdminIcon name="chevron-right" size="sm" />
                    </Link>
                    <div class="min-w-0">
                        <h2 class="truncate font-display text-xl font-bold text-surface-900 sm:text-2xl">{{ product?.name_ar }}</h2>
                        <p v-if="product?.name_en" class="truncate text-sm text-surface-400">{{ product.name_en }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.products.edit', product.id)"
                        class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                    >
                        <AdminIcon name="edit" size="sm" />
                        تعديل
                    </Link>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                        @click="confirmDelete = true"
                    >
                        <AdminIcon name="trash" size="sm" />
                        حذف
                    </button>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main column -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Cover -->
                    <div class="card-admin overflow-hidden">
                        <div class="grid aspect-[16/9] place-items-center bg-surface-100">
                            <img v-if="product?.cover_url" :src="product.cover_url" :alt="product.name_ar" class="h-full w-full object-cover" />
                            <span v-else class="flex flex-col items-center gap-2 text-sm text-surface-400">
                                <AdminIcon name="image" size="xl" />
                                لا توجد صورة غلاف
                            </span>
                        </div>

                        <!-- Gallery -->
                        <div v-if="product?.gallery?.length" class="border-t border-surface-100 p-4">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wider text-surface-400">
                                معرض الصور ({{ product.gallery.length }})
                            </p>
                            <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-6">
                                <div v-for="img in product.gallery" :key="img.id" class="overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200">
                                    <img :src="img.url" :alt="product.name_ar" class="aspect-square w-full object-cover transition hover:scale-105" loading="lazy" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="card-admin p-5 sm:p-6">
                        <h3 class="text-sm font-bold text-surface-900">الوصف</h3>
                        <div class="mt-4 space-y-5">
                            <div v-if="product?.short_description_ar || product?.short_description_en" class="rounded-xl bg-brand-50/60 p-4 text-sm leading-7 text-surface-700">
                                <span v-if="product?.short_description_ar" class="block" dir="rtl">{{ product.short_description_ar }}</span>
                                <span v-if="product?.short_description_en" class="mt-1 block text-surface-500" dir="ltr">{{ product.short_description_en }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-surface-700">الوصف التفصيلي</p>
                                <div class="mt-2 space-y-4 text-sm leading-7 text-surface-600">
                                    <p v-if="product?.description_ar" dir="rtl" class="whitespace-pre-line">{{ product.description_ar }}</p>
                                    <p v-else class="text-surface-400">لا يوجد وصف تفصيلي.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status card -->
                    <div class="card-admin p-5">
                        <h3 class="text-sm font-bold text-surface-900">الحالة</h3>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="badge-admin ring-1 ring-inset" :class="product?.is_active ? 'bg-sky-50 text-sky-700 ring-sky-200' : 'bg-surface-100 text-surface-500 ring-surface-200'">
                                <AdminIcon :name="product?.is_active ? 'eye' : 'eye-off'" size="xs" />
                                {{ product?.is_active ? 'ظاهر للتسوق' : 'مخفي' }}
                            </span>
                            <span class="badge-admin ring-1 ring-inset" :class="avail.cls">
                                <AdminIcon :name="avail.icon" size="xs" />
                                {{ avail.label }}
                            </span>
                            <span v-if="product?.is_featured" class="badge-admin bg-gold-50 text-gold-600 ring-1 ring-inset ring-gold-200">
                                <AdminIcon name="star" size="xs" />
                                مميز
                            </span>
                            <span v-if="product?.is_new" class="badge-admin bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200">
                                <AdminIcon name="sparkles" size="xs" />
                                وصل حديثاً
                            </span>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="card-admin p-5">
                        <h3 class="text-sm font-bold text-surface-900">السعر</h3>
                        <div class="mt-4 flex items-end gap-3">
                            <span class="text-2xl font-bold tabular-nums text-surface-900">{{ formatPrice(effective, product?.currency) }}</span>
                            <span v-if="discount" class="text-sm font-medium tabular-nums text-surface-400 line-through">{{ formatPrice(price, product?.currency) }}</span>
                        </div>
                        <span v-if="discount" class="badge-admin mt-3 bg-red-50 text-red-600">
                            خصم {{ discount }}%
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="card-admin p-5">
                        <h3 class="text-sm font-bold text-surface-900">معلومات المنتج</h3>
                        <dl class="mt-4 space-y-3">
                            <div v-for="row in infoRows" :key="row.label" class="flex items-center justify-between gap-4">
                                <dt class="text-sm text-surface-400">{{ row.label }}</dt>
                                <dd class="truncate text-sm font-semibold text-surface-700" :class="row.mono ? 'font-mono' : ''" :dir="row.mono ? 'ltr' : 'rtl'">{{ row.value }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Storefront link -->
                    <Link
                        v-if="product?.slug"
                        :href="route('products.show', product.slug)"
                        class="card-admin group flex items-center justify-between gap-3 p-5 transition hover:shadow-premium-lg"
                    >
                        <span class="flex items-center gap-3">
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-600 group-hover:text-white">
                                <AdminIcon name="external" size="sm" />
                            </span>
                            <span>
                                <span class="block text-sm font-bold text-surface-900">عرض في المتجر</span>
                                <span class="block text-xs text-surface-400">الصفحة العامة للمنتج</span>
                            </span>
                        </span>
                        <AdminIcon name="chevron-left" size="sm" class="text-surface-300" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- Delete confirmation -->
        <Modal :show="confirmDelete" max-width="md" @close="confirmDelete = false">
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600">
                        <AdminIcon name="trash" size="lg" />
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-surface-900">حذف المنتج</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم نقل «<span class="font-semibold text-surface-900">{{ product?.name_ar }}</span>» إلى سلة المحذوفات، ويمكنك استعادته لاحقاً.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50" @click="confirmDelete = false">
                        إلغاء
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-premium transition hover:bg-red-700 disabled:opacity-60" :disabled="deleting" @click="deleteProduct">
                        <AdminIcon name="trash" size="sm" />
                        {{ deleting ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>