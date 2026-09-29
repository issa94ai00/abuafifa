<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import AdminToast from '@/Components/Admin/AdminToast.vue';
import OfferFormModal from '@/Components/Admin/OfferFormModal.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    offer: Object,
    /** Products actually attached to this offer. */
    products: { type: Array, default: () => [] },
    /** Every product, for the attach picker in the edit form. */
    allProducts: { type: Array, default: () => [] },
    currency: { type: String, default: 'د.أ' },
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

const offer = computed(() => props.offer);

// ------------------------------------------------------------------ actions
const formOpen = ref(false);
const confirmDelete = ref(false);
const deleting = ref(false);
const toggling = ref(false);

function toggleActive() {
    toggling.value = true;
    router.patch(route('admin.offers.toggle-active', offer.value.id), {}, {
        preserveScroll: true,
        onFinish: () => (toggling.value = false),
    });
}

function deleteOffer() {
    deleting.value = true;
    router.delete(route('admin.offers.destroy', offer.value.id), {
        onSuccess: () => router.visit(route('admin.offers.index')),
        onFinish: () => (deleting.value = false),
    });
}

// ------------------------------------------------------------------ display
const STATES = {
    active: { label: 'نشط الآن', icon: 'check', cls: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
    upcoming: { label: 'قادم', icon: 'clock', cls: 'bg-amber-50 text-amber-700 ring-amber-200' },
    ended: { label: 'منتهي', icon: 'calendar', cls: 'bg-surface-100 text-surface-500 ring-surface-200' },
    hidden: { label: 'مخفي', icon: 'eye-off', cls: 'bg-surface-100 text-surface-600 ring-surface-200' },
};

const state = computed(() => STATES[offer.value?.state] ?? STATES.hidden);

function money(value) {
    if (value === null || value === undefined) return '—';
    const symbol = offer.value?.currency_symbol || props.currency;
    return `${new Intl.NumberFormat('ar-JO', { maximumFractionDigits: 2 }).format(value)} ${symbol}`;
}

function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return new Intl.DateTimeFormat('ar-JO', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(date);
}

const discountSummary = computed(() => {
    if (!offer.value) return '—';
    if (offer.value.discount_type === 'custom_price') {
        return offer.value.price_override !== null ? money(offer.value.price_override) : 'سعر خاص';
    }
    return offer.value.badge_label ?? '—';
});

const totalListPrice = computed(() =>
    props.products.reduce((sum, product) => sum + (Number(product.price) || 0), 0),
);
</script>

<template>
    <Head :title="offer ? offer.title_ar : 'تفاصيل العرض'" />

    <AdminLayout>
        <template #header>
            <p class="text-[11px] font-medium leading-4 text-surface-400">العروض</p>
            <h1 class="truncate text-base font-bold text-surface-900 sm:text-lg">
                {{ offer?.title_ar ?? 'تفاصيل العرض' }}
            </h1>
        </template>

        <!-- Header / actions -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link
                :href="route('admin.offers.index')"
                class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
            >
                <AdminIcon name="chevron-right" size="sm" />
                العودة إلى العروض
            </Link>

            <div v-if="offer" class="flex flex-wrap items-center gap-2">
                <a
                    v-if="offer.slug && offer.state === 'active'"
                    :href="route('offers.show', offer.slug)"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                >
                    <AdminIcon name="external" size="sm" />
                    معاينة في الموقع
                </a>
                <button
                    v-if="can('offer.edit')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition disabled:opacity-50"
                    :class="offer.is_active
                        ? 'border-surface-200 bg-white text-surface-700 hover:bg-surface-50'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                    :disabled="toggling"
                    @click="toggleActive"
                >
                    <AdminIcon :name="toggling ? 'refresh' : offer.is_active ? 'eye-off' : 'eye'" size="sm" :class="{ 'animate-spin': toggling }" />
                    {{ offer.is_active ? 'إخفاء العرض' : 'إظهار العرض' }}
                </button>
                <button
                    v-if="can('offer.delete')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                    @click="confirmDelete = true"
                >
                    <AdminIcon name="trash" size="sm" />
                    حذف
                </button>
                <button
                    v-if="can('offer.edit')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98]"
                    @click="formOpen = true"
                >
                    <AdminIcon name="edit" size="sm" />
                    تعديل العرض
                </button>
            </div>
        </div>

        <div v-if="offer" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main card -->
            <div class="card-admin animate-fade-in-up overflow-hidden lg:col-span-2">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="text-xl font-bold text-surface-900">{{ offer.title_ar }}</h2>
                            <p v-if="offer.title_en" class="mt-1 truncate text-sm text-surface-400" dir="ltr">{{ offer.title_en }}</p>
                            <p v-if="offer.slug" class="mt-1 truncate text-xs text-surface-300" dir="ltr">/{{ offer.slug }}</p>
                        </div>
                        <span class="badge-admin shrink-0 ring-1 ring-inset" :class="state.cls">
                            <AdminIcon :name="state.icon" size="xs" />
                            {{ state.label }}
                        </span>
                    </div>

                    <!-- Meta grid -->
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="rounded-xl bg-surface-50 p-4">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-surface-400">
                                <AdminIcon name="tag" size="sm" />
                                الخصم
                            </div>
                            <p class="mt-1.5 text-lg font-bold tabular-nums text-surface-900">{{ discountSummary }}</p>
                            <p class="mt-0.5 text-xs text-surface-400">{{ offer.discount_type_label }}</p>
                        </div>
                        <div class="rounded-xl bg-surface-50 p-4">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-surface-400">
                                <AdminIcon name="calendar" size="sm" />
                                تاريخ البدء
                            </div>
                            <p class="mt-1.5 text-sm font-bold tabular-nums text-surface-900">{{ formatDateTime(offer.starts_at) }}</p>
                        </div>
                        <div class="rounded-xl bg-surface-50 p-4">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-surface-400">
                                <AdminIcon name="clock" size="sm" />
                                تاريخ الانتهاء
                            </div>
                            <p class="mt-1.5 text-sm font-bold tabular-nums text-surface-900">{{ formatDateTime(offer.ends_at) }}</p>
                        </div>
                    </div>

                    <div v-if="offer.description_ar || offer.description_en" class="mt-6 border-t border-surface-100 pt-6">
                        <h3 class="text-sm font-bold text-surface-900">وصف العرض</h3>
                        <p v-if="offer.description_ar" class="mt-2 whitespace-pre-line text-sm leading-7 text-surface-600">{{ offer.description_ar }}</p>
                        <p v-if="offer.description_en" class="mt-2 whitespace-pre-line text-sm leading-7 text-surface-400" dir="ltr">{{ offer.description_en }}</p>
                    </div>

                    <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-surface-100 pt-6 sm:grid-cols-4">
                        <div>
                            <dt class="text-xs font-semibold text-surface-400">ترتيب العرض</dt>
                            <dd class="mt-0.5 text-sm font-bold tabular-nums text-surface-900">{{ offer.sort_order }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-surface-400">المنتجات</dt>
                            <dd class="mt-0.5 text-sm font-bold tabular-nums text-surface-900">{{ products.length }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-surface-400">مجموع الأسعار</dt>
                            <dd class="mt-0.5 text-sm font-bold tabular-nums text-surface-900">{{ money(totalListPrice) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-surface-400">أُنشئ في</dt>
                            <dd class="mt-0.5 text-sm font-bold tabular-nums text-surface-900">{{ formatDateTime(offer.created_at) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Image card -->
            <div class="card-admin animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.05s' }">
                <p class="border-b border-surface-100 px-5 py-4 text-sm font-bold text-surface-900">صورة العرض</p>
                <div class="p-5">
                    <div v-if="offer.image_url" class="aspect-video overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200">
                        <img :src="offer.image_url" :alt="offer.title_ar" class="h-full w-full object-cover" loading="lazy" />
                    </div>
                    <button
                        v-else-if="can('offer.edit')"
                        type="button"
                        class="grid aspect-video w-full place-items-center rounded-xl border border-dashed border-surface-300 bg-surface-50 text-surface-400 transition hover:border-brand-400 hover:text-brand-600"
                        @click="formOpen = true"
                    >
                        <span class="flex flex-col items-center gap-1.5 text-xs font-semibold">
                            <AdminIcon name="image" size="xl" />
                            إضافة صورة للعرض
                        </span>
                    </button>
                    <div v-else class="grid aspect-video place-items-center rounded-xl bg-surface-100 text-surface-300 ring-1 ring-surface-200">
                        <AdminIcon name="image" size="xl" />
                    </div>

                    <p class="mt-4 text-xs leading-6 text-surface-400">
                        تتغير صورة العرض في صفحة المتجر العامة تلقائياً عند تحديث العرض.
                    </p>
                </div>
            </div>

            <!-- Products list -->
            <div class="card-admin animate-fade-in-up overflow-hidden lg:col-span-3" :style="{ animationDelay: '0.1s' }">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-surface-100 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <AdminIcon name="box" size="sm" class="text-surface-400" />
                        <h3 class="text-sm font-bold text-surface-900">المنتجات المشمولة بالعرض</h3>
                        <span v-if="products.length" class="badge-admin bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200">
                            {{ products.length }} منتج
                        </span>
                    </div>
                    <button
                        v-if="can('offer.edit')"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-600 transition hover:bg-brand-50"
                        @click="formOpen = true"
                    >
                        <AdminIcon name="plus" size="sm" />
                        تعديل المنتجات
                    </button>
                </div>

                <div v-if="products.length" class="divide-y divide-surface-100">
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="group flex items-center justify-between gap-4 px-5 py-3.5 transition-colors hover:bg-surface-50/60"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-lg bg-surface-100 text-surface-400 ring-1 ring-surface-200">
                                <img v-if="product.cover_thumb_url" :src="product.cover_thumb_url" :alt="product.name_ar" class="h-full w-full object-cover" loading="lazy" />
                                <AdminIcon v-else name="box" size="sm" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-surface-900">{{ product.name_ar }}</p>
                                <p class="truncate text-xs text-surface-400" dir="ltr">{{ product.slug }}</p>
                            </div>
                            <span v-if="!product.is_active" class="badge-admin shrink-0 bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">
                                <AdminIcon name="eye-off" size="xs" />
                                مخفي
                            </span>
                        </div>
                        <div class="flex shrink-0 items-center gap-4">
                            <span class="text-sm font-bold tabular-nums text-surface-900">{{ money(product.price) }}</span>
                            <Link
                                :href="route('admin.products.show', product.id)"
                                class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-brand-50 hover:text-brand-600"
                                title="عرض المنتج"
                            >
                                <AdminIcon name="eye" size="sm" />
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center px-6 py-14 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                        <AdminIcon name="box" size="lg" />
                    </span>
                    <p class="mt-3 text-sm text-surface-400">لا توجد منتجات مرتبطة بهذا العرض بعد.</p>
                    <button
                        v-if="can('offer.edit')"
                        type="button"
                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700"
                        @click="formOpen = true"
                    >
                        <AdminIcon name="plus" size="sm" />
                        ربط منتجات بالعرض
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit -->
        <OfferFormModal
            :show="formOpen"
            :offer="offer"
            :products="allProducts"
            :currency="currency"
            @close="formOpen = false"
        />

        <!-- Delete confirmation -->
        <Modal :show="confirmDelete" max-width="md" @close="confirmDelete = false">
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600">
                        <AdminIcon name="trash" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-surface-900">حذف العرض</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم نقل «<span class="font-semibold text-surface-900">{{ offer?.title_ar }}</span>» إلى سلة المحذوفات
                            ولن يظهر في الموقع. يمكنك استعادته لاحقاً من تبويب «المحذوفات».
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                        @click="confirmDelete = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-premium transition hover:bg-red-700 disabled:opacity-60"
                        :disabled="deleting"
                        @click="deleteOffer"
                    >
                        <AdminIcon :name="deleting ? 'refresh' : 'trash'" size="sm" :class="{ 'animate-spin': deleting }" />
                        {{ deleting ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>

        <AdminToast />
    </AdminLayout>
</template>
