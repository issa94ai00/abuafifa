<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    actions: Array,
    users: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? {});
const permissions = computed(() => user.value.permissions || []);
const can = (p) => permissions.value.includes(p);

// -------------------------------------------------------------- filter state
const search = ref(props.filters?.search ?? '');
const actionFilter = ref(props.filters?.action ?? '');
const userIdFilter = ref(props.filters?.user_id ?? '');
const securityOnly = ref(!!props.filters?.security_only);
const dateFrom = ref(props.filters?.from ?? '');
const dateTo = ref(props.filters?.to ?? '');

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

function applyFilters(overrides = {}) {
    const params = {
        ...(search.value ? { search: search.value } : {}),
        ...(actionFilter.value ? { action: actionFilter.value } : {}),
        ...(userIdFilter.value ? { user_id: userIdFilter.value } : {}),
        ...(securityOnly.value ? { security_only: 1 } : {}),
        ...(dateFrom.value ? { from: dateFrom.value } : {}),
        ...(dateTo.value ? { to: dateTo.value } : {}),
        page: 1,
        ...overrides,
    };
    router.get(route('admin.activity.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    actionFilter.value = '';
    userIdFilter.value = '';
    securityOnly.value = false;
    dateFrom.value = '';
    dateTo.value = '';
    clearTimeout(searchTimer);
    applyFilters();
}

const hasActiveFilters = computed(() =>
    !!(search.value || actionFilter.value || userIdFilter.value || securityOnly.value || dateFrom.value || dateTo.value),
);

// ------------------------------------------------------------------ action groups
const actionGroups = computed(() => {
    const groups = {};
    for (const a of props.actions || []) {
        if (!groups[a.group]) groups[a.group] = [];
        groups[a.group].push(a);
    }
    return groups;
});

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

// ------------------------------------------------------------------ pagination
function pageLinks() {
    const links = props.logs?.links ?? [];
    const pages = links.filter((l) => l.url && Number.isInteger(Number(l.label)));

    const current = props.logs?.current_page ?? 1;
    const lastPage = props.logs?.last_page ?? 1;
    const around = 1;

    let first = Math.max(Number(pages[0]?.label ?? 1), current - around);
    let last = Math.min(Number(pages[pages.length - 1]?.label ?? lastPage), current + around);

    const result = [];
    let prev = null;
    for (const link of pages.filter((l) => {
        const n = Number(l.label);
        return n === 1 || n === current || (n >= first && n <= last);
    })) {
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

// ------------------------------------------------------------------ helpers
function formatDateTime(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('ar-JO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

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
</script>

<template>
    <Head title="سجل النشاطات" />

    <AdminLayout>
        <!-- Header actions -->
        <div class="flex flex-wrap items-center justify-between gap-4 animate-fade-in">
            <div>
                <h2 class="text-base font-bold text-surface-900 sm:text-lg">سجل النشاطات</h2>
                <p class="mt-0.5 text-sm text-surface-400">متابعة حركة المسؤولين والتغييرات على النظام</p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    v-if="can('activity.view')"
                    :href="route('admin.activity.export')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-surface-200 bg-white px-3 py-2 text-sm font-semibold text-surface-600 transition hover:bg-surface-50"
                    title="تصدير CSV"
                >
                    <AdminIcon name="external" size="sm" />
                    <span class="hidden sm:inline">تصدير</span>
                </a>
            </div>
        </div>

        <!-- Toolbar -->
        <section class="card-admin mt-6 animate-fade-in-up p-4 sm:p-5" :style="{ animationDelay: '0.05s' }">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-[200px] flex-1">
                    <AdminIcon name="search" size="sm" class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-surface-400" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="بحث في الوصف أو العنصر أو المستخدم..."
                        class="input-admin ps-10"
                    />
                </div>

                <select v-model="actionFilter" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[160px]" @change="applyFilters()">
                    <option value="">كل الإجراءات</option>
                    <optgroup v-for="(items, group) in actionGroups" :key="group" :label="groupLabels[group] || group">
                        <option v-for="a in items" :key="a.value" :value="a.value">{{ a.label }}</option>
                    </optgroup>
                </select>

                <select v-model="userIdFilter" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[150px]" @change="applyFilters()">
                    <option value="">كل المستخدمين</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>

                <div class="flex items-center gap-2">
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="input-admin w-full sm:w-auto"
                        :dir="'ltr'"
                        @change="applyFilters()"
                    />
                    <span class="text-xs text-surface-400">إلى</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="input-admin w-full sm:w-auto"
                        :dir="'ltr'"
                        @change="applyFilters()"
                    />
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold transition"
                    :class="securityOnly ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200' : 'text-surface-500 hover:bg-surface-100 hover:text-surface-700'"
                    @click="(securityOnly = !securityOnly), applyFilters()"
                >
                    <AdminIcon name="shield" size="sm" />
                    أمنية فقط
                </button>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                    @click="resetFilters"
                >
                    <AdminIcon name="refresh" size="sm" />
                    مسح
                </button>
            </div>
        </section>

        <!-- Logs list -->
        <section class="card-admin mt-6 animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.1s' }">
            <div v-if="logs?.data?.length" class="divide-y divide-surface-100">
                <div
                    v-for="log in logs.data"
                    :key="log.id"
                    class="group flex items-start gap-4 px-5 py-4 transition-colors hover:bg-surface-50/60"
                >
                    <!-- Icon -->
                    <span
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-full ring-1 ring-inset"
                        :class="log.is_security_event ? 'bg-red-50 text-red-600 ring-red-200' : (groupTone[log.group] || 'bg-surface-100 text-surface-500 ring-surface-200')"
                    >
                        <AdminIcon :name="log.is_security_event ? 'shield' : 'activity'" size="sm" />
                    </span>

                    <!-- Content -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-sm font-bold text-surface-900">{{ log.action_label }}</span>
                            <span
                                v-if="log.group"
                                class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold ring-1 ring-inset"
                                :class="groupTone[log.group] || 'bg-surface-100 text-surface-500 ring-surface-200'"
                            >
                                {{ groupLabels[log.group] || log.group }}
                            </span>
                            <span v-if="log.is_security_event" class="inline-flex items-center gap-1 rounded-md bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 ring-1 ring-inset ring-red-200">
                                <AdminIcon name="shield" size="xs" />
                                أمني
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-surface-400">
                            بواسطة <span class="font-semibold text-surface-600">{{ log.user?.name ?? 'النظام' }}</span>
                            <template v-if="log.subject?.label"> · {{ log.subject.label }}</template>
                        </p>
                    </div>

                    <!-- Meta -->
                    <div class="flex shrink-0 items-center gap-3">
                        <span v-if="log.ip_address" class="hidden text-xs text-surface-400 md:inline" dir="ltr">{{ log.ip_address }}</span>
                        <span class="whitespace-nowrap text-xs text-surface-400">{{ log.created_at_human }}</span>
                        <Link
                            :href="route('admin.activity.show', log.id)"
                            class="grid h-8 w-8 place-items-center rounded-lg text-surface-400 opacity-0 transition group-hover:opacity-100 hover:bg-brand-50 hover:text-brand-600"
                            title="عرض التفاصيل"
                        >
                            <AdminIcon name="eye" size="sm" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                    <AdminIcon name="activity" size="xl" />
                </span>
                <h3 class="mt-4 text-sm font-bold text-surface-900">لا توجد نشاطات</h3>
                <p class="mt-1 max-w-sm text-sm text-surface-400">
                    {{ hasActiveFilters ? 'لم يتم العثور على نشاطات مطابقة للفلاتر الحالية.' : 'لم تُسجّل أي نشاطات بعد.' }}
                </p>
                <div v-if="hasActiveFilters" class="mt-5">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                        @click="resetFilters"
                    >
                        <AdminIcon name="refresh" size="sm" />
                        مسح الفلاتر
                    </button>
                </div>
            </div>

            <!-- Footer / pagination -->
            <div v-if="logs?.data?.length" class="flex flex-col items-center justify-between gap-4 border-t border-surface-100 px-5 py-4 sm:flex-row">
                <p class="text-xs text-surface-400">
                    عرض <span class="font-semibold text-surface-600">{{ logs.from ?? 0 }}–{{ logs.to ?? 0 }}</span>
                    من إجمالي <span class="font-semibold text-surface-600">{{ logs.total ?? 0 }}</span> سجل
                </p>

                <nav v-if="logs.last_page > 1" class="flex items-center gap-1">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!logs.prev_page_url" aria-label="السابق" @click="goTo(logs.prev_page_url)">
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
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!logs.next_page_url" aria-label="التالي" @click="goTo(logs.next_page_url)">
                        <AdminIcon name="chevron-left" size="sm" />
                    </button>
                </nav>
            </div>
        </section>
    </AdminLayout>
</template>
