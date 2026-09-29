<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import { usePrice } from '@/Composables/usePrice';

const props = defineProps({
    stats: Object,
    recent_messages: Array,
    recent_products: Array,
    recent_activity: Array,
});

const page = usePage();
const { formatPrice } = usePrice();

const user = computed(() => page.props.auth?.user ?? {});
const permissions = computed(() => user.value.permissions || []);
const roleLabel = computed(() => user.value.role_labels?.join('، ') || 'مسؤول');

const can = (p) => permissions.value.includes(p);

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'صباح الخير';
    if (h < 18) return 'مساء الخير';
    return 'مساء النور';
});

const todayLabel = computed(() =>
    new Intl.DateTimeFormat('ar-JO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date()),
);

const statCards = computed(() => {
    const s = props.stats || {};
    const cards = [];

    if (s.products !== undefined) {
        cards.push({
            key: 'products', label: 'المنتجات', value: s.products,
            icon: 'box', wrap: 'bg-brand-50 text-brand-600', ring: 'group-hover:ring-brand-200',
            hint: 'إجمالي المنتجات', href: route('admin.products.index'),
        });
    }
    if (s.featured_products !== undefined) {
        cards.push({
            key: 'featured', label: 'منتجات مميزة', value: s.featured_products,
            icon: 'star', wrap: 'bg-gold-50 text-gold-600', ring: 'group-hover:ring-gold-300',
            hint: 'في الواجهة المميزة', href: route('admin.products.index'),
        });
    }
    if (s.new_arrivals !== undefined) {
        cards.push({
            key: 'new_arrivals', label: 'وصل حديثاً', value: s.new_arrivals,
            icon: 'sparkles', wrap: 'bg-emerald-50 text-emerald-600', ring: 'group-hover:ring-emerald-200',
            hint: 'منتجات جديدة', href: route('admin.products.index'),
        });
    }
    if (s.categories !== undefined) {
        cards.push({
            key: 'categories', label: 'التصنيفات', value: s.categories,
            icon: 'folder', wrap: 'bg-sky-50 text-sky-600', ring: 'group-hover:ring-sky-200',
            hint: 'أقسام المعرض', href: route('admin.categories.index'),
        });
    }
    if (s.offers !== undefined) {
        cards.push({
            key: 'offers', label: 'العروض النشطة', value: s.offers,
            icon: 'tag', wrap: 'bg-violet-50 text-violet-600', ring: 'group-hover:ring-violet-200',
            hint: 'تخفيضات جارية', href: route('admin.offers.index'),
        });
    }
    if (s.galleries !== undefined) {
        cards.push({
            key: 'galleries', label: 'المعارض', value: s.galleries,
            icon: 'image', wrap: 'bg-rose-50 text-rose-600', ring: 'group-hover:ring-rose-200',
            hint: 'صفحات القسم', href: route('admin.galleries.index'),
        });
    }
    if (s.unread_messages !== undefined) {
        cards.push({
            key: 'messages', label: 'رسائل غير مقروءة', value: s.unread_messages,
            icon: 'mail', wrap: 'bg-red-50 text-red-600', ring: 'group-hover:ring-red-200',
            hint: 'من نموذج التواصل', href: route('admin.messages.index'),
        });
    }
    if (s.users !== undefined) {
        cards.push({
            key: 'users', label: 'المستخدمون', value: s.users,
            icon: 'users', wrap: 'bg-indigo-50 text-indigo-600', ring: 'group-hover:ring-indigo-200',
            hint: 'حسابات المسؤولين', href: route('admin.users.index'),
        });
    }
    return cards;
});

const quickActions = computed(() => {
    const actions = [];
    if (can('product.create')) {
        actions.push({
            label: 'إضافة منتج', desc: 'منتج جديد للمعرض', icon: 'box',
            href: route('admin.products.create'), tone: 'text-brand-600 bg-brand-50',
        });
    }
    if (can('contact.view')) {
        actions.push({
            label: 'الرسائل الواردة', desc: props.stats?.unread_messages ? `${props.stats.unread_messages} رسالة غير مقروءة` : 'مراسلات العملاء', icon: 'mail',
            href: route('admin.messages.index'), tone: 'text-red-600 bg-red-50',
        });
    }
    if (can('gallery.view')) {
        actions.push({
            label: 'إدارة المعرض', desc: 'أقسام وصور العرض', icon: 'image',
            href: route('admin.galleries.index'), tone: 'text-rose-600 bg-rose-50',
        });
    }
    if (can('activity.view')) {
        actions.push({
            label: 'سجل النشاطات', desc: 'متابعة حركة المسؤولين', icon: 'activity',
            href: route('admin.activity.index'), tone: 'text-sky-600 bg-sky-50',
        });
    }
    if (can('setting.view')) {
        actions.push({
            label: 'إعدادات الموقع', desc: 'بيانات ومعلومات التواصل', icon: 'cog',
            href: route('admin.settings.index'), tone: 'text-surface-600 bg-surface-100',
        });
    }
    return actions;
});

function priceLabel(product) {
    const value = product.sale_price ?? product.price;
    return formatPrice(value, product.currency);
}
</script>

<template>
    <Head title="لوحة التحكم" />

    <AdminLayout>
        <!-- Welcome hero -->
        <section class="relative overflow-hidden rounded-3xl bg-gradient-premium p-6 text-white shadow-premium-lg sm:p-8">
            <div class="pointer-events-none absolute -left-16 -top-16 h-64 w-64 rounded-full bg-brand-600/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-10 h-72 w-72 rounded-full bg-gold-500/10 blur-3xl"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-6">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gold-400">معرض أبو عفيفة للمفروشات</p>
                    <h2 class="mt-1 font-display text-2xl font-bold sm:text-3xl">
                        {{ greeting }}، {{ user.name }}
                    </h2>
                    <p class="mt-2 text-sm text-surface-300">{{ todayLabel }} — إليك ملخص متجرك اليوم.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium backdrop-blur-sm">
                        <AdminIcon name="shield" size="sm" class="text-gold-400" />
                        <span>{{ roleLabel }}</span>
                    </span>
                    <Link
                        :href="route('home')"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/20 transition hover:bg-white/20"
                    >
                        <AdminIcon name="eye" size="sm" />
                        عرض المتجر
                    </Link>
                </div>
            </div>
        </section>

        <!-- Stat cards -->
        <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="(card, i) in statCards"
                :key="card.key"
                :href="card.href"
                class="card-admin group stagger-1 animate-fade-in-up p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-premium-lg"
                :style="{ animationDelay: `${i * 0.06}s` }"
            >
                <div class="flex items-start justify-between gap-3">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl transition ring-4 ring-transparent" :class="[card.wrap, card.ring]">
                        <AdminIcon :name="card.icon" size="xl" />
                    </span>
                    <span class="text-3xl font-bold tabular-nums text-surface-900">{{ card.value }}</span>
                </div>
                <p class="mt-4 text-sm font-semibold text-surface-900">{{ card.label }}</p>
                <p class="mt-0.5 text-xs text-surface-400">{{ card.hint }}</p>
            </Link>
        </section>

        <!-- Quick actions + activity -->
        <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Quick actions -->
            <div class="card-admin animate-fade-in-up p-5 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-surface-900">إجراءات سريعة</h3>
                    <AdminIcon name="sparkles" size="sm" class="text-gold-500" />
                </div>
                <div v-if="quickActions.length" class="mt-4 space-y-1.5">
                    <Link
                        v-for="action in quickActions"
                        :key="action.href"
                        :href="action.href"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-surface-50"
                    >
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg" :class="action.tone">
                            <AdminIcon :name="action.icon" size="sm" />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-surface-900">{{ action.label }}</span>
                            <span class="block truncate text-xs text-surface-400">{{ action.desc }}</span>
                        </span>
                        <AdminIcon name="chevron-left" size="sm" class="ms-auto shrink-0 text-surface-300" />
                    </Link>
                </div>
                <p v-else class="mt-4 text-sm text-surface-400">لا توجد إجراءات متاحة لصلاحياتك.</p>
            </div>

            <!-- Recent activity -->
            <div class="card-admin animate-fade-in-up p-5 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-surface-900">أحدث النشاطات</h3>
                    <Link v-if="can('activity.view')" :href="route('admin.activity.index')" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                        عرض الكل
                    </Link>
                </div>

                <div v-if="recent_activity?.length" class="mt-4">
                    <ol class="relative space-y-1 border-s border-surface-100 ps-5">
                        <li v-for="log in recent_activity" :key="log.id" class="relative pb-4 last:pb-0">
                            <span
                                class="absolute -start-[26px] top-1.5 grid h-4 w-4 place-items-center rounded-full ring-4"
                                :class="log.is_security_event ? 'bg-red-500 ring-red-100' : 'bg-gold-500 ring-gold-100'"
                            >
                                <AdminIcon v-if="log.is_security_event" name="shield" size="xs" class="text-white" />
                            </span>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-surface-900">{{ log.action }}</p>
                                    <p class="mt-0.5 truncate text-xs text-surface-400">
                                        بواسطة <span class="font-medium text-surface-600">{{ log.user }}</span>
                                        <template v-if="log.subject"> · {{ log.subject }}</template>
                                    </p>
                                </div>
                                <span class="shrink-0 text-xs text-surface-400">{{ log.created_at }}</span>
                            </div>
                        </li>
                    </ol>
                </div>
                <p v-else class="mt-4 text-sm text-surface-400">لا توجد نشاطات مسجلة بعد.</p>
            </div>
        </section>

        <!-- Recent messages + recent products -->
        <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Messages -->
            <div class="card-admin p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-surface-900">أحدث الرسائل</h3>
                    <Link v-if="can('contact.view')" :href="route('admin.messages.index')" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                        عرض الكل
                    </Link>
                </div>

                <div v-if="recent_messages?.length" class="mt-3 divide-y divide-surface-100">
                    <Link
                        v-for="msg in recent_messages"
                        :key="msg.id"
                        :href="route('admin.messages.show', msg.id)"
                        class="flex items-center gap-3 rounded-lg px-1 py-3 transition hover:bg-surface-50"
                    >
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-surface-100 text-surface-500">
                            <AdminIcon name="user" size="sm" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-sm font-semibold" :class="msg.is_read ? 'text-surface-700' : 'text-surface-900'">
                                    {{ msg.name }}
                                </span>
                                <span v-if="!msg.is_read" class="h-2 w-2 shrink-0 rounded-full bg-brand-600"></span>
                            </span>
                            <span class="block truncate text-xs text-surface-400">{{ msg.subject }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-surface-400">{{ msg.created_at }}</span>
                    </Link>
                </div>
                <p v-else class="mt-4 text-sm text-surface-400">لا توجد رسائل بعد.</p>
            </div>

            <!-- Products -->
            <div class="card-admin p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-surface-900">أحدث المنتجات</h3>
                    <Link v-if="can('product.view')" :href="route('admin.products.index')" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                        عرض الكل
                    </Link>
                </div>

                <div v-if="recent_products?.length" class="mt-3 divide-y divide-surface-100">
                    <Link
                        v-for="product in recent_products"
                        :key="product.id"
                        :href="route('admin.products.show', product.id)"
                        class="flex items-center gap-3 rounded-lg px-1 py-3 transition hover:bg-surface-50"
                    >
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600">
                            <AdminIcon name="box" size="sm" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-1.5">
                                <span class="truncate text-sm font-semibold text-surface-900">{{ product.name }}</span>
                                <AdminIcon v-if="product.is_featured" name="star" size="xs" class="shrink-0 text-gold-500" />
                            </span>
                            <span class="block truncate text-xs text-surface-400">{{ product.category || '—' }}</span>
                        </span>
                        <span class="shrink-0 text-end">
                            <span class="block text-sm font-bold text-surface-900">{{ priceLabel(product) }}</span>
                            <span class="badge-admin mt-1" :class="product.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-surface-100 text-surface-500'">
                                {{ product.is_active ? 'متاح' : 'مخفي' }}
                            </span>
                        </span>
                    </Link>
                </div>
                <p v-else class="mt-4 text-sm text-surface-400">لا توجد منتجات بعد.</p>
            </div>
        </section>
    </AdminLayout>
</template>