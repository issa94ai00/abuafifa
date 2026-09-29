<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="تسجيل الدخول" />

        <!-- Status flash -->
        <div
            v-if="status"
            class="mb-6 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="h-4 w-4 shrink-0">
                <polyline points="20 6 9 17 4 12" />
            </svg>
            <span>{{ status }}</span>
        </div>

        <!-- Heading -->
        <div class="mb-8 text-right">
            <h2 class="font-display text-2xl font-bold text-surface-950 sm:text-[1.7rem]">
                تسجيل الدخول
            </h2>
            <p class="mt-2 text-sm leading-relaxed text-surface-500">
                مرحباً بعودتك! أدخل بياناتك للوصول إلى حسابك.
            </p>
        </div>

        <form @submit.prevent="submit">
            <!-- Email -->
            <div>
                <InputLabel for="email" value="البريد الإلكتروني" />

                <div class="relative mt-2">
                    <span
                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-surface-400"
                        aria-hidden="true"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <rect x="2" y="4" width="20" height="16" rx="2" />
                            <polyline points="22,6 12,13 2,6" />
                        </svg>
                    </span>

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-0 block w-full ps-11"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="example@email.com"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="mt-5">
                <div class="flex items-center justify-between gap-3">
                    <InputLabel for="password" value="كلمة المرور" />

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-medium text-brand-600 transition-colors hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:ring-offset-2 focus:ring-offset-white rounded-md"
                    >
                        نسيت كلمة المرور؟
                    </Link>
                </div>

                <div class="relative mt-2">
                    <span
                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-surface-400"
                        aria-hidden="true"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </span>

                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-0 block w-full ps-11 pe-11"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />

                    <button
                        type="button"
                        class="absolute inset-y-0 end-0 flex items-center pe-4 text-surface-400 transition-colors hover:text-surface-600 focus:outline-none focus:ring-2 focus:ring-brand-500/50 rounded-md"
                        :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                        @click="showPassword = !showPassword"
                    >
                        <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Remember me -->
            <div class="mt-5 flex items-center justify-between">
                <label class="flex cursor-pointer select-none items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2.5 text-sm text-surface-600">تذكرني</span>
                </label>
            </div>

            <!-- Submit -->
            <div class="mt-7">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-gradient-to-l from-brand-600 to-brand-500 px-6 py-3.5 font-bold text-white shadow-gold transition-all duration-300 hover:from-brand-700 hover:to-brand-600 hover:shadow-premium-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg
                        v-if="form.processing"
                        class="h-5 w-5 animate-spin"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <circle class="opacity-30" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z" />
                    </svg>
                    <svg
                        v-else
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="h-5 w-5 rotate-180"
                        aria-hidden="true"
                    >
                        <polyline points="15 18 9 12 15 6" />
                    </svg>

                    <span>{{ form.processing ? 'جاري تسجيل الدخول…' : 'تسجيل الدخول' }}</span>
                </button>
            </div>
        </form>

        <!-- Back to storefront -->
        <div class="mt-7 border-t border-surface-100 pt-6 text-center">
            <Link
                href="/"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-surface-500 transition-colors hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/50 rounded-md"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <line x1="19" y1="12" x2="5" y2="12" />
                </svg>
                العودة إلى الموقع الرئيسي
            </Link>
        </div>
    </GuestLayout>
</template>