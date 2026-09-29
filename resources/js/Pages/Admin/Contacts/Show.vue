<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';

const props = defineProps({ message: Object });

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

function toggleRead() {
    router.patch(route('admin.messages.toggle-read', props.message.id), {}, {
        preserveScroll: true,
    });
}

function formatDateTime(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('ar-JO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function initials(name) {
    return String(name ?? '?')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}
</script>

<template>
    <Head title="تفاصيل الرسالة" />

    <AdminLayout>
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-surface-400 animate-fade-in">
            <Link :href="route('admin.messages.index')" class="font-semibold transition hover:text-brand-600">الرسائل</Link>
            <AdminIcon name="chevron-left" size="xs" />
            <span class="font-medium text-surface-600 truncate">{{ message?.subject }}</span>
        </nav>

        <!-- Header -->
        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 animate-fade-in-up">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">
                    {{ initials(message?.name) }}
                </span>
                <div>
                    <h2 class="text-base font-bold text-surface-900 sm:text-lg">{{ message?.subject }}</h2>
                    <p class="text-xs text-surface-400">
                        بواسطة <span class="font-semibold text-surface-600">{{ message?.name }}</span>
                        · {{ formatDateTime(message?.created_at) }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    v-if="can('contact.manage')"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold transition"
                    :class="message?.is_read ? 'text-surface-500 hover:bg-surface-100 hover:text-surface-700' : 'text-brand-600 hover:bg-brand-50'"
                    @click="toggleRead"
                >
                    <AdminIcon :name="message?.is_read ? 'inbox' : 'check'" size="sm" />
                    {{ message?.is_read ? 'تحديد كغير مقروء' : 'تحديد كمقروء' }}
                </button>
                <Link
                    :href="route('admin.messages.index')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-surface-200 bg-white px-3 py-2 text-sm font-semibold text-surface-600 transition hover:bg-surface-50"
                >
                    <AdminIcon name="chevron-right" size="sm" />
                    العودة
                </Link>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3 animate-fade-in-up" :style="{ animationDelay: '0.05s' }">
            <!-- Sender info -->
            <aside class="lg:col-span-1">
                <div class="card-admin p-5">
                    <h3 class="text-sm font-bold text-surface-900">بيانات المرسل</h3>

                    <div class="mt-4 space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-50 font-display text-sm font-bold text-brand-700">
                                {{ initials(message?.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-surface-900">{{ message?.name }}</p>
                                <p v-if="message?.is_read" class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600">
                                    <AdminIcon name="check" size="xs" />
                                    مقروءة
                                </p>
                                <p v-else class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                                    غير مقروءة
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3 border-t border-surface-100 pt-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-surface-400">البريد الإلكتروني</p>
                                <a :href="`mailto:${message?.email}`" class="mt-0.5 block text-sm font-semibold text-brand-600 hover:text-brand-700" dir="ltr">{{ message?.email }}</a>
                            </div>
                            <div v-if="message?.phone">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-surface-400">رقم الهاتف</p>
                                <a :href="`tel:${message?.phone}`" class="mt-0.5 block text-sm font-semibold text-surface-900 hover:text-brand-600" dir="ltr">{{ message?.phone }}</a>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-surface-400">تاريخ الإرسال</p>
                                <p class="mt-0.5 text-sm font-semibold text-surface-900">{{ formatDateTime(message?.created_at) }}</p>
                            </div>
                            <div v-if="message?.ip_address">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-surface-400">عنوان IP</p>
                                <p class="mt-0.5 text-sm font-semibold text-surface-900" dir="ltr">{{ message?.ip_address }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Message body -->
            <div class="lg:col-span-2">
                <div class="card-admin p-5 sm:p-6">
                    <div class="flex items-center gap-3 border-b border-surface-100 pb-4">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <AdminIcon name="mail" size="sm" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-surface-900">{{ message?.subject }}</h3>
                            <p class="text-xs text-surface-400">محتوى الرسالة</p>
                        </div>
                    </div>

                    <div class="mt-4 text-sm leading-7 text-surface-700 whitespace-pre-wrap">{{ message?.message }}</div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
