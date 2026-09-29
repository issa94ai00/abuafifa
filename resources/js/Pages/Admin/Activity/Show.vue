<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';

const props = defineProps({ log: Object });

const groupTone = {
    auth: 'bg-sky-50 text-sky-700 ring-sky-200',
    product: 'bg-brand-50 text-brand-700 ring-brand-200',
    category: 'bg-amber-50 text-amber-700 ring-amber-200',
    offer: 'bg-violet-50 text-violet-700 ring-violet-200',
    gallery: 'bg-rose-50 text-rose-700 ring-rose-200',
    media: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    message: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    user: 'bg-gold-50 text-gold-700 ring-gold-200',
    settings: 'bg-surface-100 text-surface-700 ring-surface-200',
    security: 'bg-red-50 text-red-700 ring-red-200',
};

const groupLabels = {
    auth: 'المصادقة',
    product: 'المنتجات',
    category: 'التصنيفات',
    offer: 'العروض',
    gallery: 'المعارض',
    media: 'الوسائط',
    message: 'الرسائل',
    user: 'المستخدمون',
    settings: 'الإعدادات',
    security: 'الأمن',
};

const formatDateTime = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleDateString('ar-JO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const metaRows = computed(() => {
    const rows = [];
    const log = props.log || {};

    if (log.user?.name) {
        rows.push({ label: 'المستخدم', value: log.user.role ? `${log.user.name} (${log.user.role})` : log.user.name, ltr: false });
    }
    if (log.user?.email) rows.push({ label: 'البريد الإلكتروني', value: log.user.email, ltr: true });
    if (log.subject?.label) {
        rows.push({ label: 'العنصر', value: log.subject.type ? `${log.subject.label} — ${log.subject.type} #${log.subject.id}` : log.subject.label, ltr: false });
    }
    if (log.ip_address) rows.push({ label: 'عنوان IP', value: log.ip_address, ltr: true });
    if (log.method) rows.push({ label: 'الطريقة', value: log.method, ltr: true });
    if (log.route_name) rows.push({ label: 'اسم المسار', value: log.route_name, ltr: true });
    if (log.url) rows.push({ label: 'الرابط', value: log.url, ltr: true });
    rows.push({ label: 'التاريخ', value: formatDateTime(log.created_at), ltr: false });
    return rows;
});
</script>

<template>
    <Head title="تفاصيل النشاط" />

    <AdminLayout>
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-surface-400 animate-fade-in">
            <Link :href="route('admin.activity.index')" class="font-semibold transition hover:text-brand-600">سجل النشاطات</Link>
            <AdminIcon name="chevron-left" size="xs" />
            <span class="font-medium text-surface-600 truncate">تفاصيل النشاط</span>
        </nav>

        <!-- Header -->
        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 animate-fade-in-up">
            <div class="flex items-center gap-3">
                <span
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-full ring-1 ring-inset"
                    :class="log?.is_security_event ? 'bg-red-50 text-red-600 ring-red-200' : (groupTone[log?.group] || 'bg-surface-100 text-surface-500 ring-surface-200')"
                >
                    <AdminIcon :name="log?.is_security_event ? 'shield' : 'activity'" size="sm" />
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-surface-900 sm:text-lg">{{ log?.action_label }}</h2>
                        <span
                            v-if="log?.is_security_event"
                            class="inline-flex items-center gap-1 rounded-md bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 ring-1 ring-inset ring-red-200"
                        >
                            <AdminIcon name="shield" size="xs" />
                            حدث أمني
                        </span>
                    </div>
                    <p class="text-xs text-surface-400">{{ log?.created_at_human }} · {{ formatDateTime(log?.created_at) }}</p>
                </div>
            </div>

            <Link
                :href="route('admin.activity.index')"
                class="inline-flex items-center gap-1.5 rounded-xl border border-surface-200 bg-white px-3 py-2 text-sm font-semibold text-surface-600 transition hover:bg-surface-50"
            >
                <AdminIcon name="chevron-right" size="sm" />
                العودة
            </Link>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3 animate-fade-in-up" :style="{ animationDelay: '0.05s' }">
            <!-- Details -->
            <div class="lg:col-span-2">
                <div class="card-admin p-5 sm:p-6">
                    <div class="flex items-center gap-3 border-b border-surface-100 pb-4">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon name="activity" size="sm" />
                        </span>
                        <h3 class="text-sm font-bold text-surface-900">تفاصيل النشاط</h3>
                    </div>

                    <dl class="mt-4 space-y-4">
                        <div class="flex flex-wrap items-start gap-x-6 gap-y-2">
                            <dt class="w-36 shrink-0 text-sm font-semibold text-surface-400">الإجراء</dt>
                            <dd class="min-w-0 flex-1 text-sm font-bold text-surface-900">
                                {{ log?.action_label }}
                                <span
                                    v-if="log?.group"
                                    class="ms-2 inline-flex items-center rounded-md px-1.5 py-0.5 align-middle text-[10px] font-bold ring-1 ring-inset"
                                    :class="groupTone[log?.group] || 'bg-surface-100 text-surface-500 ring-surface-200'"
                                >
                                    {{ groupLabels[log?.group] || log?.group }}
                                </span>
                            </dd>
                        </div>

                        <div v-if="log?.description" class="flex flex-wrap items-start gap-x-6 gap-y-2">
                            <dt class="w-36 shrink-0 text-sm font-semibold text-surface-400">الوصف</dt>
                            <dd class="min-w-0 flex-1 rounded-xl bg-surface-50 px-4 py-3 text-sm leading-6 text-surface-700">
                                {{ log?.description }}
                            </dd>
                        </div>

                        <div v-for="row in metaRows" :key="row.label" class="flex flex-wrap items-start gap-x-6 gap-y-2">
                            <dt class="w-36 shrink-0 text-sm font-semibold text-surface-400">{{ row.label }}</dt>
                            <dd class="min-w-0 flex-1 text-sm font-semibold text-surface-900" :dir="row.ltr ? 'ltr' : undefined" :class="row.ltr ? 'break-all text-start' : ''">
                                {{ row.value }}
                            </dd>
                        </div>
                    </dl>

                    <!-- Raw properties -->
                    <div v-if="log?.properties" class="mt-6 border-t border-surface-100 pt-5">
                        <h4 class="text-sm font-bold text-surface-900">البيانات التفصيلية</h4>
                        <pre class="mt-3 overflow-x-auto rounded-xl bg-surface-950 p-4 text-xs leading-6 text-surface-300" dir="ltr">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <!-- Request info -->
            <aside class="lg:col-span-1">
                <div class="card-admin p-5">
                    <h3 class="text-sm font-bold text-surface-900">معلومات الطلب</h3>

                    <dl class="mt-4 space-y-4">
                        <template v-if="log?.user">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">
                                    {{ (log.user.name || '؟').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase() }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-surface-900">{{ log.user.name }}</p>
                                    <p class="truncate text-xs text-surface-400" dir="ltr">{{ log.user.email }}</p>
                                </div>
                            </div>
                            <div v-if="log.user.role" class="flex items-center justify-between border-t border-surface-100 pt-3">
                                <span class="text-xs font-semibold text-surface-400">الدور</span>
                                <span class="rounded-md bg-surface-100 px-2 py-0.5 text-xs font-semibold text-surface-700">{{ log.user.role }}</span>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-surface-100 text-surface-500">
                                    <AdminIcon name="user" size="sm" />
                                </span>
                                <p class="text-sm font-bold text-surface-900">النظام</p>
                            </div>
                        </template>

                        <div v-if="log?.user_agent" class="border-t border-surface-100 pt-3">
                            <p class="text-xs font-semibold text-surface-400">متصفح العميل</p>
                            <p class="mt-1 break-all text-xs leading-5 text-surface-600" dir="ltr">{{ log.user_agent }}</p>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </AdminLayout>
</template>