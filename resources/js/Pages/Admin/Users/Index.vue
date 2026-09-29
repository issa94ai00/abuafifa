<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: Object,
    stats: Object,
    filters: Object,
    roles: { type: Array, default: () => [] },
});

const page = usePage();
const me = computed(() => page.props.auth?.user);

// -------------------------------------------------------------- filter state
const search = ref(props.filters?.search ?? '');
const roleFilter = ref(props.filters?.role ?? '');

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

function applyFilters(overrides = {}) {
    router.get(
        route('admin.users.index'),
        {
            ...(search.value ? { search: search.value } : {}),
            ...(roleFilter.value ? { role: roleFilter.value } : {}),
            page: 1,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters() {
    search.value = '';
    roleFilter.value = '';
    clearTimeout(searchTimer);
    applyFilters();
}

const hasActiveFilters = computed(() => !!(search.value || roleFilter.value));
const assignableRoles = computed(() => props.roles.filter((r) => r.assignable));

const roleTone = {
    'super-admin': 'bg-gold-100 text-gold-800 ring-gold-300',
    'admin': 'bg-brand-50 text-brand-700 ring-brand-300',
    'content-manager': 'bg-emerald-50 text-emerald-700 ring-emerald-300',
};

// ------------------------------------------------------------------ stats
const statCards = computed(() => [
    {
        key: 'total',
        label: 'إجمالي المستخدمين',
        value: props.stats?.total ?? 0,
        icon: 'users',
        tone: 'bg-brand-50 text-brand-600 group-hover:ring-brand-300',
    },
    {
        key: 'active',
        label: 'حسابات نشطة',
        value: props.stats?.active ?? 0,
        icon: 'check',
        tone: 'bg-emerald-50 text-emerald-600 group-hover:ring-emerald-300',
    },
    {
        key: 'staff',
        label: 'فريق العمل',
        value: props.stats?.staff ?? 0,
        icon: 'shield',
        tone: 'bg-gold-50 text-gold-600 group-hover:ring-gold-300',
    },
    {
        key: 'monthly',
        label: 'جدد هذا الشهر',
        value: props.stats?.monthly ?? 0,
        icon: 'calendar',
        tone: 'bg-surface-100 text-surface-600 group-hover:ring-surface-300',
    },
]);

function initials(name) {
    return String(name ?? '؟')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

function formatDate(value, withTime = false) {
    if (!value) return '—';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    if (withTime) options.hour = '2-digit';
    if (withTime) options.minute = '2-digit';
    return new Date(value).toLocaleDateString('ar-JO', options);
}

// ------------------------------------------------------------- create/edit
const formOpen = ref(false);
const editingUserId = ref(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    locale: 'ar',
    is_active: true,
    roles: [],
});

const isEditing = computed(() => !!editingUserId.value);

function openCreate() {
    editingUserId.value = null;
    form.clearErrors();
    form.reset();
    formOpen.value = true;
}

function openEdit(user) {
    editingUserId.value = user.id;
    form.clearErrors();
    form.defaults({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        password: '',
        password_confirmation: '',
        locale: user.locale ?? 'ar',
        is_active: !!user.is_active,
        roles: [...(user.roles ?? [])],
    });
    form.reset();
    formOpen.value = true;
}

function toggleRole(name) {
    const index = form.roles.indexOf(name);
    if (index === -1) {
        if (form.roles.length < 3) form.roles.push(name);
    } else {
        form.roles.splice(index, 1);
    }
}

function submitForm() {
    if (isEditing.value) {
        form.patch(route('admin.users.update', editingUserId.value), {
            preserveScroll: true,
            onSuccess: () => (formOpen.value = false),
            onError: () => {},
        });
    } else {
        form.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => (formOpen.value = false),
            onError: () => {},
        });
    }
}

// ------------------------------------------------------------- toggle active
const togglingActive = ref(null);

function toggleActive(user) {
    if (togglingActive.value || user.can?.toggle_active === false) return;
    togglingActive.value = user.id;
    router.patch(route('admin.users.toggle-active', user.id), {}, {
        preserveScroll: true,
        onFinish: () => (togglingActive.value = null),
    });
}

// ------------------------------------------------------------- delete
const confirmDelete = ref(null);
const deletingId = ref(null);

function openDelete(user) {
    confirmDelete.value = user;
}

function deleteUser() {
    if (!confirmDelete.value) return;
    deletingId.value = confirmDelete.value.id;
    router.delete(route('admin.users.destroy', confirmDelete.value.id), {
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
    const links = props.users?.links ?? [];
    const pages = links.filter((l) => l.url && Number.isInteger(Number(l.label)));

    const current = props.users?.current_page ?? 1;
    const lastPage = props.users?.last_page ?? 1;
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
</script>

<template>
    <Head title="المستخدمون" />

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
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl ring-4 ring-transparent transition group-hover:ring-current" :class="card.tone">
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
                <div class="relative min-w-[220px] flex-1">
                    <AdminIcon name="search" size="sm" class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-surface-400" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="بحث بالاسم أو البريد أو الهاتف..."
                        class="input-admin ps-10"
                    />
                </div>

                <select v-model="roleFilter" class="input-admin w-full cursor-pointer sm:w-auto sm:min-w-[160px]" @change="applyFilters()">
                    <option value="">كل الأدوار</option>
                    <option v-for="role in roles" :key="role.name" :value="role.name">{{ role.label }}</option>
                </select>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98]"
                    @click="openCreate"
                >
                    <AdminIcon name="plus" size="sm" />
                    إضافة مستخدم
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

        <!-- Table -->
        <section class="mt-6 animate-fade-in-up" :style="{ animationDelay: '0.1s' }">
            <div v-if="users?.data?.length" class="card-admin overflow-hidden">
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full divide-y divide-surface-100">
                        <thead>
                            <tr class="bg-surface-50/70">
                                <th class="px-5 py-3.5 text-end text-[11px] font-bold uppercase tracking-wide text-surface-400">المستخدم</th>
                                <th class="px-5 py-3.5 text-end text-[11px] font-bold uppercase tracking-wide text-surface-400">الدور</th>
                                <th class="px-5 py-3.5 text-end text-[11px] font-bold uppercase tracking-wide text-surface-400">الحالة</th>
                                <th class="px-5 py-3.5 text-end text-[11px] font-bold uppercase tracking-wide text-surface-400">آخر دخول</th>
                                <th class="px-5 py-3.5 text-end text-[11px] font-bold uppercase tracking-wide text-surface-400">تاريخ الإنشاء</th>
                                <th class="px-5 py-3.5 text-end text-[11px] font-bold uppercase tracking-wide text-surface-400">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 bg-white">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="group transition hover:bg-brand-50/40"
                                :class="{ 'opacity-75': !user.is_active }"
                            >
                                <!-- user -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-xs font-bold shadow-sm"
                                            :class="user.is_active ? 'bg-brand-50 text-brand-700' : 'bg-surface-200 text-surface-500'"
                                        >
                                            {{ initials(user.name) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-1.5 truncate text-sm font-bold text-surface-900">
                                                {{ user.name }}
                                                <span v-if="me?.id === user.id" class="rounded-md bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold text-brand-600">أنت</span>
                                            </p>
                                            <p class="truncate text-xs text-surface-400" dir="ltr">{{ user.email }}</p>
                                            <p v-if="user.phone" class="truncate text-xs text-surface-400" dir="ltr">{{ user.phone }}</p>
                                        </div>
                                    </div>
                                </td>
                                <!-- roles -->
                                <td class="px-5 py-4">
                                    <div class="flex max-w-[220px] flex-wrap gap-1">
                                        <span
                                            v-for="role in user.role_labels"
                                            :key="role"
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
                                            :class="roleTone[user.roles?.[user.role_labels.indexOf(role)]] || roleTone['content-manager']"
                                        >
                                            {{ role }}
                                        </span>
                                        <span v-if="!user.role_labels?.length" class="text-xs text-surface-400">بدون دور</span>
                                    </div>
                                </td>
                                <!-- status -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <button
                                            type="button"
                                            role="switch"
                                            :aria-checked="!!user.is_active"
                                            class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-40"
                                            :class="user.is_active ? 'bg-emerald-500' : 'bg-surface-300'"
                                            :disabled="user.can?.toggle_active === false || togglingActive === user.id"
                                            :title="user.can?.toggle_active === false ? 'لا يمكن تعطيل حسابك' : ''"
                                            @click="toggleActive(user)"
                                        >
                                            <span
                                                class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all duration-200"
                                                :class="user.is_active ? 'start-6' : 'start-0.5'"
                                            ></span>
                                        </button>
                                        <span class="text-xs font-semibold" :class="user.is_active ? 'text-emerald-600' : 'text-surface-400'">
                                            {{ user.is_active ? 'نشط' : 'معطل' }}
                                        </span>
                                    </div>
                                </td>
                                <!-- last login -->
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-surface-500">
                                    <span v-if="user.last_login_at" class="font-semibold text-surface-600">{{ formatDate(user.last_login_at, true) }}</span>
                                    <span v-else class="text-xs text-surface-400">لم يسجل بعد</span>
                                </td>
                                <!-- created -->
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-surface-500">{{ formatDate(user.created_at) }}</td>
                                <!-- actions -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            v-if="user.can?.update"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-brand-50 hover:text-brand-600"
                                            title="تعديل"
                                            @click="openEdit(user)"
                                        >
                                            <AdminIcon name="edit" size="sm" />
                                        </button>
                                        <button
                                            v-if="user.can?.delete"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-red-50 hover:text-red-600"
                                            title="حذف"
                                            @click="openDelete(user)"
                                        >
                                            <AdminIcon name="trash" size="sm" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile cards -->
                <div class="divide-y divide-surface-100 lg:hidden">
                    <article
                        v-for="user in users.data"
                        :key="user.id"
                        class="p-4"
                        :class="{ 'opacity-75': !user.is_active }"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-xs font-bold shadow-sm"
                                :class="user.is_active ? 'bg-brand-50 text-brand-700' : 'bg-surface-200 text-surface-500'"
                            >
                                {{ initials(user.name) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="flex items-center gap-1.5 text-sm font-bold leading-5 text-surface-900">
                                    <span class="min-w-0 truncate">{{ user.name }}</span>
                                    <span v-if="me?.id === user.id" class="shrink-0 rounded-md bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold text-brand-600">أنت</span>
                                </p>
                                <p class="truncate text-xs text-surface-400" dir="ltr">{{ user.email }}</p>
                                <p v-if="user.phone" class="truncate text-xs text-surface-400" dir="ltr">{{ user.phone }}</p>

                                <div class="mt-2 flex flex-wrap gap-1">
                                    <span
                                        v-for="role in user.role_labels"
                                        :key="role"
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
                                        :class="roleTone[user.roles?.[user.role_labels.indexOf(role)]] || roleTone['content-manager']"
                                    >
                                        {{ role }}
                                    </span>
                                    <span v-if="!user.role_labels?.length" class="text-xs text-surface-400">بدون دور</span>
                                </div>

                                <dl class="mt-2 space-y-0.5 text-xs text-surface-400">
                                    <div class="flex gap-1.5">
                                        <dt>آخر دخول:</dt>
                                        <dd class="font-semibold text-surface-600">
                                            {{ user.last_login_at ? formatDate(user.last_login_at, true) : 'لم يسجل بعد' }}
                                        </dd>
                                    </div>
                                    <div class="flex gap-1.5">
                                        <dt>أُنشئ في:</dt>
                                        <dd class="font-semibold text-surface-600">{{ formatDate(user.created_at) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-surface-100 pt-3">
                            <div class="flex items-center gap-2.5">
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="!!user.is_active"
                                    class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-40"
                                    :class="user.is_active ? 'bg-emerald-500' : 'bg-surface-300'"
                                    :disabled="user.can?.toggle_active === false || togglingActive === user.id"
                                    :aria-label="user.is_active ? 'تعطيل الحساب' : 'تفعيل الحساب'"
                                    @click="toggleActive(user)"
                                >
                                    <span
                                        class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all duration-200"
                                        :class="user.is_active ? 'start-6' : 'start-0.5'"
                                    ></span>
                                </button>
                                <span class="text-xs font-semibold" :class="user.is_active ? 'text-emerald-600' : 'text-surface-400'">
                                    {{ user.is_active ? 'نشط' : 'معطل' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    v-if="user.can?.update"
                                    type="button"
                                    class="grid h-10 w-10 place-items-center rounded-lg text-surface-400 transition hover:bg-brand-50 hover:text-brand-600"
                                    aria-label="تعديل"
                                    @click="openEdit(user)"
                                >
                                    <AdminIcon name="edit" size="sm" />
                                </button>
                                <button
                                    v-if="user.can?.delete"
                                    type="button"
                                    class="grid h-10 w-10 place-items-center rounded-lg text-surface-400 transition hover:bg-red-50 hover:text-red-600"
                                    aria-label="حذف"
                                    @click="openDelete(user)"
                                >
                                    <AdminIcon name="trash" size="sm" />
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="card-admin flex flex-col items-center justify-center px-6 py-20 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                    <AdminIcon name="users" size="xl" />
                </span>
                <h3 class="mt-4 text-sm font-bold text-surface-900">لا يوجد مستخدمون</h3>
                <p class="mt-1 max-w-sm text-sm text-surface-400">
                    {{ hasActiveFilters ? 'لم يتم العثور على مستخدمين مطابقين للفلاتر الحالية.' : 'أضف أول مستخدم لإدارة فريق العمل.' }}
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
                        v-if="!hasActiveFilters"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700"
                        @click="openCreate"
                    >
                        <AdminIcon name="plus" size="sm" />
                        إضافة أول مستخدم
                    </button>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="users?.data?.length" class="mt-4 flex flex-col items-center justify-between gap-4 rounded-2xl border border-surface-100 bg-white px-5 py-4 sm:flex-row">
                <p class="text-xs text-surface-400">
                    عرض <span class="font-semibold text-surface-600">{{ users.from ?? 0 }}–{{ users.to ?? 0 }}</span>
                    من إجمالي <span class="font-semibold text-surface-600">{{ users.total ?? 0 }}</span> مستخدم
                </p>

                <nav v-if="users.last_page > 1" class="flex items-center gap-1">
                    <button
                        type="button"
                        class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40"
                        :disabled="!users.prev_page_url"
                        aria-label="السابق"
                        @click="goTo(users.prev_page_url)"
                    >
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
                    <button
                        type="button"
                        class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40"
                        :disabled="!users.next_page_url"
                        aria-label="التالي"
                        @click="goTo(users.next_page_url)"
                    >
                        <AdminIcon name="chevron-left" size="sm" />
                    </button>
                </nav>
            </div>
        </section>

        <!-- Create / Edit modal -->
        <Modal :show="formOpen" max-width="lg" @close="formOpen = false">
            <div class="max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-surface-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon :name="isEditing ? 'edit' : 'users'" size="sm" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-surface-900">{{ isEditing ? 'تعديل المستخدم' : 'إضافة مستخدم جديد' }}</h3>
                            <p class="text-xs text-surface-400">{{ isEditing ? 'تحديث بيانات الحساب والدور' : 'إنشاء حساب للوصول إلى لوحة التحكم' }}</p>
                        </div>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-surface-100 hover:text-surface-700" aria-label="إغلاق" @click="formOpen = false">
                        <AdminIcon name="close" size="sm" />
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-x-5 gap-y-5 p-6 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">الاسم الكامل</label>
                        <input v-model="form.name" type="text" class="input-admin" placeholder="مثال: محمد أحمد" />
                        <InputError :message="form.errors.name" class="mt-1.5" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">البريد الإلكتروني</label>
                        <input v-model="form.email" type="email" dir="ltr" class="input-admin" placeholder="user@example.com" />
                        <InputError :message="form.errors.email" class="mt-1.5" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">رقم الهاتف</label>
                        <input v-model="form.phone" type="tel" dir="ltr" class="input-admin" placeholder="09xxxxxxxx" />
                        <InputError :message="form.errors.phone" class="mt-1.5" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">اللغة</label>
                        <select v-model="form.locale" class="input-admin cursor-pointer">
                            <option value="ar">العربية</option>
                            <option value="en">English</option>
                        </select>
                        <InputError :message="form.errors.locale" class="mt-1.5" />
                    </div>

                    <div v-if="!isEditing">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">كلمة المرور</label>
                        <input v-model="form.password" type="password" dir="ltr" class="input-admin" placeholder="12+ حرفاً" />
                        <InputError :message="form.errors.password" class="mt-1.5" />
                    </div>

                    <div v-if="!isEditing">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">تأكيد كلمة المرور</label>
                        <input v-model="form.password_confirmation" type="password" dir="ltr" class="input-admin" placeholder="أعد كتابة كلمة المرور" />
                        <InputError :message="form.errors.password_confirmation" class="mt-1.5" />
                    </div>

                    <div v-if="isEditing" class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">كلمة مرور جديدة <span class="font-normal text-surface-400">(اختياري — اتركها فارغة للإبقاء على الحالية)</span></label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <input v-model="form.password" type="password" dir="ltr" class="input-admin" placeholder="12+ حرفاً" />
                            <input v-model="form.password_confirmation" type="password" dir="ltr" class="input-admin" placeholder="تأكيد كلمة المرور" />
                        </div>
                        <InputError :message="form.errors.password || form.errors.password_confirmation" class="mt-1.5" />
                    </div>

                    <div v-if="!isEditing" class="sm:col-span-2">
                        <div class="flex items-center justify-between rounded-xl bg-surface-50 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-surface-700">حساب نشط</p>
                                <p class="text-xs text-surface-400">يمكن تفعيله أو تعطيله لاحقاً</p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form.is_active"
                                class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200"
                                :class="form.is_active ? 'bg-emerald-500' : 'bg-surface-300'"
                                @click="form.is_active = !form.is_active"
                            >
                                <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all duration-200" :class="form.is_active ? 'start-6' : 'start-0.5'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-semibold text-surface-700">الأدوار <span class="font-normal text-surface-400">(حتى 3 أدوار)</span></label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <label
                                v-for="role in assignableRoles"
                                :key="role.name"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition"
                                :class="form.roles.includes(role.name) ? 'border-brand-400 bg-brand-50/60 ring-1 ring-brand-300' : 'border-surface-200 hover:border-surface-300 hover:bg-surface-50'"
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-surface-300 text-brand-600 accent-brand-600"
                                    :checked="form.roles.includes(role.name)"
                                    :disabled="form.roles.length >= 3 && !form.roles.includes(role.name)"
                                    @change="toggleRole(role.name)"
                                />
                                <span class="text-sm font-semibold text-surface-700">{{ role.label }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.roles || form.errors['roles.0']" class="mt-1.5" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-surface-100 px-6 py-4">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                        @click="formOpen = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                        :disabled="form.processing"
                        @click="submitForm"
                    >
                        <AdminIcon :name="form.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': form.processing }" />
                        {{ form.processing ? 'جارٍ الحفظ...' : (isEditing ? 'حفظ التعديلات' : 'إنشاء الحساب') }}
                    </button>
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
                        <h3 class="text-base font-bold text-surface-900">حذف المستخدم</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم حذف حساب «<span class="font-semibold text-surface-900">{{ confirmDelete?.name }}</span>»
                            وأي جلسات تسجيل دخول نشطة. لا يمكن التراجع عن هذا الإجراء.
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
                        @click="deleteUser"
                    >
                        <AdminIcon :name="deletingId === confirmDelete?.id ? 'refresh' : 'trash'" size="sm" />
                        {{ deletingId === confirmDelete?.id ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>