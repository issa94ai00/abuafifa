<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    messages: Object,
    stats: Object,
    filters: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

// -------------------------------------------------------------- filter state
const search = ref(props.filters?.search ?? '');
const unreadOnly = ref(!!props.filters?.unread_only);

let searchTimer = null;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

function applyFilters(overrides = {}) {
    router.get(
        route('admin.messages.index'),
        {
            ...(search.value ? { search: search.value } : {}),
            ...(unreadOnly.value ? { unread_only: 1 } : {}),
            page: 1,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters() {
    search.value = '';
    unreadOnly.value = false;
    clearTimeout(searchTimer);
    applyFilters();
}

const hasActiveFilters = computed(() => !!(search.value || unreadOnly.value));

// ------------------------------------------------------------- toggle read
const togglingId = ref(null);

function toggleRead(message) {
    togglingId.value = message.id;
    router.patch(route('admin.messages.toggle-read', message.id), {}, {
        preserveScroll: true,
        onFinish: () => { togglingId.value = null; },
    });
}

// ------------------------------------------------------------- delete
const confirmDelete = ref(null);
const deletingId = ref(null);

function openDelete(message) {
    confirmDelete.value = message;
}

function deleteMessage() {
    if (!confirmDelete.value) return;
    deletingId.value = confirmDelete.value.id;
    router.delete(route('admin.messages.destroy', confirmDelete.value.id), {
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

// ------------------------------------------------------------------ stats
const statCards = computed(() => [
    {
        key: 'total',
        label: 'إجمالي الرسائل',
        value: props.stats?.total ?? 0,
        icon: 'mail',
        tone: 'bg-brand-50 text-brand-600 group-hover:ring-brand-300',
    },
    {
        key: 'unread',
        label: 'غير مقروءة',
        value: props.stats?.unread ?? 0,
        icon: 'inbox',
        tone: 'bg-red-50 text-red-600 group-hover:ring-red-300',
    },
    {
        key: 'read',
        label: 'مقروءة',
        value: (props.stats?.total ?? 0) - (props.stats?.unread ?? 0),
        icon: 'check',
        tone: 'bg-emerald-50 text-emerald-600 group-hover:ring-emerald-300',
    },
]);

// ------------------------------------------------------------------ pagination
function pageLinks() {
    const links = props.messages?.links ?? [];
    const pages = links.filter((l) => l.url && Number.isInteger(Number(l.label)));

    const current = props.messages?.current_page ?? 1;
    const lastPage = props.messages?.last_page ?? 1;
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
function initials(name) {
    return String(name ?? '?')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

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
</script>

<template>
    <Head title="الرسائل" />

    <AdminLayout>
        <!-- Stats -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <div
                v-for="(card, i) in statCards"
                :key="card.key"
                class="card-admin group animate-fade-in-up p-4 sm:p-5"
                :style="{ animationDelay: `${i * 0.05}s` }"
            >
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl ring-4 ring-transparent transition group-hover:ring-current" :class="card.tone">
                        <AdminIcon :name="card.icon" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <span class="block text-2xl font-bold tabular-nums text-surface-900">{{ card.value }}</span>
                        <span class="block truncate text-xs font-medium text-surface-400">{{ card.label }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Toolbar -->
        <section class="card-admin mt-6 animate-fade-in-up p-4 sm:p-5" :style="{ animationDelay: '0.05s' }">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Tabs -->
                <div class="inline-flex items-center gap-1 rounded-xl bg-surface-100 p-1">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                        :class="unreadOnly ? 'text-surface-500 hover:text-surface-700' : 'bg-white text-surface-900 shadow-sm ring-1 ring-black/5'"
                        @click="(unreadOnly = false), applyFilters()"
                    >
                        <AdminIcon name="mail" size="sm" />
                        الكل
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                        :class="unreadOnly ? 'bg-white text-surface-900 shadow-sm ring-1 ring-black/5' : 'text-surface-500 hover:text-surface-700'"
                        @click="(unreadOnly = true), applyFilters()"
                    >
                        <AdminIcon name="inbox" size="sm" />
                        غير مقروءة
                        <span v-if="props.stats?.unread" class="inline-flex min-w-[18px] items-center justify-center rounded-full bg-red-100 px-1.5 text-[11px] font-bold text-red-700">
                            {{ props.stats.unread }}
                        </span>
                    </button>
                </div>

                <div class="relative min-w-[200px] flex-1">
                    <AdminIcon name="search" size="sm" class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-surface-400" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="بحث بالاسم أو البريد أو الموضوع..."
                        class="input-admin ps-10"
                    />
                </div>

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

        <!-- Messages list -->
        <section class="card-admin mt-6 animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.1s' }">
            <div v-if="messages?.data?.length" class="divide-y divide-surface-100">
                <div
                    v-for="msg in messages.data"
                    :key="msg.id"
                    class="group flex items-start gap-4 px-5 py-4 transition-colors hover:bg-surface-50/60"
                    :class="{ 'bg-brand-50/30': !msg.is_read }"
                >
                    <!-- Avatar -->
                    <span
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-xs font-bold"
                        :class="msg.is_read ? 'bg-surface-100 text-surface-500' : 'bg-brand-50 text-brand-700'"
                    >
                        {{ initials(msg.name) }}
                    </span>

                    <!-- Content -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-sm font-bold" :class="msg.is_read ? 'text-surface-700' : 'text-surface-900'">
                                {{ msg.name }}
                            </span>
                            <span v-if="!msg.is_read" class="h-2 w-2 shrink-0 rounded-full bg-brand-600"></span>
                            <span class="hidden text-xs text-surface-400 sm:inline" dir="ltr">&lt;{{ msg.email }}&gt;</span>
                        </div>
                        <p class="mt-0.5 truncate text-sm font-semibold" :class="msg.is_read ? 'text-surface-600' : 'text-surface-800'">
                            {{ msg.subject }}
                        </p>
                        <p class="mt-0.5 line-clamp-1 text-xs text-surface-400">
                            {{ msg.message }}
                        </p>
                    </div>

                    <!-- Meta & actions -->
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="hidden whitespace-nowrap text-xs text-surface-400 md:inline">{{ formatDateTime(msg.created_at) }}</span>

                        <div class="flex items-center gap-1 opacity-0 transition group-hover:opacity-100">
                            <button
                                v-if="can('contact.manage')"
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-lg text-surface-400 transition hover:bg-sky-50 hover:text-sky-600"
                                :title="msg.is_read ? 'تحديد كغير مقروء' : 'تحديد كمقروء'"
                                :disabled="togglingId === msg.id"
                                @click.stop="toggleRead(msg)"
                            >
                                <AdminIcon :name="msg.is_read ? 'inbox' : 'check'" size="sm" />
                            </button>
                            <Link
                                :href="route('admin.messages.show', msg.id)"
                                class="grid h-8 w-8 place-items-center rounded-lg text-surface-400 transition hover:bg-brand-50 hover:text-brand-600"
                                title="عرض التفاصيل"
                            >
                                <AdminIcon name="eye" size="sm" />
                            </Link>
                            <button
                                v-if="can('contact.manage')"
                                type="button"
                                class="grid h-8 w-8 place-items-center rounded-lg text-surface-400 transition hover:bg-red-50 hover:text-red-600"
                                title="حذف"
                                @click.stop="openDelete(msg)"
                            >
                                <AdminIcon name="trash" size="sm" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                    <AdminIcon name="mail" size="xl" />
                </span>
                <h3 class="mt-4 text-sm font-bold text-surface-900">
                    {{ unreadOnly ? 'لا توجد رسائل غير مقروءة' : 'لا توجد رسائل' }}
                </h3>
                <p class="mt-1 max-w-sm text-sm text-surface-400">
                    {{ hasActiveFilters ? 'لم يتم العثور على رسائل مطابقة للفلاتر الحالية.' : 'لم يُرسل أي زائر رسالة بعد.' }}
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
            <div v-if="messages?.data?.length" class="flex flex-col items-center justify-between gap-4 border-t border-surface-100 px-5 py-4 sm:flex-row">
                <p class="text-xs text-surface-400">
                    عرض <span class="font-semibold text-surface-600">{{ messages.from ?? 0 }}–{{ messages.to ?? 0 }}</span>
                    من إجمالي <span class="font-semibold text-surface-600">{{ messages.total ?? 0 }}</span> رسالة
                </p>

                <nav v-if="messages.last_page > 1" class="flex items-center gap-1">
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!messages.prev_page_url" aria-label="السابق" @click="goTo(messages.prev_page_url)">
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
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-surface-500 transition hover:bg-surface-100 disabled:opacity-40" :disabled="!messages.next_page_url" aria-label="التالي" @click="goTo(messages.next_page_url)">
                        <AdminIcon name="chevron-left" size="sm" />
                    </button>
                </nav>
            </div>
        </section>

        <!-- Delete confirmation -->
        <Modal :show="!!confirmDelete" max-width="md" @close="confirmDelete = null">
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600">
                        <AdminIcon name="trash" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-surface-900">حذف الرسالة</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            سيتم حذف رسالة «<span class="font-semibold text-surface-900">{{ confirmDelete?.name }}</span>» نهائياً.
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
                        @click="deleteMessage"
                    >
                        <AdminIcon :name="deletingId === confirmDelete?.id ? 'refresh' : 'trash'" size="sm" />
                        {{ deletingId === confirmDelete?.id ? 'جارٍ الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
