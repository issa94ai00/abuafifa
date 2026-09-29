<script setup>
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    contact: { type: Object, default: () => ({}) },
    seo: { type: Object, default: () => ({}) },
});

const page = usePage();

const form = useForm({
    name: '',
    phone: '',
    email: '',
    subject: '',
    message: '',
});

function submit() {
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

/** A dialable tel: target — separators stripped, any country code kept. */
function telHref(value) {
    const cleaned = String(value ?? '').replace(/[^\d+]/g, '');
    return cleaned ? `tel:${cleaned}` : null;
}

/** wa.me wants bare digits, and treats a 00 prefix as part of the number. */
function whatsappHref(value) {
    let digits = String(value ?? '').replace(/\D/g, '');
    if (digits.startsWith('00')) digits = digits.slice(2);
    return digits ? `https://wa.me/${digits}` : null;
}

const infoItems = computed(() => [
    { icon: 'phone', label: 'الهاتف', value: props.contact?.phone, href: telHref(props.contact?.phone), ltr: true },
    { icon: 'whatsapp', label: 'واتساب', value: props.contact?.whatsapp, href: whatsappHref(props.contact?.whatsapp), ltr: true },
    { icon: 'mail', label: 'البريد الإلكتروني', value: props.contact?.email, href: props.contact?.email ? `mailto:${props.contact.email}` : null, ltr: true },
    { icon: 'map-pin', label: 'العنوان', value: props.contact?.address },
    { icon: 'clock', label: 'ساعات العمل', value: props.contact?.working_hours },
]);

const whatsappLink = computed(() => whatsappHref(page.props.settings?.whatsapp));

const hasMessage = computed(() => Boolean(page.props.flash?.success));
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <div
                class="absolute inset-0 opacity-[0.07] bg-cover bg-center"
                style="background-image: url('/images/home/texture-cta-1200.jpg')"
            ></div>

            <!-- Floating decorative shapes -->
            <div class="absolute top-24 right-[12%] w-2 h-2 rounded-full bg-gold-400/40 animate-pulse" aria-hidden="true"></div>
            <div class="absolute top-44 left-[16%] w-1.5 h-1.5 rounded-full bg-gold-300/30 animate-pulse" style="animation-delay: 1s" aria-hidden="true"></div>
            <div class="absolute bottom-36 right-[20%] w-1 h-1 rounded-full bg-gold-500/50 animate-pulse" style="animation-delay: 0.5s" aria-hidden="true"></div>
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[40rem] h-[40rem] rounded-full bg-gold-500/10 blur-3xl" aria-hidden="true"></div>
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-500/25 to-transparent" aria-hidden="true"></div>

            <div class="section-container pt-32 pb-20 sm:pt-40 sm:pb-28 relative">
                <RevealOnScroll animation="fade-in-up">
                    <span class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium mb-4">
                        <span class="h-px w-8 bg-gradient-to-l from-gold-500 to-transparent"></span>
                        يسعدنا التواصل معك
                        <span class="h-px w-8 bg-gradient-to-r from-gold-500 to-transparent"></span>
                    </span>
                    <h1 class="font-display text-3xl sm:text-5xl md:text-6xl font-bold text-white leading-tight mb-5">
                        تواصل معنا
                        <span class="block text-transparent bg-clip-text bg-gradient-to-l from-gold-300 to-gold-500 mt-2">
                            راسلنا ولا تتردد
                        </span>
                    </h1>
                    <p class="text-surface-300 text-base sm:text-lg max-w-2xl leading-relaxed">
                        راسلنا لاستشارة مجانية أو لأي استفسار عن منتجاتنا، وسنعود إليك في أقرب وقت.
                    </p>

                    <!-- Response-time pill -->
                    <div class="mt-8 inline-flex items-center gap-2.5 px-5 py-2.5 rounded-full bg-white/5 backdrop-blur-sm border border-white/10">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        <span class="text-surface-300 text-sm">نرد عادةً خلال 24 ساعة</span>
                    </div>
                </RevealOnScroll>
            </div>

            <!-- Bottom edge fade -->
            <div class="absolute bottom-0 inset-x-0 h-24 bg-gradient-to-t from-surface-50 to-transparent" aria-hidden="true"></div>
        </section>

        <section class="section-container py-14 sm:py-20">
            <div class="grid lg:grid-cols-[1fr_400px] gap-10 lg:gap-14 items-start">
                <!-- Form -->
                <RevealOnScroll animation="fade-in-up">
                    <form class="relative bg-white rounded-3xl border border-surface-100 shadow-premium-lg overflow-hidden" @submit.prevent="submit">
                        <!-- Accent bar -->
                        <div class="h-1.5 bg-gradient-to-l from-brand-600 via-gold-500 to-brand-600" aria-hidden="true"></div>

                        <div class="p-6 sm:p-10">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-50 to-gold-50 ring-1 ring-brand-100 flex items-center justify-center text-brand-600">
                                    <Icon name="mail" class="w-5 h-5" />
                                </div>
                                <h2 class="font-display text-xl sm:text-2xl font-bold text-surface-900">
                                    أرسل لنا رسالة
                                </h2>
                            </div>
                            <p class="text-sm text-surface-500 mb-8 pr-14">
                                املأ النموذج وسيتواصل معك فريقنا في أقرب وقت.
                            </p>

                            <!-- Success message -->
                            <Transition
                                enter-active-class="transition ease-out duration-300"
                                enter-from-class="opacity-0 -translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-200"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div
                                    v-if="hasMessage && !form.errors.name"
                                    class="mb-7 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800"
                                    role="status"
                                >
                                    <span class="w-9 h-9 shrink-0 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                                        <Icon name="check" class="w-4 h-4" />
                                    </span>
                                    <p class="text-sm font-medium">
                                        تم إرسال رسالتك بنجاح، سنتواصل معك قريباً.
                                    </p>
                                </div>
                            </Transition>

                            <div class="grid sm:grid-cols-2 gap-5 mb-5">
                                <div>
                                    <label for="name" class="flex items-center gap-1.5 text-sm font-medium text-surface-700 mb-2">
                                        الاسم <span class="text-brand-600">*</span>
                                    </label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="اسمك الكريم"
                                        class="w-full bg-surface-50 border border-surface-200 rounded-xl px-4 py-3 text-sm placeholder:text-surface-400 transition-colors duration-200 hover:border-surface-300 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                        :class="{ 'border-red-400 focus:ring-red-500': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label for="phone" class="flex items-center gap-1.5 text-sm font-medium text-surface-700 mb-2">
                                        رقم الهاتف <span class="text-brand-600">*</span>
                                    </label>
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        placeholder="07X XXXX XXX"
                                        dir="ltr"
                                        class="w-full bg-surface-50 border border-surface-200 rounded-xl px-4 py-3 text-sm placeholder:text-surface-400 transition-colors duration-200 hover:border-surface-300 focus:outline-none focus:ring-2 focus:ring-brand-500/40 text-right"
                                        :class="{ 'border-red-400 focus:ring-red-500': form.errors.phone }"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1.5 text-xs text-red-600">{{ form.errors.phone }}</p>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5 mb-5">
                                <div>
                                    <label for="email" class="flex items-center gap-1.5 text-sm font-medium text-surface-700 mb-2">البريد الإلكتروني</label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="you@example.com"
                                        dir="ltr"
                                        class="w-full bg-surface-50 border border-surface-200 rounded-xl px-4 py-3 text-sm placeholder:text-surface-400 transition-colors duration-200 hover:border-surface-300 focus:outline-none focus:ring-2 focus:ring-brand-500/40 text-right"
                                        :class="{ 'border-red-400 focus:ring-red-500': form.errors.email }"
                                    />
                                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-600">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <label for="subject" class="flex items-center gap-1.5 text-sm font-medium text-surface-700 mb-2">الموضوع</label>
                                    <input
                                        id="subject"
                                        v-model="form.subject"
                                        type="text"
                                        placeholder="استشارة، طلب عرض سعر..."
                                        class="w-full bg-surface-50 border border-surface-200 rounded-xl px-4 py-3 text-sm placeholder:text-surface-400 transition-colors duration-200 hover:border-surface-300 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                    />
                                </div>
                            </div>

                            <div class="mb-7">
                                <label for="message" class="flex items-center gap-1.5 text-sm font-medium text-surface-700 mb-2">
                                    الرسالة <span class="text-brand-600">*</span>
                                </label>
                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    rows="6"
                                    required
                                    placeholder="اكتب رسالتك هنا..."
                                    class="w-full bg-surface-50 border border-surface-200 rounded-xl px-4 py-3 text-sm placeholder:text-surface-400 transition-colors duration-200 hover:border-surface-300 focus:outline-none focus:ring-2 focus:ring-brand-500/40 resize-y"
                                    :class="{ 'border-red-400 focus:ring-red-500': form.errors.message }"
                                ></textarea>
                                <p v-if="form.errors.message" class="mt-1.5 text-xs text-red-600">{{ form.errors.message }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-4">
                                <button
                                    type="submit"
                                    class="btn-primary w-full sm:w-auto"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                    <span v-else>إرسال الرسالة</span>
                                    <Icon v-if="!form.processing" name="arrow-left" class="w-4 h-4" />
                                </button>
                                <p class="text-xs text-surface-400">
                                    الحقول المعلّمة بـ <span class="text-brand-600 font-medium">*</span> إلزامية.
                                </p>
                            </div>
                        </div>
                    </form>
                </RevealOnScroll>

                <!-- Info sidebar -->
                <div class="space-y-5 lg:sticky lg:top-28">
                    <RevealOnScroll animation="fade-in-up">
                        <div class="bg-white rounded-3xl border border-surface-100 shadow-premium overflow-hidden">
                            <div class="px-6 pt-6 pb-5 border-b border-surface-100 bg-gradient-to-l from-brand-50/60 to-gold-50/60">
                                <div class="flex items-center gap-2.5 mb-1">
                                    <Icon name="map-pin" class="w-5 h-5 text-brand-600" />
                                    <h2 class="font-display font-semibold text-surface-900">معلومات التواصل</h2>
                                </div>
                                <p class="text-xs text-surface-500">قنواتنا المفتوحة لخدمتك</p>
                            </div>
                            <ul class="px-6 py-5 space-y-4">
                                <li v-for="item in infoItems" :key="item.label">
                                    <a
                                        v-if="item.href"
                                        :href="item.href"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-start gap-3 text-sm text-surface-600 hover:text-brand-700 transition-colors group"
                                    >
                                        <span class="w-10 h-10 shrink-0 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center group-hover:bg-brand-100 group-hover:shadow-gold transition-all">
                                            <Icon :name="item.icon" class="w-5 h-5" />
                                        </span>
                                        <span>
                                            <span class="block text-xs text-surface-400 mb-0.5">{{ item.label }}</span>
                                            <span class="font-medium" :class="{ 'text-left': item.ltr }" :dir="item.ltr ? 'ltr' : undefined">{{ item.value }}</span>
                                        </span>
                                    </a>
                                    <div v-else class="flex items-start gap-3 text-sm text-surface-600">
                                        <span class="w-10 h-10 shrink-0 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                                            <Icon :name="item.icon" class="w-5 h-5" />
                                        </span>
                                        <span>
                                            <span class="block text-xs text-surface-400 mb-0.5">{{ item.label }}</span>
                                            <span class="font-medium" :class="{ 'text-left': item.ltr }" :dir="item.ltr ? 'ltr' : undefined">{{ item.value }}</span>
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </RevealOnScroll>

                    <RevealOnScroll animation="fade-in-up" :delay="100">
                        <div class="relative bg-gradient-premium rounded-3xl overflow-hidden p-6 shadow-premium">
                            <div class="absolute -top-16 -left-16 w-40 h-40 rounded-full bg-gold-500/10 blur-2xl" aria-hidden="true"></div>
                            <div class="relative">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-gold text-surface-950 flex items-center justify-center shadow-gold">
                                        <Icon name="sparkles" class="w-5 h-5" />
                                    </div>
                                    <h2 class="font-display font-semibold text-white">استشارة مجانية</h2>
                                </div>
                                <p class="text-sm text-surface-300 leading-relaxed mb-5">
                                    فريقنا من المصممين جاهز لمساعدتك في تنسيق منزلك واختيار القطع المناسبة.
                                </p>
                                <a
                                    v-if="whatsappLink"
                                    :href="whatsappLink"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn-white w-full !py-3.5 text-sm shadow-premium"
                                >
                                    <Icon name="whatsapp" class="w-5 h-5 text-[#25D366]" />
                                    استشارة عبر واتساب
                                </a>
                                <Link v-else href="/contact" class="btn-white w-full !py-3.5 text-sm">
                                    استشارة عبر واتساب
                                </Link>
                            </div>
                        </div>
                    </RevealOnScroll>
                </div>
            </div>

            <!-- Map -->
            <RevealOnScroll animation="fade-in-up">
                <div class="mt-14">
                    <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
                        <div>
                            <h2 class="font-display text-xl sm:text-2xl font-bold text-surface-900">موقعنا على الخريطة</h2>
                            <p v-if="contact?.address" class="mt-1 text-sm text-surface-500 flex items-center gap-1.5">
                                <Icon name="map-pin" class="w-4 h-4 text-brand-500" />
                                {{ contact.address }}
                            </p>
                        </div>
                        <a
                            v-if="contact?.map_lat"
                            :href="`https://www.openstreetmap.org/?mlat=${contact.map_lat}&mlon=${contact.map_lng}#map=16/${contact.map_lat}/${contact.map_lng}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors"
                        >
                            فتح في خرائط أكبر
                            <Icon name="arrow-left" class="w-4 h-4" />
                        </a>
                    </div>
                    <div class="relative overflow-hidden rounded-3xl shadow-premium border border-surface-100">
                        <iframe
                            v-if="contact?.map_lat"
                            title="موقع معرض أبو عفيفة للمفروشات"
                            :src="`https://www.openstreetmap.org/export/embed.html?bbox=${Number(contact.map_lng) - 0.008}%2C${Number(contact.map_lat) - 0.005}%2C${Number(contact.map_lng) + 0.008}%2C${Number(contact.map_lat) + 0.005}&layer=mapnik&marker=${contact.map_lat}%2C${contact.map_lng}`"
                            class="w-full h-[380px] border-0"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                    </div>
                </div>
            </RevealOnScroll>
        </section>
    </PublicLayout>
</template>